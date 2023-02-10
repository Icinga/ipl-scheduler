<?php

namespace ipl\Tests\Scheduler\Lib;

use DateTimeInterface;

class ExpiringFrequency extends BaseTestFrequency
{
    /** @var bool */
    protected $expired = false;

    /** @var DateTimeInterface */
    protected $end;

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
