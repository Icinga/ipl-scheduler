<?php

namespace ipl\Tests\Scheduler\Lib;

use BadMethodCallException;
use DateTime;
use ipl\Scheduler\Contract\Frequency;

class NeverDueFrequency implements Frequency
{
    public function isDue(DateTime $dateTime): bool
    {
        return false;
    }

    public function getNextDue(DateTime $dateTime): DateTime
    {
        return (new DateTime())->setTimestamp(PHP_INT_MAX);
    }

    public function isExpired(DateTime $dateTime): bool
    {
        return false;
    }
}
