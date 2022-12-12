<?php

namespace ipl\Tests\Scheduler\Lib;

use BadMethodCallException;
use DateTime;
use ipl\Scheduler\Contract\Frequency;

class ImmediateDueFrequency implements Frequency
{
    public function isDue(DateTime $dateTime): bool
    {
        return true;
    }

    public function getNextDue(DateTime $dateTime): DateTime
    {
        return $dateTime;
    }

    public function isExpired(DateTime $dateTime): bool
    {
        return false;
    }
}
