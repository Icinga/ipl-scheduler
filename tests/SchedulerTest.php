<?php

namespace ipl\Tests\Scheduler;

use DateTime;
use ipl\Scheduler\Contract\Task;
use ipl\Scheduler\OneOff;
use ipl\Tests\Scheduler\Lib\CountableScheduler;
use ipl\Tests\Scheduler\Lib\ExpiringFrequency;
use ipl\Tests\Scheduler\Lib\ImmediateDueFrequency;
use ipl\Tests\Scheduler\Lib\NeverDueFrequency;
use ipl\Tests\Scheduler\Lib\PromiseBoundTask;
use ipl\Tests\Scheduler\Lib\TaskRejectedException;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Loop;
use React\Promise;
use React\Promise\ExtendedPromiseInterface;
use Throwable;

class SchedulerTest extends TestCase
{
    /** @var CountableScheduler */
    protected $scheduler;

    public function setUp(): void
    {
        $this->scheduler = new CountableScheduler();
    }

    protected function runAndStopEventLoop()
    {
        Loop::futureTick(function () {
            Loop::stop();
        });

        Loop::run();
    }

    public function testSchedulingTasksNotYetDue()
    {
        $task = new PromiseBoundTask(Promise\resolve());
        $nextDue = new DateTime('+1 week');

        $scheduledAt = null;
        $this->scheduler
            ->on(CountableScheduler::ON_TASK_SCHEDULED, function (Task $_, DateTime $time) use (&$scheduledAt) {
                $scheduledAt = $time;
            })
            ->schedule($task, new OneOff($nextDue));

        $this->runAndStopEventLoop();

        $this->assertEquals($nextDue, $scheduledAt, 'Scheduler did not get the correct schedule date time');

        $this->assertEquals(1, $this->scheduler->count());
        $this->assertEquals(1, $this->scheduler->countTimers());
        $this->assertEquals(0, $this->scheduler->countPromises($task->getUuid()));
    }

    public function testDoesNotScheduleNeverDueTasks()
    {
        $neverRun = true;
        $task = new PromiseBoundTask(Promise\resolve());
        $this->scheduler
            ->schedule($task, new NeverDueFrequency())
            ->on(CountableScheduler::ON_TASK_RUN, function (Task $_, ExtendedPromiseInterface $p) use (&$neverRun) {
                $neverRun = false;
            });

        $this->runAndStopEventLoop();

        $this->assertTrue($neverRun, 'Scheduler::schedule() runs a task with a never due frequency');

        $this->assertEquals(1, $this->scheduler->count());
        $this->assertEquals(1, $this->scheduler->countTimers());
        $this->assertEquals(0, $this->scheduler->countPromises($task->getUuid()));
    }

    public function testDueTasksRunImmediately()
    {
        $hasRun = false;
        $task = new PromiseBoundTask(Promise\resolve());
        $this->scheduler
            ->schedule($task, new ImmediateDueFrequency())
            ->on(CountableScheduler::ON_TASK_RUN, function (Task $t, ExtendedPromiseInterface $_) use (&$hasRun) {
                $hasRun = true;
            });

        $this->runAndStopEventLoop();

        $this->assertTrue($hasRun, 'Scheduler::schedule() did not run scheduled tasks');

        $this->assertEquals(1, $this->scheduler->count());
        $this->assertEquals(1, $this->scheduler->countTimers());
        $this->assertEquals(0, $this->scheduler->countPromises($task->getUuid()));
    }

    public function testCancelingRunningTasks()
    {
        $canceledPromises = [];
        $task = new PromiseBoundTask((new Promise\Deferred())->promise());
        $this->scheduler
            ->schedule($task, new ImmediateDueFrequency())
            ->on(CountableScheduler::ON_TASK_CANCEL, function (Task $_, array $promises) use (&$canceledPromises) {
                $canceledPromises = $promises;
            });

        // Tick callbacks are guaranteed to be executed in the order they are enqueued,
        // thus we will not be able to remove the task before it is finished. Therefore,
        // we need to use timers with 0 intervals.
        Loop::addTimer(0, function () use ($task) {
            $this->scheduler->remove($task);
        });

        $this->runAndStopEventLoop();

        // When a task is due before the ongoing promise gets resolved, the scheduler will
        // not cancel it. Instead, it will start a new one for the new operations.
        $this->assertCount(2, $canceledPromises, 'Scheduler::remove() did not cancel running tasks');

        $scheduledTimers = $this->scheduler->countTimers();
        $scheduledTasks = $this->scheduler->count();
        $remainingPromises = $this->scheduler->countPromises($task->getUuid());

        $this->assertEquals(0, $scheduledTasks, 'Scheduler::removeTasks() did not remove all canceled tasks');
        $this->assertEquals(0, $scheduledTimers, 'Scheduler::removeTasks() did not remove all scheduled timers');
        $this->assertEquals(0, $remainingPromises, 'Scheduler::removeTasks() did not remove all promises of a task');
    }

    public function testCancelingScheduledNotYetRunTasks()
    {
        $canceledPromises = [];
        $task = new PromiseBoundTask((new Promise\Deferred())->promise());
        $this->scheduler
            ->schedule($task, new NeverDueFrequency())
            ->on(CountableScheduler::ON_TASK_CANCEL, function (Task $_, array $promises) use (&$canceledPromises) {
                $canceledPromises = $promises;
            });

        Loop::futureTick(function () use ($task) {
            $this->scheduler->remove($task);
        });

        $this->runAndStopEventLoop();

        $this->assertCount(0, $canceledPromises);

        $this->assertEquals(0, $this->scheduler->count());
        $this->assertEquals(0, $this->scheduler->countTimers());
        $this->assertEquals(0, $this->scheduler->countPromises($task->getUuid()));
    }

    public function testFailedTasksPropagateReason()
    {
        $taskFailed = false;
        $reason = null;
        $deferred = new Promise\Deferred();
        $task = new PromiseBoundTask($deferred->promise());
        $this->scheduler
            ->schedule($task, new ImmediateDueFrequency())
            ->on(CountableScheduler::ON_TASK_RUN, function (Task $_, ExtendedPromiseInterface $p) use ($deferred) {
                $deferred->reject(new TaskRejectedException('rejected'));
            })
            ->on(CountableScheduler::ON_TASK_FAILED, function (Task $t, Throwable $err) use (&$taskFailed, &$reason) {
                $taskFailed = true;
                $reason = $err;
            });

        $this->runAndStopEventLoop();

        $this->assertTrue($taskFailed, 'Scheduler did not handle failed tasks');

        $this->assertInstanceOf(TaskRejectedException::class, $reason);
        $this->assertEquals('rejected', $reason->getMessage());

        $this->assertEquals(1, $this->scheduler->count());
        $this->assertEquals(1, $this->scheduler->countTimers());
        $this->assertEquals(0, $this->scheduler->countPromises($task->getUuid()));
    }

    public function testDoneTasksPropagateReturn()
    {
        $taskFulFilled = false;
        $returnResult = null;
        $deferred = new Promise\Deferred();
        $task = new PromiseBoundTask($deferred->promise());
        $this->scheduler
            ->schedule($task, new ImmediateDueFrequency())
            ->on(CountableScheduler::ON_TASK_RUN, function (Task $_, ExtendedPromiseInterface $p) use ($deferred) {
                $deferred->resolve(10);
            })
            ->on(CountableScheduler::ON_TASK_DONE, function (Task $_, $result) use (&$taskFulFilled, &$returnResult) {
                $taskFulFilled = true;
                $returnResult = $result;
            });

        $this->runAndStopEventLoop();

        $this->assertTrue($taskFulFilled, 'Scheduler did not run a task successfully');
        $this->assertEquals(10, $returnResult, 'Scheduler::runTask() did not propagate expected return result');

        $this->assertEquals(1, $this->scheduler->count());
        $this->assertEquals(1, $this->scheduler->countTimers());
        $this->assertEquals(0, $this->scheduler->countPromises($task->getUuid()));
    }

    public function testCountsWithMultipleScheduledTasks()
    {
        $task1 = new PromiseBoundTask(Promise\resolve());
        $task2 = new PromiseBoundTask(Promise\resolve());
        $task3 = new PromiseBoundTask((new Promise\Deferred())->promise());

        $this->scheduler
            ->schedule($task1, new ImmediateDueFrequency())
            ->schedule($task2, new ImmediateDueFrequency())
            ->schedule($task3, new OneOff(new DateTime('+1 milliseconds')));

        $this->runAndStopEventLoop();

        $scheduledTimers = $this->scheduler->countTimers();

        $this->assertEquals(3, $this->scheduler->count(), 'Scheduler::schedule() failed to schedule expected tasks');
        $this->assertEquals(3, $scheduledTimers, 'Scheduler::schedule() did not return expected scheduled timers');

        $this->assertEquals(0, $this->scheduler->countPromises($task1->getUuid()));
        $this->assertEquals(0, $this->scheduler->countPromises($task2->getUuid()));
        $this->assertEquals(1, $this->scheduler->countPromises($task3->getUuid()));
    }

    public function testDoesNotScheduleExpiredTasks()
    {
        $task = new PromiseBoundTask(Promise\resolve());
        $frequency = new ExpiringFrequency();
        $frequency->setExpired();

        $this->scheduler->schedule($task, $frequency);

        $this->runAndStopEventLoop();

        $this->assertEquals(0, $this->scheduler->count(), 'Scheduler::schedule() has scheduled expired task');
        $this->assertEquals(0, $this->scheduler->countTimers());
        $this->assertEquals(0, $this->scheduler->countPromises($task->getUuid()));
    }

    public function testTaskIsDetachedAfterExpiring()
    {
        $deferred = new Promise\Deferred();
        $frequency = new ExpiringFrequency();
        $task = new PromiseBoundTask($deferred->promise());

        $expireTime = new DateTime();
        $frequency->endAt($expireTime);

        $expiredAt = null;
        $this->scheduler
            ->on(CountableScheduler::ON_TASK_EXPIRED, function (Task $_, DateTime $expires) use (&$expiredAt) {
                $expiredAt = $expires;
            })
            ->on(
                CountableScheduler::ON_TASK_RUN,
                function (Task $t, ExtendedPromiseInterface $_) use ($deferred, $frequency) {
                    $frequency->setExpired();

                    $timer = Loop::addTimer(0, function () use ($deferred, &$timer) {
                        $deferred->resolve(0);

                        Loop::cancelTimer($timer);
                    });
                }
            )
            ->schedule($task, $frequency);

        $this->runAndStopEventLoop();

        $this->assertEquals($expireTime, $expiredAt, 'Scheduler::schedule() did not get expected expire time');

        $this->assertEquals(0, $this->scheduler->count(), 'Scheduler::schedule() did not remove expired task');
        $this->assertEquals(0, $this->scheduler->countTimers());
        $this->assertEquals(0, $this->scheduler->countPromises($task->getUuid()));
    }

    public function testOneOffTasksRunOnlyOnce()
    {
        $hasRun = false;
        $task = new PromiseBoundTask((new Promise\Deferred())->promise());

        $this->scheduler
            ->schedule($task, new OneOff(new DateTime('+1 milliseconds')))
            ->on(CountableScheduler::ON_TASK_RUN, function (Task $t, ExtendedPromiseInterface $_) use (&$hasRun) {
                $hasRun = true;
            });

        $this->runAndStopEventLoop();

        $this->assertTrue($hasRun, 'Scheduler::schedule() did not run a task with one-off frequency');

        $this->assertEquals(1, $this->scheduler->count());
        $this->assertEquals(1, $this->scheduler->countTimers());
        $this->assertEquals(1, $this->scheduler->countPromises($task->getUuid()));
    }

    public function testAlreadyRejectedTaskErrorPropagation()
    {
        $this->expectNotToPerformAssertions();

        // Won't work yet
        $task = new PromiseBoundTask(Promise\reject(new TaskRejectedException()));
        $task1 = new PromiseBoundTask(Promise\reject('Rejected!'));

        $this->scheduler->schedule($task, new ImmediateDueFrequency());

        $this->runAndStopEventLoop();

        // Assert here
    }
}
