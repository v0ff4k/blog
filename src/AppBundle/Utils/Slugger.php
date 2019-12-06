<?php

namespace AppBundle\Utils;

/**
 * Auto generated in doctrine! This is outdated ver
 * @deprecated
 * @TODO remove.
 */
class Slugger
{
    /**
     * @param string $string
     *
     * @return string
     */
    public function slugify($string)
    {
        return preg_replace('/\s+/', '-', mb_strtolower(trim(strip_tags($string)), 'UTF-8'));
    }
}
