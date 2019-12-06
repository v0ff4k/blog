<?php

namespace AppBundle\Helper;

use AppBundle\AppBundle;
use AppBundle\Entity\Post;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class UserHelper
{

    private static $instance = null;
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface $containerInterface */
    private static $containerInterface;
    /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
    private static $oDoctrine;
    /** @var \Monolog\Logger $oLogg */
    private static $oLogg;
    /** @var \Symfony\Component\Translation\IdentityTranslator $oTrans */
    private static $oTrans;
    /** @var \Symfony\Component\HttpKernel\KernelInterface $oKernel */
    private static $oKernel;
    /** @var \Symfony\Component\Routing\Router $oRouter */
    private static $oRouter;
    /** @var \Symfony\Component\Security\Csrf\CsrfTokenManager */
    private static $oCsrfTokenManager;
    /** @var \AppBundle\Entity\User $user */
    protected $user = null;
    /** @var string $token */
    protected $token;

    /**
     * UserHelper constructor.
     */
    public function __construct()
    {
        $this->token = (empty($_COOKIE['token'])) ? $this->generateToken() : $_COOKIE['token'];
    }

    /**
     * Just gen random string for using userTokens
     *
     * @return string
     */
    public function generateToken()
    {
        $token = md5(date('Y-m-d H:i:s u').rand(9, 999));
        return $token;
    }

    /**
     * Current static class, mostly for call "uHelper.function" from twig
     * @return UserHelper
     */
    public static function that()
    {
        return new self;
    }

    /** @return UserHelper  $instance - for DI calls */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new UserHelper();
        }

        return self::$instance;
    }

    /**
     * Short translator calls
     *
     * @return \Symfony\Component\Translation\IdentityTranslator
     */
    public static function getTrans()
    {
        if (!self::$oTrans) {
            if (!self::getContainer()->has('translator')) {
                throw new \LogicException('The Translator is not found');
            }

            try {
                self::$oTrans = self::getContainer()->get('translator');
            } catch (\Exception $e) {
                self::getLogg()->error('trans Error:'.$e->getMessage());
            }
        }

        return self::$oTrans;
    }

    /**
     * Get DI container for extra usage.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface|\Symfony\Component\DependencyInjection\Container
     */
    public static function getContainer()
    {
        if (!static::$containerInterface) {
            static::$containerInterface = AppBundle::getContainer();
        }

        return static::$containerInterface;
    }

    /**
     * @return \Monolog\Logger
     */
    public static function getLogg()
    {
        if (!self::$oLogg) {
            if (!self::getContainer()->has('logger')) {
                throw new \LogicException('The Monolog/Logger is not found');
            }

            try {
                self::$oLogg = self::getContainer()->get('logger');
            } catch (\Exception $e) {
                //...
            }
        }

        return self::$oLogg;
    }

    /**
     * @return User|mixed|null
     * @throws \Exception
     */
    public static function getCurUser()
    {
        if (!self::getContainer()->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        /** @var \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $secureToken */
        if (null === $secureToken = self::getContainer()->get('security.token_storage')->getToken()) {
            return null;
        }

        if (!is_object($user = $secureToken->getUser())) {
            return null;
        }

        return $user;
    }

    /**
     * @param null $tokenid
     * @return string
     * @throws \Exception
     */
    public static function getValueCsrfTokenManager($tokenid = null)
    {
        /** @see \Symfony\Component\Security\Csrf\CsrfToken -> getValue() */
        return self::getCsrfTokenManager()->getToken($tokenid)->getValue();
    }

    /**
     * @return \Symfony\Component\Security\Csrf\CsrfTokenManager
     * @throws \Exception
     */
    public static function getCsrfTokenManager()
    {
        if (!self::$oCsrfTokenManager) {
            if (!self::getContainer()->has('security.csrf.token_manager')) {
                throw new \LogicException('CsrfTokenManager not found');
            }
            self::$oCsrfTokenManager = self::getContainer()->get('security.csrf.token_manager');
        }

        return self::$oCsrfTokenManager;
    }

    /**
     * Sanitize value, string or text.
     *
     * @param $string
     * @param bool|string $removeAllHtml
     *          true-remove all tags within,
     *          'safe'-only spc chars, like htmlentities()
     *          and just strip_tags() others, that more safely,
     * @return string
     */
    public static function sanitizeVal($string, $removeAllHtml = false)
    {

        $string = preg_replace('#(on.*?|style)(\s*)="[^"]+"#', '', $string);

        if (true === $removeAllHtml) {
            $string = filter_var($string, FILTER_SANITIZE_STRING);
        } elseif ('safe' == $removeAllHtml) {
            $string = filter_var($string, FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $string = strip_tags($string, '<p><a><br><hr><img><b><u><i><div><span><tt><font><table><th><tr><td>');
        }

        return $string;
    }

    /**
     * Return number per page, worked for comments, post, e.t.c.
     * @return int
     */
    public static function getNumPerPage()
    {
        return self::getContainer()->hasParameter('num_per_page')
            ? (integer)self::getContainer()->getParameter('num_per_page')
            : 10;
    }

    /**
     * Check input parameter if a string|number|integer
     *      and try to convert into valid integer [1..9999]
     * @param mixed $page
     * @return int
     */
    public static function checkPage($page)
    {
        return (!is_numeric((int)$page) or 1 > (int)$page) ? 1 : (int)$page;
    }

    /**
     * GetDbName - getter parameter  database_name
     *
     * unused in non direct calls
     * @return string|bool|mixed - string on success, false on failure
     */
    public static function getDbName()
    {
        return self::getContainer()->hasParameter('database_name')
            ? (integer)self::getContainer()->getParameter('database_name')
            : false;
    }

    /**
     * hideEmail - vasya_pupkin@some.mail.com  >> vasya.***@....com
     *
     * @param string $string
     * @return string
     */
    public static function hideEmail($string)
    {
        if (strpos($string, '@') !== false) {
            // name@serv.com >> na**@...com
            $e = explode("@", $string);
            $name = implode(array_slice($e, 0, count($e) - 1), '@');
            $length = floor(strlen($name) / 4);
            $stars = str_repeat('*', 3);
            $dots = str_repeat('.', 3);
            $domain = explode('.', end($e));
            $string = substr($name, 0, $length) . $stars . "@" . $dots . end($domain);
        }

        return $string;
    }

    /**
     * Get Token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set Token, a string marker for user(registered and anonymous), +update user db + send fresh cookie
     *
     * @param string $token
     * @return UserHelper
     * @throws \Exception
     */
    public function setToken($token)
    {
        $this->token = $token;
        self::getLogg()->info('uHelper sets secure Token string: '.$token);

        //if user set and new token is new, so update!
        if ((!empty($this->user) && $this->user instanceof User) && $token != $this->user->getToken()) {
            self::getLogg()->info(
                'UHelper UPD secure Token string '.
                ' uHelper new token: '.$this->token.
                ' old token in $user: '.$this->user->getToken().
                ' for userId: '.$this->user->getId().
                ' Email: '.$this->user->getEmail()
            );

            $this->user = $this->user->setToken($token);
            $this->persistAndFlush($this->user);
            self::getLogg()->info('UHelper UPD secure Token after UPD: '.$this->user->getToken());
        }

        $serverName = ServerHelper::getServHost();
        $secure = (self::isDev()) ? false : true;//secure+httponly cookie ONLY for PROD env.!
        setcookie('token', $token, time() + 1209600, '/', $serverName, $secure, $secure);
        self::getLogg()->info(
            'setting new cookie: token: '.$token.
            'host: '.$serverName.' issecure: '.(string)$secure
        );

        return $this;
    }

    /**
     * getDoctine, defined here to choose what will be in use
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Registry|object
     * @throws \Exception
     */
    public static function getDoctrine()
    {
        if (!self::$oDoctrine) {
            if (!self::getContainer()->has('doctrine')) {
                throw new \LogicException('The DoctrineBundle is not registered');
            }
            self::$oDoctrine = self::getContainer()->get('doctrine');
        }

        return self::$oDoctrine;
    }

    /**
     * Safe persist and flush big data at once to db.
     *
     * @param $obj
     * @param bool $em
     * @throws \Exception
     */
    public function persistAndFlush($obj, $em = false)
    {
        // New entity persistence
        $em = (false === $em) ? self::getDoctrine()->getManager() : $em;
        $em->getConnection()->beginTransaction();

        // Try and make the transaction
        try {
            $em->persist($obj);
            $em->flush();

            // Try and commit the transaction
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            // Rollback the failed transaction attempt
            $em->getConnection()->rollback();
            self::getLogg()->error('Bad transaction, rollback !' . json_encode($e));
            throw $e;
        }
    }

    /**
     * is we in dev environment?
     *
     * @param boolean $showIsDebug - default is false(if true, checks if debug mode is enabled)
     * @return bool
     * @throws \Exception
     */
    public static function isDev($showIsDebug = false)
    {
        if ($showIsDebug) {
            return static::getKernel()->isDebug();
        } else {
            return in_array(static::getKernel()->getEnvironment(), ['test', 'dev']);
        }
    }

    /**
     * getKernel for some quiring.
     *
     * @return KernelInterface|\Symfony\Component\HttpKernel\Kernel
     *
     */
    public static function getKernel()
    {
        if (!self::$oKernel) {
            if (!self::getContainer()->has('kernel')) {
                throw new \LogicException('KernelInterface not found');
            }
            try {
                self::$oKernel = self::getContainer()->get('kernel');
            } catch (\Exception $e) {
                self::getLogg()->error('geting kernel causing error: ' . $e->getMessage());
            }
        }

        return self::$oKernel;
    }

    /**
     * getRouter, defined here to choose what will be in use
     *
     * @return object|\Symfony\Bundle\FrameworkBundle\Routing\Router|\Symfony\Component\Routing\Router
     * @throws \Exception
     */
    public static function getRouter()
    {
        if (!self::$oRouter) {
            if (!self::getContainer()->has('router')) {
                throw new \LogicException('The RouterBundle is not registered');
            }
            self::$oRouter = self::getContainer()->get('router');
        }

        return self::$oRouter;
    }

    /**
     * Removes all non-alphanumeric characters except whitespaces.
     *
     * @param string $query
     * @return string
     */
    public static function sanitizeString($query)
    {
        $noSpace = trim(preg_replace('/[[:space:]]+/', ' ', $query));
        return preg_replace('/[^[:alnum:] ]/', '', $noSpace);
    }

    /**
     * Splits the search query into terms and removes the ones which are irrelevant(length less than 3symbols).
     *
     * @param string $searchQuery
     * @return array
     */
    public static function extractSearchTerms($searchQuery)
    {
        $terms = array_unique(explode(' ', mb_strtolower($searchQuery)));

        return array_filter($terms, function ($term) {
            return 2 <= mb_strlen($term);
        });
    }

    /**
     * Repace commas into dot for cac/compare
     * @param $var
     * @return mixed
     */
    public static function toDot($var)
    {
        return $var = str_replace(',', '.', $var);
    }

    /**
     * Convert  text from sneak case to camelcase
     *
     * @param $input
     * @param string $separator
     * @return mixed
     */
    public static function snakeToCamelCase($input, $separator = '_')
    {
        return str_replace($separator, '', lcfirst(ucwords($input, $separator)));
    }
    
    /**
     * completeAuthUserAfterRequest, founded by token. same in controller  UserHelper::setUser($this->getUser());
     * for future use in twig.
     *
     * @param User $user
     * @param Request $request
     * @return UserHelper
     * @throws \Exception
     */
    public function completeAuthUserAfterRequest(User $user, Request $request)
    {

        if (empty($user) or (!$user instanceof User)) {
            self::getLogg()->warning('Try to reg empty $user! need CHECK!');

            return $this;
        }

        self::getLogg()->info('Completing, current token: '.$user->getToken());

        //Log in user into the symfony system
        $newToken = new UsernamePasswordToken($user, $user->getPassword(), 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($newToken);

        //update session
        self::getContainer()->get('session')->set('_security_main', serialize($newToken));
        self::getLogg()->info(
            'Complete authenicate user '.
            ' serializing a new token(UsernamePasswordToken): '.serialize($newToken)
        );

        //event_dispatcher
        $event = new InteractiveLoginEvent($request, $newToken);
        self::getContainer()->get("event_dispatcher")->dispatch("security.interactive_login", $event);

        self::getLogg()->info('endCompleting, current token: '.$user->getToken());

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * SetUser
     *
     * @param User $user
     * @return UserHelper
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }
}
