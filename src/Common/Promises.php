<?php

namespace ipl\Scheduler\Common;

use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use React\Promise\CancellablePromiseInterface;
use SplObjectStorage;

trait Promises
{
    /** @var SplObjectStorage */
    private $promises;

    /**
     * Register the given cancelable promise for the given UUID
     *
     * @param UuidInterface $uuid
     * @param CancellablePromiseInterface $promise
     *
     * @return $this
     */
    protected function registerPromise(UuidInterface $uuid, CancellablePromiseInterface $promise): self
    {
        if (! $this->promises->contains($uuid)) {
            $this->promises->attach($uuid, new SplObjectStorage());
        }

        $this->promises->offsetGet($uuid)->attach($promise);

        return $this;
    }

    /**
     * Unregister the given promise for the specified UUID
     *
     * @param UuidInterface $uuid
     * @param CancellablePromiseInterface $promise
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the given UUID doesn't have any registered promises or when the specified
     *                                  UUID promises didn't contain the provided promise
     */
    protected function unregisterPromise(UuidInterface $uuid, CancellablePromiseInterface $promise): self
    {
        if (! $this->promises->contains($uuid)) {
            throw new InvalidArgumentException(
                sprintf('There are no registered promises for UUID %s', $uuid->toString())
            );
        }

        /** @var SplObjectStorage $promises */
        $promises = $this->promises->offsetGet($uuid);
        if (! $promises->contains($promise)) {
            throw new InvalidArgumentException(
                sprintf('There is no such promise for UUID %s', $uuid->toString())
            );
        }

        $promises->detach($promise);

        return $this;
    }

    /**
     * Cancel all registered promises for the given UUID
     *
     * @param UuidInterface $uuid
     *
     * @return $this
     *
     * @throws InvalidArgumentException When there are no registered promises for the given UUID
     */
    protected function cancelPromises(UuidInterface $uuid): self
    {
        if (! $this->promises->contains($uuid)) {
            throw new InvalidArgumentException(
                sprintf('There are no registered promises for UUID %s to be canceled', $uuid->toString())
            );
        }

        /** @var CancellablePromiseInterface $promise */
        foreach ($this->promises->offsetGet($uuid) as $promise) {
            $promise->cancel();
        }

        $this->promises->detach($uuid);

        return $this;
    }
}
