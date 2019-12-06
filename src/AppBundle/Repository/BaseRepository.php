<?php

namespace AppBundle\Repository;

use AppBundle\Helper\UserHelper;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class BaseRepository extends EntityRepository
{

    const HOUR_LIFETIME = 3600; // 60sec * 60min
    const DAY_LIFETIME = 86400; // 60sec * 60min * 24hout

    /**
     * Paginate object, standard Query. did not use Lib in big broject!!!
     *
     * @param \Doctrine\ORM\Query $query
     * @param int $currentPage - current requested page
     * @param int $maxPerPage - items per page display
     * @return \Pagerfanta\Pagerfanta
     */
    protected function pagerfantaPaginate(Query $query, $currentPage, $maxPerPage = 10)
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $currentPage = UserHelper::checkPage($currentPage);
        $nbPages = $paginator->getNbPages();
        $currentPageReal = ($currentPage > $nbPages ? $nbPages : $currentPage);

        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($currentPageReal);

        return $paginator;
    }
}
