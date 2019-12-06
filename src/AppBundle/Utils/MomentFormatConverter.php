<?php

namespace AppBundle\Utils;

/**
 * Class MomentFormatConverter - convert momen.js datetime format into php
 * @package AppBundle\Utils
 */
class MomentFormatConverter
{
    /** @var array $formatConvertRules */
    private static $formatConvertRules =
        [
            'yyyy' => 'YYYY', 'yy' => 'YY', 'y' => 'YYYY', // year
            'dd' => 'DD', 'd' => 'D', // day
            'EE' => 'ddd', 'EEEEEE' => 'dd', // day of week
            'ZZZZZ' => 'Z', 'ZZZ' => 'ZZ', // timezone
            '\'T\'' => 'T', // letter 'T'
        ];

    /**
     * Returns associated moment.js format.
     *
     * @param string $format PHP Date format
     * @return string
     */
    public function convert($format)
    {
        return strtr($format, self::$formatConvertRules);
    }
}
