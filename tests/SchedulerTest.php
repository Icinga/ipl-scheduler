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
        Loop::futureTick(function (): void {
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
            ->on(CountableScheduler::ON_TASK_SCHEDULED, function (Task $_, DateTime $time) use (&$scheduledAt): void {
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
            ->on(
                CountableScheduler::ON_TASK_RUN,
                function (Task $_, ExtendedPromiseInterface $p) use (&$neverRun): void {
                    $neverRun = false;
                }
            );

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
            ->on(
                CountableScheduler::ON_TASK_RUN,
                function (Task $t, ExtendedPromiseInterface $_) use (&$hasRun): void {
                    $hasRun = true;
                }
            );

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
            ->on(
                CountableScheduler::ON_TASK_CANCEL,
                function (Task $_, array $promises) use (&$canceledPromises): void {
                    $canceledPromises = $promises;
                }
            );

        // Wait 0.01s for the scheduler to run the task a couple of times before
        // removing it und stopping the event loop.
        Loop::addTimer(.01, function () use ($task): void {
            $this->scheduler->remove($task);

            Loop::stop();
        });

        Loop::run();

        // When a task is due before the ongoing promise gets resolved, the scheduler will
        // not cancel it. Instead, it will start a new one for the new operations.
        $startedPromises = $task->getStartedPromises();
        $this->assertCount($startedPromises, $canceledPromises, 'Scheduler::remove() did not cancel running tasks');

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
            ->on(
                CountableScheduler::ON_TASK_CANCEL,
                function (Task $_, array $promises) use (&$canceledPromises): void {
                    $canceledPromises = $promises;
                }
            );

        Loop::futureTick(function () use ($task): void {
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
            ->on(
                CountableScheduler::ON_TASK_RUN,
                function (Task $_, ExtendedPromiseInterface $p) use ($deferred): void {
                    $deferred->reject(new TaskRejectedException('rejected'));
                }
            )
            ->on(
                CountableScheduler::ON_TASK_FAILED,
                function (Task $t, Throwable $err) use (&$taskFailed, &$reason): void {
                    $taskFailed = true;
                    $reason = $err;
                }
            );

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
            ->on(
                CountableScheduler::ON_TASK_RUN,
                function (Task $_, ExtendedPromiseInterface $p) use ($deferred): void {
                    $deferred->resolve(10);
                }
            )
            ->on(
                CountableScheduler::ON_TASK_DONE,
                function (Task $_, $result) use (&$taskFulFilled, &$returnResult): void {
                    $taskFulFilled = true;
                    $returnResult = $result;
                }
            );

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
            ->on(CountableScheduler::ON_TASK_EXPIRED, function (Task $_, DateTime $expires) use (&$expiredAt): void {
                $expiredAt = $expires;
            })
            ->on(
                CountableScheduler::ON_TASK_RUN,
                function (Task $t, ExtendedPromiseInterface $_) use ($deferred, $frequency): void {
                    $frequency->setExpired();

                    Loop::addTimer(0, function ($timer) use ($deferred): void {
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
        $countRuns = 0;
        $deferred = new Promise\Deferred();
        $task = new PromiseBoundTask($deferred->promise());
        $this->scheduler
            ->schedule($task, new OneOff(new DateTime('+1 milliseconds')))
            ->on(
                CountableScheduler::ON_TASK_RUN,
                function (Task $t, ExtendedPromiseInterface $_) use (&$countRuns, $deferred): void {
                    $countRuns += 1;

                    $deferred->resolve();
                }
            );

        $this->runAndStopEventLoop();

        $this->assertEquals(0, $this->scheduler->count());
        $this->assertEquals(0, $this->scheduler->countTimers());
        $this->assertEquals(0, $this->scheduler->countPromises($task->getUuid()));

        $this->assertEquals(1, $countRuns, 'Scheduler::schedule() did not run a task with one-off frequency only once');
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
