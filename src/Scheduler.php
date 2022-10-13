<?php

namespace ipl\Scheduler;

use DateTime;
use InvalidArgumentException;
use ipl\Scheduler\Common\Promises;
use ipl\Scheduler\Common\Timers;
use ipl\Scheduler\Contract\Frequency;
use ipl\Scheduler\Contract\Task;
use ipl\Stdlib\Events;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use SplObjectStorage;
use Throwable;

class Scheduler
{
    use Events;
    use Timers;
    use Promises;

    public const ON_TASK_DONE = 'task-done';

    public const ON_TASK_FAILED = 'task-failed';

    public const ON_TASK_SCHEDULED = 'task-scheduled';

    /** @var LoopInterface The underlying event loop responsible for spawning the tasks */
    protected $loop;

    /** @var SplObjectStorage The scheduled tasks of this scheduler */
    protected $tasks;

    public function __construct()
    {
        $this->loop = Loop::get();

        $this->tasks = new SplObjectStorage();
        $this->timers = new SplObjectStorage();
        $this->promises = new SplObjectStorage();

        $this->init();
    }

    /**
     * Initialize this scheduler
     */
    protected function init(): void
    {
    }

    /**
     * Remove and cancel the given task
     *
     * Cancels the pending timer and all unresolved promises for the given task
     *
     * @param Task $task
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the given task isn't scheduled
     */
    public function remove(Task $task): self
    {
        if (! $this->hasTask($task)) {
            throw new InvalidArgumentException(sprintf('Task %s not scheduled', $task->getName()));
        }

        $this->detachTimer($task->getUuid());
        $this->cancelPromises($task->getUuid());

        $this->tasks->detach($task);

        return $this;
    }

    /**
     * Remove and cancel all tasks
     *
     * @return $this
     */
    public function removeTasks(): self
    {
        foreach ($this->tasks as $task) {
            $this->remove($task);
        }

        return $this;
    }

    /**
     * Get whether the specified task is scheduled
     *
     * @param Task $task
     *
     * @return bool
     */
    public function hasTask(Task $task): bool
    {
        return $this->tasks->contains($task);
    }

    /**
     * Schedule the given task based on the specified frequency
     *
     * @param Task $task
     * @param Frequency $frequency
     *
     * @return void
     */
    public function schedule(Task $task, Frequency $frequency): void
    {
        $now = new DateTime();
        if ($frequency->isDue($now)) {
            $this->loop->futureTick(function () use (&$task) {
                $this->runTask($task);
            });
            $this->emit(static::ON_TASK_SCHEDULED, [$task, $now]);
        }

        $nextDue = $frequency->getNextDue($now);

        $loop = function () use (&$loop, &$task, $frequency) {
            $this->runTask($task);

            $now = new DateTime();
            $nextDue = $frequency->getNextDue($now);

            $timer = $this->loop->addTimer($nextDue->getTimestamp() - $now->getTimestamp(), $loop);
            $this->addTimer($task->getUuid(), $timer);
            $this->emit(static::ON_TASK_SCHEDULED, [$task, $nextDue]);
        };

        $timer = $this->loop->addTimer($nextDue->getTimestamp() - $now->getTimestamp(), $loop);
        $this->addTimer($task->getUuid(), $timer);
        $this->emit(static::ON_TASK_SCHEDULED, [$task, $nextDue]);

        $this->tasks->attach($task);
    }

    public function isValidEvent($event)
    {
        $events = array_flip([
            static::ON_TASK_DONE,
            static::ON_TASK_FAILED,
            static::ON_TASK_SCHEDULED
        ]);

        return isset($events[$event]);
    }

    /**
     * Runs the given task immediately and registers handlers for the returned promise
     *
     * @param Task $task
     *
     * @return void
     */
    protected function runTask(Task $task): void
    {
        $promise = $task->run();
        $this->registerPromise($task->getUuid(), $promise);

        $promise->then(
            function ($result) use ($task, &$promise) {
                $this->emit(self::ON_TASK_DONE, [$task, $result]);
            },
            function (Throwable $reason) use ($task) {
                $this->emit(self::ON_TASK_FAILED, [$task, $reason]);
            }
        )->always(function () use ($task, &$promise) {
            // Unregister the promise without canceling it as it's already resolved
            $this->unregisterPromise($task->getUuid(), $promise);
        });
    }
}
