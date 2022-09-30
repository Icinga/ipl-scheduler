<?php

namespace ipl\Scheduler\Common;

use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use React\EventLoop\TimerInterface;
use SplObjectStorage;

trait Timers
{
    /** @var SplObjectStorage */
    private $timers;

    /**
     * Get the registered timer for the given UUID if any
     *
     * @param UuidInterface $uuid
     *
     * @return TimerInterface
     *
     * @throws InvalidArgumentException When there is no registered timer for the given UUID
     */
    protected function getTimer(UuidInterface $uuid): TimerInterface
    {
        if (! $this->timers->contains($uuid)) {
            throw new InvalidArgumentException(
                sprintf('There is no registered timer for UUID %s', $uuid->toString())
            );
        }

        return $this->timers->offsetGet($uuid);
    }

    /**
     * Add a scheduled timer for the given UUID
     *
     * @param UuidInterface $uuid
     * @param TimerInterface $timer
     *
     * @return $this
     */
    protected function addTimer(UuidInterface $uuid, TimerInterface $timer): self
    {
        $this->timers->attach($uuid, $timer);

        return $this;
    }

    /**
     * Cancel the scheduled timer for the given UUID
     *
     * @param UuidInterface $uuid
     *
     * @return $this
     *
     * @throws InvalidArgumentException When there is no registered timer for the given UUID
     */
    protected function detachTimer(UuidInterface $uuid): self
    {
        if (! $this->timers->contains($uuid)) {
            throw new InvalidArgumentException(
                sprintf('There is no registered timer for uuid %s to be canceled', $uuid->toString())
            );
        }

        $this->loop->cancelTimer($this->getTimer($uuid));

        $this->timers->detach($uuid);

        return $this;
    }
}
