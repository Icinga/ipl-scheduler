<?php

namespace ipl\Tests\Scheduler\Mock;

use BadMethodCallException;
use DateTime;
use ipl\Scheduler\Contract\Frequency;

class NeverDueFrequency implements Frequency
{
    public function isDue(DateTime $dateTime = null): bool
    {
        return false;
    }

    public function getNextDue(DateTime $dateTime): DateTime
    {
        return (new DateTime())->setTimestamp(PHP_INT_MAX);
    }

    public function startAt(DateTime $start): Frequency
    {
        throw new BadMethodCallException('Not implemented');
    }
}