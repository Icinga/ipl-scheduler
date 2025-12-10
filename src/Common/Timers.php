<?php

namespace ipl\Scheduler\Common;

use LogicException;
use Ramsey\Uuid\UuidInterface;
use React\EventLoop\TimerInterface;
use SplObjectStorage;

trait Timers
{
    /** @var ?SplObjectStorage<UuidInterface, TimerInterface> */
    protected ?SplObjectStorage $timers = null;

    /**
     * Set a timer for the given UUID
     *
     * **Example Usage:**
     *
     * ```php
     * $timers->attachTimer($uuid, Loop::addTimer($interval, $callback));
     * ```
     *
     * @param UuidInterface $uuid
     * @param TimerInterface $timer
     *
     * @return $this
     */
    protected function attachTimer(UuidInterface $uuid, TimerInterface $timer): static
    {
        if (! $this->timers) {
            throw new LogicException('Timers must not be null');
        }

        $this->timers->offsetSet($uuid, $timer);

        return $this;
    }

    /**
     * Detach and return the timer for the given UUID, if any
     *
     * **Example Usage:**
     *
     * ```php
     * Loop::cancelTimer($timers->detachTimer($uuid));
     * ```
     *
     * @param UuidInterface $uuid
     *
     * @return ?TimerInterface
     */
    protected function detachTimer(UuidInterface $uuid): ?TimerInterface
    {
        if (! $this->timers) {
            throw new LogicException('Timers must not be null');
        }

        if (! $this->timers->offsetExists($uuid)) {
            return null;
        }

        $timer = $this->timers->offsetGet($uuid);

        $this->timers->offsetUnset($uuid);

        return $timer;
    }
}
