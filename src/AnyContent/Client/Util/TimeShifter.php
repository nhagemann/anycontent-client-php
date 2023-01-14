<?php

declare(strict_types=1);

namespace AnyContent\Client\Util;

class TimeShifter
{
    public static function getMaxTimestamp()
    {
        //19.01.2038
        return number_format(2147483647, 4, '.', '');
    }

    public static function getMaxTimeshift()
    {
        // roundabout 10 years, equals to 1.1.1980

        return number_format(315532800, 4, '.', '');
    }

    public static function getTimeshiftTimestamp($timeshift = 0)
    {
        if ($timeshift < self::getMaxTimeshift()) {
            return number_format(microtime(true) - $timeshift, 4, '.', '');
        }

        return $timeshift;
    }
}
