<?php

namespace ipl\Tests\Scheduler\Lib;

use DateTime;
use DateTimeInterface;
use ipl\Scheduler\Contract\Frequency;

class NeverDueFrequency implements Frequency
{
    public function isDue(DateTimeInterface $dateTime): bool
    {
        return false;
    }

    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface
    {
        return (new DateTime())->setTimestamp(PHP_INT_MAX);
    }

    public function isExpired(DateTimeInterface $dateTime): bool
    {
        return false;
    }
}
