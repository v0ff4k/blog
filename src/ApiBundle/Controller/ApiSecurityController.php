<?php

namespace ApiBundle\Controller;

use ApiBundle\Security\ApiFormValidator;
use AppBundle\Entity\User;
use AppBundle\Helper\UserHelper;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller used to manage the app security. for /login /logout  pages
 */
class ApiSecurityController extends ApiBaseController
{

    /**
     * Login action
     * @ApiDoc(
     *     resource = true,
     *     description = "Login page",
     *     requirements={ {"name"="request", "dataType"="Symfony\Component\HttpFoundation\Request", } },
     *     parameters={
     *      {"name"="_username", "dataType"="text", "required"=true, "description"="user's username or email"},
     *      {"name"="_password", "dataType"="text", "required"=true, "description"="user's password"},
     *      {"name"="csrf_token", "dataType"="text", "required"=true,
     *              "description"="tempory generated csrf key for forbid bruteforcing and post via unauthorized API"},
     *     },
     *     method="GET, POST",
     *     statusCodes = {
     *     200 = "Returned when auth successful",
     *     401 = "Returned when not authenticated",
     *     404 = "Returned when the type is not found"
     *      }
     * )
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function loginAction(Request $request)
    {
        $tokenId = 'apilogin';
        /** @var \ApiBundle\Service\ApiService $apiService */
        $oApiService = $this->get('api.service.security');

        $csrfToken = UserHelper::getValueCsrfTokenManager($tokenId);
        $defaultUsername = $this->translate('label.username', [], 'messages') .
            '/'.$this->translate('label.email', [], 'messages');
        $form = [ $tokenId => [
            'status' => "login, ".$this->translate("help_text.{$tokenId}.fields"),
            '_username' => $defaultUsername,
            '_password' => $this->translate('label.password', [], 'messages'),
            '_csrf_token' => $csrfToken
        ]];

        if ($request->isMethod('POST')) {
            if (!$oApiService->isValidPostedCsrfToken($request, $tokenId)) {
                $form = [ $tokenId => [
                    'status' => 'check_csrf_token, ' . $this->translate('Invalid CSRF token.', [], 'security'),
                    '_csrf_token' => $csrfToken
                ]];//less strictly than:  throw new InvalidCsrfTokenException('Invalid CSRF token.');

                return $this->returnApiResponse($form);
            }

            list($result, $form, $responseStatus) = $oApiService->formValidate($form, $tokenId, new User, $request);

            if ('bad' === $result) {
                return $this->returnApiResponse($form, $responseStatus);
            }

            $loginOrEmail = $request->get('_username', '');
            $password = $request->get('_password', '');
            $user = $this->getDoctrine()->getRepository('AppBundle:User')->loadUserByUsername($loginOrEmail);

            if (empty($user) or (!$user instanceof User)) {
                $form[$tokenId]['status'] = 'user_not_found, ' . $this->translate('help_text.user_not_found');

                return $this->returnApiResponse($form, Response::HTTP_NOT_FOUND);
            }

            if (!$this->get('security.password_encoder')->isPasswordValid($user, $password)) {
                $form[$tokenId]['status'] = 'check password, ' . $this->translate('help_text.bad_credentials');

                return $this->returnApiResponse($form, Response::HTTP_UNAUTHORIZED);
            }

            $newToken = UserHelper::getInstance()
                ->setUser($user)
                ->completeAuthUserAfterRequest($user, $request)
                ->generateToken();
            UserHelper::getInstance()->setToken($newToken);

            $form = [$tokenId => [
                'status' => 'logged_in, ' . $this->translate("help_text.{$tokenId}.successful"),
                'name' => $user->getName(),
                'token' => $newToken,
            ]];

            return $this->returnApiResponse($form, Response::HTTP_OK);
        }

        return $this->returnApiResponse($form, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * API logout.
     *
     * @ApiDoc(
     *     resource = true,
     *     description = "Logout user, generate new token + cookie. in 100% stateless CLIENT, client forget API token",
     *     requirements={ {"name"="request", "dataType"="Symfony\Component\HttpFoundation\Request", } },
     *     parameters={},
     *     method="GET",
     *     statusCodes = {
     *          302 = "Returned when logged out successful, and redirect to (lo|ca|le)/api/blog",
     *      }
     * )
     * @return Response
     * @throws \Exception
     */
    public function logoutAction()
    {

        /** @var \ApiBundle\Service\ApiService $apiService */
        $this->get('api.service.security')->logoutSuccessful();

        // redirect to homepage
        $response = new Response();
        $response->setStatusCode(Response::HTTP_FOUND);
        $url = UserHelper::getContainer()->get('router')->generate('api_index');
        $response->headers->set('Location', $url);

        return $response;
    }

    /**
     * edit user(author edit Name/Surname/Password)
     *
     * @ApiDoc(
     *     resource = true,
     *     description = "User edit info( editable Name, Surname and Password)",
     *     requirements={ {"name"="request", "dataType"="Symfony\Component\HttpFoundation\Request", } },
     *     parameters={
     *      {"name"="_name", "dataType"="text", "required"=false,
 *                  "description"="user's real name"},
     *      {"name"="_surname", "dataType"="text", "required"=false,
     *              "description"="user's surname"},
     *      {"name"="_plainPassword", "dataType"="text", "required"=false,
     *              "description"="user's password"},
     *      {"name"="csrf_token", "dataType"="text", "required"=true,
     *              "description"="tempory generated csrf key for forbid bruteforcing and post via unauthorized API"},
     *     },
     *     headers={
     *         {
     *             "name"="x-api-key",
     *             "description"="Authorization 'the api' key(also can be in cookie/request as  'token' values)"
     *         }
     *     },
     *     method="GET, PUT",
     *     statusCodes = {
     *     200 = "Returned when update successful",
     *     400 = "Returned, when inputted values are incorrect, validation failed.",
     *     403 = "Returned when not authenticated",
     *     404 = "Returned when the inputted type is not found(incorrect)"
     *      }
     * )
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function editAction(Request $request)
    {

        $this->denyAccessUnlessGrantedFully();
//todo if cookie exist and correct-ok, but if  only api-token - is  NOT OK !!!!!
        $tokenId = 'user_edit';
        /** @var \ApiBundle\Service\ApiService $apiService */
        $oApiService = $this->get('api.service.security');
        /** @var User $user */
        $user = UserHelper::getCurUser();

        //tempory form here.
        $form = [
            '_name' => $user->getName(),
            '_surname' => $user->getSurname(),
            '_plainPassword' => '',
        ];

        list($formResult, $status) = $oApiService
            ->formProcess($form, $tokenId, $user, $request, true, true);

        return $this->returnApiResponse($formResult, $status);
    }

    /**
     * Register user
     *
     * @ApiDoc(
     *     resource = true,
     *     description = "User registration page",
     *     requirements={ {"name"="request", "dataType"="Symfony\Component\HttpFoundation\Request", } },
     *     parameters={
     *      {"name"="_name", "dataType"="text", "required"=true,
     *          "description"="User's real Name, ex: Vasiliy"},
     *      {"name"="_surname", "dataType"="text", "required"=true,
     *          "description"="User's surname, ex: Pupkin"},
     *      {"name"="_username", "dataType"="text", "required"=true,
     *          "description"="User's login name, ex: vl_che-85 "},
     *      {"name"="_email", "dataType"="text", "required"=true,
     *          "description"="User's email, also used as login, ex: vas@ya.serv.com"},
     *      {"name"="_plainPassword", "dataType"="text", "required"=true,
     *          "description"="User's password, ex:  qWe12# "},
     *      {"name"="_csrf_token", "dataType"="text", "required"=true,
     *          "description"="tempory generated CSRF key for forbid bruteforce and posting from unauthorized API"},
     *     },
     *     method="GET, POST",
     *     statusCodes = {
     *     200 = "Returned, when registration successful",
     *     400 = "Returned, when inputted values are incorrect, validation failed.",
     *     404 = "Returned, when the field type is not found"
     *      }
     * )
     *
     * @param Request $request
     * @return RedirectResponse|Response|string
     * @throws \Exception
     */
    public function registerAction(Request $request)
    {
        $user = new User();
        $tokenId = 'registration';

        //tempory here.
        $form = [
            '_name' => $this->translate('label.name', [], 'messages'),
            '_surname' => $this->translate('label.surname', [], 'messages'),
            '_email' => $this->translate('label.email', [], 'messages'),
            '_username' => $this->translate('label.username', [], 'messages'),
            '_plainPassword' => $this->translate('label.password', [], 'messages'),
        ];

        /** @var \ApiBundle\Service\ApiService $apiService */
        $oApiService = $this->get('api.service.security');

        list($formResult, $status) = $oApiService
            ->formProcess($form, $tokenId, $user, $request, true, false);

        return $this->returnApiResponse($formResult, $status);
    }
}
