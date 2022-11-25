<?php

namespace ipl\Tests\Scheduler\Lib;

use BadMethodCallException;
use DateTime;
use ipl\Scheduler\Contract\Frequency;

class ImmediateDueFrequency extends BaseTestFrequency
{
    public function isDue(DateTime $dateTime = null): bool
    {
        return true;
    }

    public function getNextDue(DateTime $dateTime): DateTime
    {
        return $dateTime;
    }

    public function startAt(DateTime $start): Frequency
    {
        throw new BadMethodCallException('Not implemented');
    }
}
