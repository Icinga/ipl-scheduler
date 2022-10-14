<?php

namespace ipl\Scheduler\Common;

use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use React\Promise\PromiseInterface;
use SplObjectStorage;

trait Promises
{
    /** @var SplObjectStorage */
    private $promises;

    /**
     * Add the given promise for the specified UUID
     *
     * @param UuidInterface $uuid
     * @param PromiseInterface $promise
     *
     * @return $this
     */
    protected function addPromise(UuidInterface $uuid, PromiseInterface $promise): self
    {
        if (! $this->promises->contains($uuid)) {
            $this->promises->attach($uuid, []);
        }

        $this->promises[$uuid][] = $promise;

        return $this;
    }

    /**
     * Remove the given promise for the specified UUID
     *
     * @param UuidInterface $uuid
     * @param PromiseInterface $promise
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the given UUID doesn't have any registered promises or when the specified
     *                                  UUID promises doesn't contain the provided promise
     */
    protected function removePromise(UuidInterface $uuid, PromiseInterface $promise): self
    {
        if (! $this->promises->contains($uuid)) {
            throw new InvalidArgumentException(
                sprintf('There are no registered promises for UUID %s', $uuid->toString())
            );
        }

        $key = array_search($promise, $this->promises[$uuid], true);
        if ($key === false) {
            throw new InvalidArgumentException(
                sprintf('There is no such promise for UUID %s', $uuid->toString())
            );
        }
        unset($this->promises[$uuid][$key]);

        return $this;
    }

    /**
     * Detach and return promises for the given UUID, if any
     *
     * @param UuidInterface $uuid
     *
     * @return PromiseInterface[]
     */
    protected function detachPromises(UuidInterface $uuid): array
    {
        if (! $this->promises->contains($uuid)) {
            return [];
        }

        $promises = $this->promises[$uuid];

        $this->promises->detach($uuid);

        return $promises;
    }
}
