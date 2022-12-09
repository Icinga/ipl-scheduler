<?php

namespace ipl\Tests\Scheduler\Lib;

use BadMethodCallException;
use DateTime;
use ipl\Scheduler\Contract\Frequency;

class AbsoluteDueFrequency implements Frequency
{
    /** @var DateTime */
    protected $dateTime;

    public function __construct(DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    public function isDue(DateTime $dateTime): bool
    {
        return $this->dateTime <= $dateTime;
    }

    public function getNextDue(DateTime $dateTime): DateTime
    {
        return max($this->dateTime, $dateTime);
    }
}
