<?php

namespace ipl\Tests\Scheduler\Lib;

use DateTimeInterface;

class ImmediateDueFrequency extends BaseTestFrequency
{
    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface
    {
        return $dateTime;
    }
}
