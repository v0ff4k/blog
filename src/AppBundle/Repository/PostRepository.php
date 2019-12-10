<?php

namespace AppBundle\Repository;

use AppBundle\Helper\UserHelper;
use AppBundle\Helper\PostHelper;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class PostRepository extends BaseRepository
{

    /**
     * Find posts older than specific dateTime, if not set - getting older that now.
     *
     * @param int $page
     * @param null|\DateTime find  older  than  specific DateTime
     * @return Pagerfanta
     */
    public function findLatestPosts($page = 1, $onlyActive = true, $moment = null, $withComments = false)
    {
        if (!($moment instanceof \DateTime)) {
            $moment = new \DateTime('now');
        }

        $page = UserHelper::checkPage($page);
        $numPerPage = UserHelper::getNumPerPage();

        $query = $this->createQueryBuilder('p');
        //IN REAL PROJECT, USE ONLY NEEDED FIELDS + DONT ISE PAGERFANTA, ONLY HANDS!
        $query->select('p, a')
        ;

        if ($withComments) {
            $query->addSelect('c');
        }

        $query->leftJoin('p.author', 'a');

        if ($withComments) {
            $query
                ->leftJoin(
                    'p.comments',
                    'c',
                    Expr\Join::WITH,
                    'c.isActive <> 0'
                );
        }

        $query
            ->orderBy('p.createdAt', 'DESC')
            ->andWhere('p.createdAt < :moment')->setParameter('moment', $moment);

        if ($withComments) {
            $query->groupBy('p.id');
        }

        if ($onlyActive) {
            $query->andWhere('p.isActive = 1');
        }

        $result = $query->getQuery();
        $result->useQueryCache(true)->useResultCache(true, self::DAY_LIFETIME);
        return $this->pagerfantaPaginate($result, $page, $numPerPage);
    }

    /**
     * get number of Comments for specific posts
     *
     * @todo refac/optimize
     * @param mixed|Pagerfanta|array $posts can be Pagerfanta object or array with id's
     * @return array  ['postId' => 'commentCount', 'postId2' => '#ofComments']
     */
    public function getCommentsForPosts($posts)
    {

        $postWithCommentsArray = [];// [postId => commentCounts]
        if ($posts instanceof Pagerfanta) {
            $postsId = [];

            foreach ($posts as $post) {
                $postsId[] = (method_exists($post, 'getId') ? $post->getId() : $post['id']);
            }
            $posts = $postsId;
        }
        //@todo unwork with mysql  v5.7 and newer !
        if (is_array($posts) && !empty($posts)) {
            $query = $this->createQueryBuilder('p');
            $query
                ->select('p.id AS id', 'COUNT(c.id) AS comments')
                ->leftJoin(
                    'p.comments',
                    'c',
                    Expr\Join::WITH,
                    'c.isActive <> 0'
                )
                ->where('p.id IN (:ids)')
                ->setParameter('ids', $posts, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
                ->groupBy('p.id')
            ;

            $result = $query->getQuery();
            $result->useQueryCache(true)->useResultCache(true, self::DAY_LIFETIME);
            $array = $result->getArrayResult();
            foreach ($array as $i) {//todo WAT?
                $key = $i['id'];
                $val = $i['comments'];
                $postWithCommentsArray[$key] = $val;
            }
        }

        return $postWithCommentsArray;// ['postId' => 'commentCount'];
    }


    /**
     * Find post by specific id+slug and cache it.
     *
     * @param $id
     * @param $slug
     * @return object|array|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findPostByIdAndSlug($id, $slug)
    {

        if ($id != 0 && !empty($slug)) {
            //by id+slug
            $predicates = 'p.id = :id AND p.slug LIKE :slug';
            $params = ['id' => $id ,'slug' => '%' . $slug . '%'];
        } elseif (!empty($slug) && $id === 0) {
            //by slug
            $predicates = 'p.slug LIKE :slug';
            $params = ['slug' => '%' . $slug . '%'];
        } elseif ($id != 0 and empty($slug)) {
            //by id
            $predicates = 'p.id = :id';
            $params = ['id' => $id];
        } else {
            // hang up
            return null;
        }

        $query = $this->createQueryBuilder('p');
        $subQuery = $query->expr()->andX($query->expr()->neq('c.isActive', 0));
        $query
            ->select('p, a, c')
            ->where($predicates)->setParameters($params)
            ->leftJoin('p.author', 'a')
            ->leftJoin('p.comments', 'c', Expr\Join::WITH, $subQuery)
        ;

        $result = $query->getQuery();
        $result->useQueryCache(true)->useResultCache(true, self::DAY_LIFETIME);

        try {
            $items = $result->getSingleResult();
        } catch (NoResultException $e) {
            $items = [];
        } catch (NonUniqueResultException $e) {
            $items = isset($result->getResult()[0])
                    ? $result->getResult()[0]
                    : $result->getResult();
        }

        return $items;
    }

    /**
     * @param string $rawQuery The search query as input by the user
     * @param int    $limit    The maximum number of results returned
     *
     * @return array
     */
    public function findBySearchQuery($rawQuery, $limit = null)
    {

        $limit = (!empty($limit) && is_integer((int)$limit))// @see UserHelper::checkPage($limit)
            ? $limit
            : UserHelper::getContainer()->getParameter('num_per_page');

        $query = PostHelper::sanitizeString($rawQuery);
        $searchTerms = PostHelper::extractSearchTerms($query);

        if (0 === count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('p');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
                ->orWhere('p.title LIKE :t_'.$key)
                ->orWhere('p.content LIKE :t_'.$key)
                ->setParameter('t_'.$key, '%'.$term.'%')
            ;
        }
        $queryBuilder
            ->andWhere('p.isActive = 1')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ;

        $result = $queryBuilder->getQuery();
        $result->useQueryCache(true)->useResultCache(true, self::DAY_LIFETIME);

        return $result->getResult();
    }

}
