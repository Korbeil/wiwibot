<?php

declare(strict_types=1);

namespace Wiwi\Bot\Helper;

use Carbon\Carbon;
use Carbon\CarbonInterval;

class DateIntervalHelper
{
    public static function intervalToString(CarbonInterval $dateInterval): string
    {
        static $mapping = [
            ['y', 'y'],
            ['m', 'm'],
            ['d', 'd'],
            ['h', 'h'],
            ['i', 'min'],
            ['s', 'sec'],
        ];

        $tmp = [];
        foreach ($mapping as [$format, $toDisplay]) {
            if ($dateInterval->format('%' . $format) > 0) {
                $tmp[] = sprintf('%s%s', $dateInterval->format("%{$format}"), $toDisplay);
            }
        }

        return implode(' ', $tmp);
    }

    public static function secondsToString(int $seconds): string
    {
        $dtF = new Carbon('@0');
        $dtT = new Carbon("@{$seconds}");

        return self::intervalToString($dtF->diff($dtT));
    }
}
