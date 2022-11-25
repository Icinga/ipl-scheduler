<?php

namespace ipl\Tests\Scheduler\Lib;

use DateTime;
use ipl\Scheduler\Contract\Frequency;

class ExpiringFrequency extends BaseTestFrequency
{
    /** @var DateTime */
    protected $end;

    public function isDue(DateTime $dateTime = null): bool
    {
        return true;
    }

    public function getNextDue(DateTime $dateTime): DateTime
    {
        if ($this->isExpired($dateTime)) {
            return $this->end;
        }

        return $dateTime->modify('+1 minute');
    }

    public function isExpired(DateTime $dateTime): bool
    {
        return $this->end <= $dateTime;
    }

    public function endAt(DateTime $end): Frequency
    {
        $this->end = $end;

        return $this;
    }
}
