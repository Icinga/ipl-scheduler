<?php

namespace ipl\Tests\Scheduler\Lib;

use DateTimeInterface;
use ipl\Scheduler\Contract\Frequency;

class ExpiringFrequency implements Frequency
{
    /** @var bool */
    protected $expired = false;

    /** @var DateTimeInterface */
    protected $end;

    public function isDue(DateTimeInterface $dateTime): bool
    {
        return true;
    }

    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface
    {
        if ($this->isExpired($dateTime)) {
            return $this->end;
        }

        return $dateTime;
    }

    public function isExpired(DateTimeInterface $dateTime): bool
    {
        return $this->expired;
    }

    public function setExpired(bool $value = true): self
    {
        $this->expired = $value;

        return $this;
    }

    public function endAt(DateTimeInterface $dateTime): self
    {
        $this->end = $dateTime;

        return $this;
    }
}
