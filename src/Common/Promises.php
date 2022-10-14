<?php

namespace ipl\Scheduler\Common;

use ArrayObject;
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
            $this->promises->attach($uuid, new ArrayObject());
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

        foreach ($this->promises[$uuid] as $k => $v) {
            if ($v === $promise) {
                unset($this->promises[$uuid][$k]);

                return $this;
            }
        }

        throw new InvalidArgumentException(
            sprintf('There is no such promise for UUID %s', $uuid->toString())
        );
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

        /** @var ArrayObject $promises */
        $promises = $this->promises[$uuid];

        $this->promises->detach($uuid);

        return $promises->getArrayCopy();
    }
}
