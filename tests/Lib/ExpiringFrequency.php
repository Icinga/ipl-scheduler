<?php

namespace ipl\Tests\Scheduler\Lib;

use DateTimeInterface;
use ipl\Scheduler\Common\FrequencyStatus;

class ExpiringFrequency extends BaseTestFrequency
{
    /** @var ?DateTimeInterface */
    protected $end;

    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface
    {
        if (FrequencyStatus::fromFrequency($this, $dateTime) === FrequencyStatus::EXPIRED) {
            return $this->end;
        }

        // Return a future time so the scheduler sets up a timer instead of hitting the
        // $nextDue <= $now early return, which would prevent the $loop callback from running.
        return (clone $dateTime)->modify('+1 second');
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->end;
    }

    public function endAt(DateTimeInterface $dateTime): static
    {
        $this->end = $dateTime;

        return $this;
    }
}
