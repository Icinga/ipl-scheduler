<?php

namespace ipl\Scheduler;

use DateTime;
use ipl\Scheduler\Contract\Frequency;

class OneOff implements Frequency
{
    /** @var DateTime Start time of this frequency */
    protected $dateTime;

    public function __construct(DateTime $dateTime)
    {
        $this->dateTime = clone $dateTime;
    }

    public function isDue(DateTime $dateTime): bool
    {
        return ! $this->isExpired($dateTime) && $this->dateTime == $dateTime;
    }

    public function getNextDue(DateTime $dateTime): DateTime
    {
        if ($this->isExpired($dateTime) || $this->dateTime > $dateTime) {
            return $this->dateTime;
        }

        return $dateTime;
    }

    public function isExpired(DateTime $dateTime): bool
    {
        return $this->dateTime < $dateTime;
    }
}
