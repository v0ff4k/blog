<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\UserType;
use AppBundle\Form\UserEditType;
use AppBundle\Helper\UserHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller used to manage the app security. for /login /logout  pages
 */
class SecurityController extends BaseController
{

    /**
     * Login action
     *
     * @param Request $request
     * @param AuthenticationUtils $helper
     * @return Response
     * @throws \Exception
     */
    public function loginAction(Request $request, AuthenticationUtils $helper)
    {
        if (UserHelper::getCurUser()) {
            return (!empty($_SERVER['HTTP_REFERER']))
                ? new RedirectResponse($_SERVER['HTTP_REFERER'])
                : $this->redirectToRoute('homepage');
        }

        $csrfToken = UserHelper::getValueCsrfTokenManager('authenticate');

        return $this->render('AppBundle:security:login.html.twig', [
            'last_username' => $helper->getLastUsername(),
            'error' => $helper->getLastAuthenticationError(),
            'csrf_token' => $csrfToken,
            'token' => UserHelper::getInstance()->getToken(),
        ]);
    }

    /**
     * Pseudo logout. never be reached:
     *
     * @see security.firewalls.secured_area.logout
     * @return Response
     */
    public function logoutAction()
    {
        $this->addTransFlash('success', 'menu.logout_successful');
        return $this->redirectToRoute('homepage');
    }

    /**
     * Outdated edit user(author edit info)
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addTransFlash('success', 'user.updated_successfully');

            return $this->redirectToRoute('security_edit');
        }

        return $this->render('AppBundle:security:edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * User registration
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function registerAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            UserHelper::getInstance()->persistAndFlush($user);
            $this->addTransFlash('success', 'user.created_successfully');

            return $this->redirectToRoute('security_login');
        }

        return $this->render(
            'AppBundle:security:register.html.twig',
            ['form' => $form->createView()]
        );
    }

    /**
     * Twig error page, can be reached site.com/e/(404|403|500|'')   etc
     *
     * @param $code
     * @return mixed
     */
    public function errorAction($code)
    {
        $code = ($code != '500' || $code != 404 || $code != 403 ? '' : $code);
        return $this->render(
            'Exception/error' . $code . '.html.twig',
            ['status_code' => $code, 'status_text' => 'status text' ]
        );
        // original
//        return $this->render(
//            'TwigBundle:Exception:error'.$code.'.html.twig',
//            ['status_code' => $code, 'status_text' => 'status text']
//        );
    }
}
