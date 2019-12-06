<?php

namespace AppBundle\Repository;

use AppBundle\Helper\UserHelper;
use Pagerfanta\Pagerfanta;

class CommentRepository extends BaseRepository
{

    /**
     * Find posts older than specific dateTime, if not set - getting older that now.
     *
     * @param integer $postId
     * @param integer $page
     * @param null|\DateTime find  older  than  specific DateTime
     * @return Pagerfanta
     */
    public function findLatestComments($postId, $page = 1, $onlyActive = true, $moment = null)
    {
        if (!($moment instanceof \DateTime)) {
            $moment = new \DateTime('now');
        }

        $page = UserHelper::checkPage($page);
        $numPerPage = UserHelper::getNumPerPage();

        $query = $this
            ->createQueryBuilder('c')
            ->select('c, a')
            ->leftJoin('c.author', 'a')
            ->orderBy('c.createdAt', 'DESC')
            ->andWhere('c.post = :postId')->setParameter('postId', $postId)
            ->andWhere('c.createdAt < :moment')->setParameter('moment', $moment)
        ;

        if ($onlyActive) {
            $query->andWhere('c.isActive = 1');
        }

        $result = $query->getQuery();
        $result
            ->useQueryCache(true)
            ->useResultCache(true, self::DAY_LIFETIME);

        return $this->pagerfantaPaginate($result, $page, $numPerPage);
    }
}
