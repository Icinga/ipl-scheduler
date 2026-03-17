<?php

namespace ipl\Scheduler\Contract;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;
use React\Promise\PromiseInterface;

interface Task
{
    /**
     * Get the name of this task
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get unique identifier of this task
     *
     * @return UuidInterface
     */
    public function getUuid(): UuidInterface;

    /**
     * Get the description of this task
     *
     * @return ?string
     */
    public function getDescription(): ?string;

    /**
     * Get the last run of this task
     *
     * @return DateTimeInterface|false|null Null if there was no last run and false if the last run is not provided
     */
    public function getLastRun(): DateTimeInterface|false|null;

    /**
     * Run this tasks operations
     *
     * This commits the actions in a non-blocking fashion to the event loop and yields a deferred promise
     *
     * @return PromiseInterface
     */
    public function run(): PromiseInterface;
}
