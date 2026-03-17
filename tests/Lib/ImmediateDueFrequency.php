<?php

namespace ipl\Tests\Scheduler\Lib;

use DateTimeInterface;

class ImmediateDueFrequency extends BaseTestFrequency
{
    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface
    {
        // Return a future time so the scheduler sets up a timer instead of hitting the
        // $nextDue <= $now early return, which would prevent the $loop callback from running.
        // One millisecond to be close to immediate.
        return (clone $dateTime)->modify('+1 millisecond');
    }
}
