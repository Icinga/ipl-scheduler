<?php

namespace ipl\Tests\Scheduler\Lib;

use DateTimeInterface;
use ipl\Scheduler\Contract\Frequency;

class ImmediateDueFrequency implements Frequency
{
    public function isDue(DateTimeInterface $dateTime): bool
    {
        return true;
    }

    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface
    {
        return $dateTime;
    }

    public function isExpired(DateTimeInterface $dateTime): bool
    {
        return false;
    }
}
