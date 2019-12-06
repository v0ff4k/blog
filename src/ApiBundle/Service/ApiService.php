<?php

namespace ApiBundle\Service;

use ApiBundle\Helper\ItemCollection;
use ApiBundle\Security\ApiFormValidator;
use AppBundle\Entity\User;
use AppBundle\Helper\UserHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Csrf\CsrfToken;

class ApiService
{

    /**
     * Search user and token(string)  in "Request": TokenStorage, x-api-key or cookie
     * for future auth by 1 token(string) in cookie or header.
     *
     * @todo replace it with polymorphism  if-else case-break >>> abstract class getUserFromRequest($req)
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array [$user, $token]
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function getUserFromRequest(Request $request)
    {

        $user = null;
        $token = '';

        /** @var \AppBundle\Entity\User $user */
        if (($user = UserHelper::getCurUser()) instanceof User) {
            ////search in TokenInterface as ALREADY logged "User" object
            //$user = UserHelper::getCurUser();
            UserHelper::getLogg()->info('oApiService() normal auth user: getAuthenticationToken()->getUser()');
        }

        if ($request->get('token', false) && (!$user instanceof User or empty($user))) {
            ///search by POST/GET with user's token
            $token = $request->get('token');
            $user = UserHelper::getDoctrine()->getRepository(User::class)->getUserByToken($token);
            UserHelper::getLogg()->info('oApiService() user by request token: '.$token);
        }

        if ($request->headers->has('x-api-key') && (!$user instanceof User or empty($user))) {
            ///search by header: x-api-key
            $token = $request->headers->get('x-api-key');
            $user = UserHelper::getDoctrine()->getRepository(User::class)->getUserByToken($token);
            UserHelper::getLogg()->info('oApiService() user by x-api-key: '.$token);
        }

        if ($request->cookies->has('token') && (!$user instanceof User or empty($user))) {
            ///search by cookie with user's token
            $token = $request->cookies->get('token');
            $user = UserHelper::getDoctrine()->getRepository(User::class)->getUserByToken($token);
            UserHelper::getLogg()->info('oApiService() user by cookie token: '.$token);
        }

        if (!$user instanceof User or empty($user)) {
            //user not found, generate new token
            $token = UserHelper::getInstance()->generateToken();
            $user = UserHelper::getDoctrine()->getRepository(User::class)->getUserByToken($token);
            UserHelper::getLogg()->info(
                'oApiService() generate user from cookie / UHtoken / generate new, '.
                ' Token string: '.$token
            );
        }

        return $user;
    }



    /**
     * Gets '_csrf_token' from Request and comparing it with  $tokenId(string)
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $tokenId
     * @throws \Exception
     * @return bool
     */
    public function isValidPostedCsrfToken(Request $request, $tokenId)
    {
        $csrfPostedToken = $request->get('_csrf_token', '');

        if (empty($csrfPostedToken)) {
            return false;
        }
        $result = UserHelper::getCsrfTokenManager()->isTokenValid(new CsrfToken($tokenId, $csrfPostedToken));
        UserHelper::getLogg()->info(
            'check posted csrf: ' . $csrfPostedToken .
            ' with tokenID: ' . $tokenId .
            ' result - they are: ' . ($result ? 'same' : 'diff' )
        );
        return (true === $result ? true : false);
    }

    /**
     * Form processing service for  useredit/registration via (POST|PUT) Method  (login in future, need ref.+)
     *
     * @param $form
     * @param $tokenId
     * @param $object
     * @param Request $request
     * @param bool $withCsrf
     * @param bool $skipEmptyVal
     * @return array Return array of [form, Response::HTTP_***]
     * @throws \Exception
     */
    public function formProcess($form, $tokenId, $object, Request $request, $withCsrf = true, $skipEmptyVal = false)
    {
        //todo $form can be object(with multiLevelData), in future denormalize
        $defaultResponseStatus = Response::HTTP_OK;

        if (true === $withCsrf) {
            $csrfToken = UserHelper::getValueCsrfTokenManager($tokenId);
            $form = array_merge($form, ['_csrf_token' => $csrfToken]);
        }

        $statusMessage = $this->translate("help_text.{$tokenId}.fields");
        $status = ['status' => $tokenId . ", " . $statusMessage];
        $form = array_merge($status, $form);
        $form = [ $tokenId => $form ];

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            //POST or PUT

            if (true === $withCsrf && !$this->isValidPostedCsrfToken($request, $tokenId)) {
                $message = $this->translate('Invalid CSRF token.', [], 'security');
                $form[$tokenId]['status'] = 'check_csrf_token, ' . $message;
                $form[$tokenId]['_csrf_token'] = $csrfToken;

                return [$form, Response::HTTP_BAD_REQUEST];
            }

            $updateDb = false;
//            // PROCESS start- (validator)
//            foreach ($form[$tokenId] as $i => $v) {
//                if ('_' == substr($i, 0, 1) && $i != '_csrf_token') {
//                    $method = 'set' . ucfirst(substr($i, 1));// ex:  setName
//                    $val = $request->get($i, '');// ex: Vasiliy
//
//                    if (!empty($val) && method_exists($object, $method)) {
//                        //field name and value correct
//                        $form[$tokenId][$i] = (stristr('pass', $i) === false ? $val : '***' );
//                        if (true !== $validationResult = ApiFormValidator::isValid($i, $val)) {
//                            $statusMessage = $this->translate('problem.validation_error');
//                            $form[$tokenId]['status'] = 'validation_error, ' . $statusMessage;
//                            $form[$tokenId][$i] = $val . ' << ' . $validationResult;
//
//                            return [$form, Response::HTTP_BAD_REQUEST];
//                        }
//                        $object->{$method}($val);// ex: $user->setName('Vasiliy')
//                        (false === $updateDb) and ($updateDb = true);
//                        UserHelper::getLogg()->info(
//                            'prepare upd: ' .
//                            (method_exists($object, 'getId') && !empty($object->getId())
//                                ? ' object id: ' . $object->getId()
//                                : '') .
//                            ' setter: ' . $method . '('. $val . ')'
//                        );
//                    } elseif ((empty($val) && !$skipEmptyVal) or (!method_exists($object, $method))) {
//                        //field value empty(and not allowed it) or field name is incorrect
//                        $invalidMessage = $this->translate('problem.field_send_error');
//                        $invalidFieldMessage = $this->translate('problem.invalid_field_please_check');
//                        $form[$tokenId]['status'] = 'field_send_error, ' . $invalidMessage;
//                        $form[$tokenId][$i] = $val . ' << ' . $invalidFieldMessage;
//                        UserHelper::getLogg()->error(
//                            'error val: ' . $val .
//                            ' User method: ' . $method
//                        );
//                        return [$form, Response::HTTP_NOT_FOUND];
//                    }
//                }
//            }
//            //PROCESS END-

            list($updateDb, $form, $responseStatus) = $this->formValidate($form, $tokenId, $object, $request);

            if ('bad' == $updateDb) {
                return [$form, $responseStatus];
            }


            //process by object type
            if (true === $updateDb) {
                UserHelper::getLogg()->info(
                    'update via: ' . $request->getUri() .
                    ' object: ' . json_encode($object)
                );

                UserHelper::getInstance()->persistAndFlush($object);
                $form[$tokenId]['status'] = 'success, ' . $this->translate("help_text.{$tokenId}.successful");
            }

            $params = [];
            if ($object instanceof User) {
                UserHelper::getInstance()->completeAuthUserAfterRequest($object, $request);
                $params = ['%name%' => (!empty($object->getName()) ? $object->getName() : 'user')];
            }

            $message = $this->translate("help_text.{$tokenId}.successful", $params);

            //clear all $form with status only+token
            $form = [$tokenId => ['status' => $tokenId . '-ok, ' . $message]];

            if (method_exists($object, 'getToken')) {
                $form[$tokenId]['token'] = $object->getToken();
            }

            return [$form, $defaultResponseStatus];
        }

        return [$form, $defaultResponseStatus];
    }

    /**
     * Validate form for specific token
     *
     * @param $form - form array
     * @param $tokenId  - unique token(or just string) id in form
     * @param $object - object that members will be validated.
     * @param \Symfony\Component\HttpFoundation\Request $request - can be used inside function
     * @param bool $skipEmptyVal
     * @return array ['(bad|true|false)', $form, Response::HTTP_***]
     * @throws \Exception
     */
    public function formValidate($form, $tokenId, $object, Request $request, $skipEmptyVal = false)
    {
        $updateDb = false;
        // PROCESS start- (validator)
        foreach ($form[$tokenId] as $i => $v) {
            if ('_' == substr($i, 0, 1) && $i != '_csrf_token') {
                $method = 'set' . ucfirst(substr($i, 1));// ex:  setName
                $val = $request->get($i, '');// ex: Vasiliy

                if (!empty($val) && method_exists($object, $method)) {
                    //field name and value correction for display
                    $form[$tokenId][$i] = (stristr('pass', $i) === false ? $val : '***' );

                    if (true !== $validationResult = ApiFormValidator::isValid($i, $val)) {
                        $statusMessage = $this->translate('problem.validation_error');
                        $form[$tokenId]['status'] = 'validation_error, ' . $statusMessage;
                        $form[$tokenId][$i] = $val . ' << ' . $validationResult;

                        return ['bad', $form, Response::HTTP_BAD_REQUEST];
                    }
                    $object->{$method}($val);// ex: $user->setName('Vasiliy')
                    (false === $updateDb) and ($updateDb = true);
                    UserHelper::getLogg()->info(
                        'prepare upd: ' .
                        (method_exists($object, 'getId') && !empty($object->getId())
                            ? ' object id: ' . $object->getId()
                            : '') .
                        ' setter: ' . $method . '('. $val . ')'
                    );
                } elseif ((empty($val) && !$skipEmptyVal) or (!method_exists($object, $method))) {
                    //field value empty(and not allowed it) or field name is incorrect
                    $invalidMessage = $this->translate('problem.field_send_error');
                    $invalidFieldMessage = $this->translate('problem.invalid_field_please_check');
                    $form[$tokenId]['status'] = 'field_send_error, ' . $invalidMessage;
                    $form[$tokenId][$i] = $val . ' << ' . $invalidFieldMessage;
                    UserHelper::getLogg()->error(
                        'error val: ' . $val .
                        ' User method: ' . $method
                    );
                    return ['bad', $form, Response::HTTP_NOT_FOUND];
                }
            }
        }
        //PROCESS END-

        return [$updateDb, $form, 'ok'];
    }



    /**
     * log out user. also stateless REST API can be without it, client just forget about TOKEN string
     *
     * @return void
     * @throws \Exception
     */
    public function logoutSuccessful()
    {
        //clear  token/session
        $token = new AnonymousToken(null, new User());
        UserHelper::getContainer()->get('security.token_storage')->setToken($token);
        UserHelper::getContainer()->get('session')->invalidate();

        //clear token string for user
        UserHelper::getInstance()->setUser(new User())->setToken('');

        return;
    }

    /**
     * Make translation, lightweight service method for api.
     *
     * @param string $message
     * @param array $params
     * @param string $domain
     * @return string
     */
    public function translate($message, $params = [], $domain = 'api')
    {
        return UserHelper::getTrans()->trans($message, $params, $domain);
    }

    /**
     * Own Pagerfanta collection.
     *
     * @param \Pagerfanta\Pagerfanta $pagerfantaObject
     * @param $route
     * @param array $routeParams
     * @param integer $page
     * @return \ApiBundle\Helper\ItemCollection
     */
    public static function getPagerfantaCollection($pagerfantaObject, $route, $routeParams = [], $page = 1)
    {
        $pageCollections = new ItemCollection(
            iterator_to_array($pagerfantaObject->getIterator()),
            $pagerfantaObject->getNbResults()
        );

        $linkUrl = function ($target) use ($route, $routeParams) {
            $routeTargetParams = array_merge($routeParams, ['page' => $target]);
            return UserHelper::getRouter()->generate($route, $routeTargetParams);
        };

        $page = ($page > $pagerfantaObject->getNbPages()
            ? $pagerfantaObject->getNbPages()
            : ((1 > (int)$page) ? 1 : (int)$page));

        // hasPreviousPage() + getPreviousPage() works bugly without upd currPage!
        // hasNextPage() + getNextPage() works bugly without upd currPage!
        $pagerfantaObject->setCurrentPage($page);
        $pageCollections->addLink('first_page', $linkUrl(1));
        $pageCollections->addLink('current_page', $linkUrl($page));
        $pageCollections->addLink('last_page', $linkUrl($pagerfantaObject->getNbPages()));

        // generate: <<--perv \\
        if ($pagerfantaObject->hasPreviousPage()) {
            $pageCollections->addLink('prev_page', $linkUrl($pagerfantaObject->getPreviousPage()));
        }
        // generate: next-->> \\
        if ($pagerfantaObject->hasNextPage()) {
            $pageCollections->addLink('next_page', $linkUrl($pagerfantaObject->getNextPage()));
        }

        return $pageCollections;
    }
}
