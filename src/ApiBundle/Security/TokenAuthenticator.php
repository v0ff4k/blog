<?php

namespace ApiBundle\Security;

use AppBundle\Entity\User;
use AppBundle\Helper\UserHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * Class TokenAuthenticator TokenSmoken
 *
 *  API clients will send an x-api-key header(token in request or in cookie) on each request with their API token.
 * This code will seek the associated user (if any)
 * @package AppBundle\Security
 * @see https://symfony.com/doc/3.4/_images/security/authentication-guard-methods.svg
 * @example https://symfony.com/doc/3.4/security/guard_authentication.html
 */
class TokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return bool
     * @throws \Exception
     */
    public function supports(Request $request) //~1
    {

        if ((
            $request->headers->has('x-api-key') or
            !empty($request->get('token', '')) or
            $request->cookies->has('token')
            ) and (
                empty(UserHelper::getCurUser() and
                !UserHelper::getCurUser() instanceof User)
            )
        ) {
            $trigger = true;
        } else {
            $trigger = false;
        }

        return $trigger;
    }

    /**
     * Called on every request. Return to $this->getUser() as 1st argument.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return UserInterface|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCredentials(Request $request) //~2
    {
        UserHelper::getLogg()->info(
            'T0k3nAuthenicator trig getCredentials with ' .
            ' Request: ' . json_encode($request->getUri() .
                ' query: ' . $request->getQueryString())
        );

        $oApiService = UserHelper::getContainer()->get('api.service.security');
        $user = $oApiService->getUserFromRequest($request);

        return $user;
    }

    /***
     * Get user by  cred-s and userProvider
     *
     * @param mixed $credentials
     * @param \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider
     * @return null|\Symfony\Component\Security\Core\User\UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider) //~3
    {

        UserHelper::getLogg()->info('T0k3nAuthenicator trig getUser()');

        if (empty($credentials) or !$credentials  instanceof User) {
            return null;
        }

        // if a User object return it!
        return $credentials;
    }

    /**
     * @param mixed $credentials
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user) //~4
    {
        UserHelper::getLogg()
            ->info('T0k3nAuthenicator trig checkCred-s() with ' .
            ' cred-s:' . json_encode($credentials) .
            ' and user: ' . json_encode($user))
        ;

        return true;
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param string $providerKey
     * @return null|\Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        UserHelper::getLogg()->info(
            'T0k3nAuthenicator trig onAuth-nSuccess() with ' .
            ' Request: ' . json_encode($request->getUri() . $request->getQueryString()) .
            ' token: ' . json_encode($token) .
            ' providerKey' . json_encode($providerKey)
        );

        //possible clone from \AppBundle\EventListener\AuthenticationListener::onAuthenticationSuccess($event)
        //  var User $user = $event->getAuthenticationToken()->getUser();

        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => UserHelper::getTrans()->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        UserHelper::getLogg()->info('T0k3nAuthenicator trig onAuth-nFailure with' .
            ' Request: ' . json_encode($request->getUri() . $request->getQueryString()) .
            ' and Auth-nException');

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\Exception\AuthenticationException|null $authException
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {

        UserHelper::getLogg()->info(
            'T0k3nAuthenicator trig start() with' .
            ' Request: ' . json_encode($request->getUri() . '; queryString:'. $request->getQueryString())
        );


        $data = [
            'message' => UserHelper::getTrans()->trans('help.auth_required')
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     *
     *
     * @return bool
     */
    public function supportsRememberMe()
    {
        UserHelper::getLogg()->info('T0k3nAuthenicator trig supp-sRememberMe() returning false');

        return false;
    }
}