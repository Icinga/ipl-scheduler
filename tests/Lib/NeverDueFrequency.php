<?php

namespace ipl\Tests\Scheduler\Lib;

use DateTime;
use DateTimeInterface;

class NeverDueFrequency extends BaseTestFrequency
{
    public function isDue(DateTimeInterface $dateTime): bool
    {
        return false;
    }

    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface
    {
        return (new DateTime())->setTimestamp(PHP_INT_MAX);
    }
}
