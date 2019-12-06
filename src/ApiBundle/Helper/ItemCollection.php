<?php

namespace ApiBundle\Helper;

/**
 * Class ItemCollection
 * micro helper to help serialize output in Pagerfanta. pop decision.
 *
 * @package ApiBundle\Helper
 */
class ItemCollection
{

    /** @var array $items */
    private $items;

    /** @var integer $totalItems */
    private $totalItems;

    /** @var int $count */
    private $count;

    /** @var array $links */
    private $links = [];

    public function __construct(array $items, $totalItems)
    {
        $this->items = $items;
        $this->totalItems = $totalItems;
        $this->count = count($items);
    }

    /**
     * Add link to paginate in Pagerfanta.
     *
     * @param $ref
     * @param $url
     */
    public function addLink($ref, $url)
    {
        $this->links[$ref] = $url;
    }

}
