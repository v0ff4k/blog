<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\User;
use AppBundle\Helper\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class AuthenticationListener
 *
 * @package AppBundle\EventListener
 * @see     https://symfony.com/doc/3.4/_images/security/authentication-guard-methods.svg
 */
class AuthenticationListener implements EventSubscriberInterface
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var AuthorizationChecker $authorizationChecker */
    private $authorizationChecker;

    /** @var TokenStorageInterface $tokenStorage */
    private $tokenStorage;

    /** @var null|\Symfony\Component\HttpFoundation\Request */
    private $request;

    public function __construct(
        EntityManagerInterface $em,
        AuthorizationChecker $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        RequestStack $request
    ) {
        $this->em = $em;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->request = $request->getCurrentRequest();
    }

    public static function getSubscribedEvents()
    {
        return array(
            AuthenticationEvents::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        );
    }

    /**
     * onAuthSuccess - update tokens, in db or on cookie, whatever  wrong or null or outdated
     *
     * @param    InteractiveLoginEvent|AuthenticationEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function onAuthenticationSuccess($event)
    {

        UserHelper::getLogg()->info('onAuthenticationSuccess() start');
        $oApiService = UserHelper::getContainer()->get('api.service.security');
        /** @var User| $user */
        $user = $oApiService->getUserFromRequest($this->request);

        //if not authenticated(anon.)
        if (empty($user) or !$user instanceof User) {
            return;
        }

        //is user is not active
        if (empty($user->getIsActive())) {
            UserHelper::getContainer()->get('session')->getFlashBag()
                ->add('error', 'You are not activated!');
            return;
        }

        //if user not in system, completing register $user with updating token.
        if ((
                empty(UserHelper::getCurUser()) or
                !UserHelper::getCurUser() instanceof User
            ) or
            UserHelper::getCurUser() instanceof User and
            UserHelper::getCurUser()->getToken() != $user->getToken()
        ) {
            //user not authenticated in security system, so authenticate user
            UserHelper::getInstance()->completeAuthUserAfterRequest($user, $this->request);
            UserHelper::getLogg()->info('completing reg in system current user: '.$user->getId().$user->getName());
        }

        //user without token @todo think about outdated token in stateless paradigm
        if (empty($user->getToken())) {
            $newToken = UserHelper::getInstance()->generateToken();
            UserHelper::getLogg()->info(
                'onAuth-nSuccess()  user without token, '.
                ' newToken:'.json_encode($newToken).
                ' userToken:'.json_encode($user->getToken())
            );

            //update records with actual User+token
            UserHelper::getInstance()->setUser($user);
            UserHelper::getInstance()->setToken($newToken);

            UserHelper::getLogg()->info(
                'onAuth.-nSuc.-s()  user has owned token, all ok!, '.
                ' user:'.json_encode($user)
            );
        }
    }
}
