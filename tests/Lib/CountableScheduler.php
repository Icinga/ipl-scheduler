<?php

namespace ipl\Tests\Scheduler\Lib;

use Countable;
use ipl\Scheduler\Scheduler;
use Ramsey\Uuid\UuidInterface;

class CountableScheduler extends Scheduler implements Countable
{
    public function count(): int
    {
        return $this->tasks->count();
    }

    public function countPromises(UuidInterface $uuid): int
    {
        if (! $this->promises->offsetExists($uuid)) {
            return 0;
        }

        return $this->promises->offsetGet($uuid)->count();
    }

    public function countTimers(): int
    {
        return $this->timers->count();
    }
}
