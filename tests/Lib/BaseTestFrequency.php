<?php

namespace ipl\Tests\Scheduler\Lib;

use BadMethodCallException;
use DateTime;
use ipl\Scheduler\Contract\Frequency;

abstract class BaseTestFrequency implements Frequency
{
    public function isExpired(DateTime $dateTime): bool
    {
        return false;
    }

    public function startAt(DateTime $start): Frequency
    {
        throw new BadMethodCallException('Not implemented');
    }

    public function endAt(DateTime $end): Frequency
    {
        throw new BadMethodCallException('Not implemented');
    }
}
