<?php

namespace AppBundle\Repository;

use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Doctrine\ORM\NoResultException;

class UserRepository extends BaseRepository implements UserLoaderInterface
{

    /**
     * UserInterfase build in find user by username, extend it for able search both email/username
     *
     * @param string $usernameOrEmail
     * @return mixed|null|\Symfony\Component\Security\Core\User\UserInterface
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByUsername($usernameOrEmail)
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :query OR u.email = :query')
            ->setParameter('query', $usernameOrEmail)
            ->andWhere('u.isActive = 1')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Getter user by token
     *
     * @param string|array $token
     * @return array|object|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUserByToken($token)
    {
        $q = $this->createQueryBuilder('u')
            ->select('u')
            ;

        if (is_string($token)) {
            $q->andWhere("u.token = :token")->setParameter('token', $token);
        } elseif (is_array($token)) {
            foreach ($token as $t) {
                $q->orWhere("u.token = :token")->setParameter('token', $t);
            }
        } else {
            return null;
        }

        $r = $q->getQuery();

        $r->useQueryCache(true)
            ->useResultCache(true, 3600);

        try {
            $result = $r->getSingleResult();
        } catch (NoResultException $e) {
            $result = null;
        }

        return $result;
    }


    /**
     * Find user by login/email+password and update token
     *
     * @param $usernameOrEmail
     * @param $encodedPassword
     * @param $userToken
     * @return mixed|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function searchAndUpdateToken($usernameOrEmail, $encodedPassword, $userToken)
    {

        $q = $this->createQueryBuilder('u')
            ->update('u')
            ->set('u.token', $userToken)
            ->where('(u.username = :query OR u.email = :query ) AND u.password = :pass')
            ->setParameter('query', $usernameOrEmail)
            ->setParameter('pass', $encodedPassword)
        ;

        $r = $q->getQuery();

        $r->useQueryCache(true)
            ->useResultCache(true, 3600);

        try {
            $result = $r->getSingleResult();
        } catch (NoResultException $e) {
            $result = null;
        }

        return $result;
    }
}
