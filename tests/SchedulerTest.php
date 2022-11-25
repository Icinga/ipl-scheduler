<?php

namespace ipl\Tests\Scheduler;

use DateTime;
use ipl\Scheduler\Contract\Task;
use ipl\Tests\Scheduler\Lib\AbsoluteDueFrequency;
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

    protected function runOff()
    {
        Loop::futureTick(function () {
            Loop::stop();
        });

        Loop::run();
    }

    public function testSchedulingTasks()
    {
        $task = new PromiseBoundTask(Promise\resolve());
        $now = new DateTime();
        $now->modify('+2 month');

        $nextDue = null;
        $this->scheduler
            ->on(CountableScheduler::ON_TASK_SCHEDULED, function (Task $_, DateTime $time) use (&$nextDue) {
                $nextDue = $time;
            })
            ->schedule($task, new AbsoluteDueFrequency($now));

        $this->runOff();

        $this->assertEquals($now, $nextDue, 'Scheduler could not get the correct schedule date time');
    }

    public function testNeverDueTaskSchedules()
    {
        $neverRun = true;
        $this->scheduler
            ->schedule(new PromiseBoundTask(Promise\resolve()), new NeverDueFrequency())
            ->on(CountableScheduler::ON_TASK_RUN, function (Task $_, ExtendedPromiseInterface $p) use (&$neverRun) {
                $neverRun = false;
            });

        $this->runOff();

        $this->assertTrue($neverRun, 'Scheduler::schedule() runs a task with a never due frequency');
    }

    public function testRunningScheduledTasks()
    {
        $hasRun = false;
        $task = new PromiseBoundTask(Promise\resolve());
        $this->scheduler
            ->schedule($task, new ImmediateDueFrequency())
            ->on(CountableScheduler::ON_TASK_RUN, function (Task $t, ExtendedPromiseInterface $_) use (&$hasRun) {
                $hasRun = true;
            });

        $this->runOff();

        $this->assertTrue($hasRun, 'Scheduler::schedule() could not run scheduled tasks');
    }

    public function testCancelingRunningTasks()
    {
        $promisesCount = [];
        $task = new PromiseBoundTask((new Promise\Deferred())->promise());
        $this->scheduler
            ->schedule($task, new ImmediateDueFrequency())
            ->on(CountableScheduler::ON_TASK_CANCEL, function (Task $_, array $promises) use (&$promisesCount) {
                $promisesCount = $promises;
            });

        // We have to use addtimer!!
        Loop::addTimer(0, function () use ($task) {
            $this->scheduler->remove($task);
        });

        $this->runOff();

        $this->assertCount(2, $promisesCount, 'Scheduler::remove() could not cancel running tasks');
    }

    public function testFailedTasks()
    {
        $taskFailed = false;
        $deferred = new Promise\Deferred();
        $task = new PromiseBoundTask($deferred->promise());
        $this->scheduler
            ->schedule($task, new ImmediateDueFrequency())
            ->on(CountableScheduler::ON_TASK_RUN, function (Task $_, ExtendedPromiseInterface $p) use ($deferred) {
                $deferred->reject(new TaskRejectedException('Exited mysteriously!'));
            })
            ->on(CountableScheduler::ON_TASK_FAILED, function (Task $t, Throwable $_) use (&$taskFailed) {
                $taskFailed = true;
            });

        $this->runOff();

        $this->assertTrue($taskFailed, 'Scheduler could not handle failed tasks');
    }

    public function testTaskRunSuccess()
    {
        $taskFulFilled = false;
        $deferred = new Promise\Deferred();
        $task = new PromiseBoundTask($deferred->promise());
        $this->scheduler
            ->schedule($task, new ImmediateDueFrequency())
            ->on(CountableScheduler::ON_TASK_RUN, function (Task $_, ExtendedPromiseInterface $p) use ($deferred) {
                $deferred->resolve(10);
            })
            ->on(CountableScheduler::ON_TASK_DONE, function (Task $_, $any) use (&$taskFulFilled) {
                $taskFulFilled = true;
            });

        $this->runOff();

        $this->assertTrue($taskFulFilled, 'Scheduler could not run task successfully');
    }

    public function testPropagateTaskExceptions()
    {
        $this->expectException(TaskRejectedException::class);

        $exception = null;
        $deferred = new Promise\Deferred();
        $task = new PromiseBoundTask($deferred->promise());

        $this->scheduler
            ->schedule($task, new ImmediateDueFrequency())
            ->on(CountableScheduler::ON_TASK_RUN, function (Task $t, ExtendedPromiseInterface $_) use ($deferred) {
                $deferred->reject(new TaskRejectedException('Rejected!'));
            })
            ->on(CountableScheduler::ON_TASK_FAILED, function (Task $_, Throwable $reason) use (&$exception) {
                $exception = $reason;
            });

        $this->runOff();

        throw $exception;
    }

    public function testSpawningNewTasks()
    {
        $task1 = new PromiseBoundTask(Promise\resolve());
        $task2 = new PromiseBoundTask(Promise\resolve());

        $this->scheduler
            ->schedule($task1, new ImmediateDueFrequency())
            ->schedule($task2, new ImmediateDueFrequency());

        $this->runOff();

        $this->assertEquals(
            2,
            $this->scheduler->count(),
            'Scheduler::schedule() failed to schedule expected tasks'
        );
    }

    public function testCountScheduledTimers()
    {
        $task1 = new PromiseBoundTask(Promise\resolve());
        $task2 = new PromiseBoundTask(Promise\resolve());

        $this->scheduler
            ->schedule($task1, new ImmediateDueFrequency())
            ->schedule($task2, new ImmediateDueFrequency());

        $this->runOff();

        $this->assertEquals(
            2,
            $this->scheduler->countTimers(),
            'Scheduler::schedule() could not return expected scheduled timers'
        );
    }

    public function testTimersAndTasksCountAfterCancellation()
    {
        $deferred = new Promise\Deferred();
        $task = new PromiseBoundTask($deferred->promise());
        $this->scheduler->schedule($task, new ImmediateDueFrequency());

        Loop::futureTick(function () {
            $this->scheduler->removeTasks();
        });

        $this->runOff();

        $scheduler = $this->scheduler;
        $this->assertEquals(0, $scheduler->count(), 'Scheduler::removeTasks() could not remove all canceled tasks');
        $this->assertEquals(
            0,
            $scheduler->countTimers(),
            'Scheduler::removeTasks() could not cancel and remove all scheduled timers'
        );
    }

    public function testSchedulingExpiredTask()
    {
        $task = new PromiseBoundTask(Promise\resolve());
        $frequency = new ExpiringFrequency();
        $frequency->endAt(new DateTime('-2 hours'));

        $this->scheduler->schedule($task, $frequency);

        $this->runOff();

        $this->assertEquals(0, $this->scheduler->count(), 'Scheduler::schedule() has scheduled expired task');
    }

    public function testTaskIsDetachedAfterExpiring()
    {
        $deferred = new Promise\Deferred();
        $frequency = new ExpiringFrequency();
        $task = new PromiseBoundTask($deferred->promise());

        $expireTime = new DateTime('+1 minute');
        $frequency->endAt($expireTime);

        $expiredAt = null;
        $this->scheduler
            ->on(CountableScheduler::ON_TASK_EXPIRED, function (Task $_, DateTime $expires) use (&$expiredAt) {
                $expiredAt = $expires;
            })
            ->on(CountableScheduler::ON_TASK_RUN, function (Task $t, ExtendedPromiseInterface $_) use ($deferred, &$expireTime) {
                // Make sure the next `getNextDue()` call returns the end time of the frequency
                $expireTime->modify('-2 hours');

                $timer = Loop::addTimer(0, function () use ($deferred, &$timer) {
                    $deferred->resolve(0);

                    Loop::cancelTimer($timer);
                });
            })
            ->schedule($task, $frequency);

        $this->runOff();

        $this->assertEquals($expireTime, $expiredAt, 'Scheduler::schedule() could not get expected expire time');
        $this->assertEquals(0, $this->scheduler->count(), 'Scheduler::schedule() could not remove expired task');
    }
}
