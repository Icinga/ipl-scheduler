<?php

namespace ipl\Tests\Scheduler\Lib;

use DateTime;
use ipl\Scheduler\Contract\Frequency;

class ExpiringFrequency implements Frequency
{
    /** @var bool */
    protected $expired = false;

    /** @var DateTime */
    protected $end;

    public function isDue(DateTime $dateTime): bool
    {
        return true;
    }

    public function getNextDue(DateTime $dateTime): DateTime
    {
        if ($this->isExpired($dateTime)) {
            return $this->end;
        }

        return $dateTime;
    }

    public function isExpired(DateTime $dateTime): bool
    {
        return $this->expired;
    }

    public function setExpired(bool $value = true): self
    {
        $this->expired = $value;

        return $this;
    }

    public function endAt(DateTime $dateTime): self
    {
        $this->end = $dateTime;

        return $this;
    }
}
