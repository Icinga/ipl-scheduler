# Icinga PHP Library - Tasks Scheduler

`ipl-scheduler` provides an event-loop-driven task scheduler for PHP. It runs
tasks at regular intervals or once at a specific time, using an event-driven
and promise-based model built on top of [ReactPHP](https://reactphp.org/).

Tasks are plain PHP classes. Frequency is configured independently via cron
expressions, one-off datetimes, or iCalendar recurrence rules. The scheduler
emits lifecycle events so you can observe scheduling, execution, completion,
failures, and expiration — without coupling your tasks to the scheduler itself.

## Installation

The recommended way to install this library is via
[Composer](https://getcomposer.org):

```shell
composer require ipl/scheduler
```

`ipl/scheduler` requires PHP 8.2 or later.

For best performance, install the [`ext-ev`](https://www.php.net/manual/en/intro.ev.php)
PHP extension. It significantly improves event loop efficiency and is highly recommended
for production use.

## Key Abstractions

| Class       | Description                                      |
|-------------|--------------------------------------------------|
| `Scheduler` | Manages tasks in the ReactPHP event loop         |
| `Cron`      | Repeating frequency driven by a cron expression  |
| `RRule`     | Repeating frequency driven by an iCalendar RRULE |
| `OneOff`    | Single-run frequency at an exact point in time   |

## Usage

### Implementing a Task

Implement the `Task` interface. Use the `TaskProperties` trait to satisfy the
`getName()`, `getUuid()`, and `getDescription()` requirements without
boilerplate.

Return `resolve()` or `reject()` when the result is already available synchronously.
For background work such as async I/O, subprocess calls, or non-blocking HTTP requests,
use a `Deferred` and settle it once the operation finishes:

```php
use ipl\Scheduler\Common\TaskProperties;
use ipl\Scheduler\Contract\Task;
use Ramsey\Uuid\Uuid;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Throwable;

use function React\Promise\resolve;

class SendReport implements Task
{
    use TaskProperties;

    public function __construct()
    {
        $this->setName('send-report');
        $this->setUuid(Uuid::uuid4());
    }

    public function run(): PromiseInterface
    {
        // Use resolve() or reject() only when the result is already available synchronously.
        // return resolve('Report sent.');

        // Use a Deferred when the work happens asynchronously, e.g. after a
        // subprocess calls, a stream read, or a non-blocking HTTP call completes.
        $deferred = new Deferred();

        Loop::futureTick(function () use ($deferred): void {
            try {
                // Perform actual work here.
                $deferred->resolve('Report sent.');
            } catch (Throwable $e) {
                $deferred->reject($e);
            }
        });

        return $deferred->promise();
    }
}
```

> **Note:** Always call either `resolve()` or `reject()` on every code path.
> A `Deferred` whose promise is never settled will silently prevent
> `ON_TASK_DONE` and `ON_TASK_FAILED` from firing.

### Scheduling with a Cron Expression

Use `Cron` to run a task on a repeating schedule defined by a standard
five-field cron expression:

```php
use ipl\Scheduler\Cron;
use ipl\Scheduler\Scheduler;
use React\EventLoop\Loop;

$scheduler = new Scheduler();

// Run every day at 08:00.
$scheduler->schedule(new SendReport(), new Cron('0 8 * * *'));

Loop::run();
```

### Scheduling a One-Off Run

Use `OneOff` to run a task exactly once at a specific datetime:

```php
use ipl\Scheduler\OneOff;
use ipl\Scheduler\Scheduler;
use React\EventLoop\Loop;

$scheduler = new Scheduler();

$scheduler->schedule(new SendReport(), new OneOff(new DateTime('2026-04-01 09:00:00')));

Loop::run();
```

### Scheduling with an iCalendar Recurrence Rule

Use `RRule` for complex recurrence patterns. Pass a timezone string to align
recurrences with a specific offset:

```php
use ipl\Scheduler\RRule;
use ipl\Scheduler\Scheduler;
use React\EventLoop\Loop;

$scheduler = new Scheduler();

// Run every day at 09:00 Europe/Berlin time.
$frequency = RRule::fromFrequency(RRule::DAILY);
$frequency
    ->setTimezone('Europe/Berlin')
    ->startAt(new DateTime('2026-01-01 09:00:00'));

$scheduler->schedule(new SendReport(), $frequency);

Loop::run();
```

You can also construct an `RRule` directly from an RRULE string:

```php
$frequency = new RRule('FREQ=WEEKLY;BYDAY=MO,WE,FR');
```

### Listening to Lifecycle Events

The scheduler emits events at each stage of a task's lifecycle. Register
listeners using `on()`:

```php
use ipl\Scheduler\Scheduler;
use ipl\Scheduler\Contract\Task;
use React\Promise\PromiseInterface;

$scheduler = new Scheduler();

$scheduler->on(Scheduler::ON_TASK_SCHEDULED, function (Task $task, DateTime $runAt) {
    echo sprintf('Task "%s" scheduled for %s', $task->getName(), $runAt->format('c'));
});

$scheduler->on(Scheduler::ON_TASK_RUN, function (Task $task, PromiseInterface $promise) {
    echo sprintf('Task "%s" is running', $task->getName());
});

$scheduler->on(Scheduler::ON_TASK_DONE, function (Task $task, mixed $result) {
    echo sprintf('Task "%s" completed: %s', $task->getName(), $result);
});

$scheduler->on(Scheduler::ON_TASK_FAILED, function (Task $task, Throwable $e) {
    echo sprintf('Task "%s" failed: %s', $task->getName(), $e->getMessage());
});

$scheduler->on(Scheduler::ON_TASK_EXPIRED, function (Task $task, DateTime $expiredAt) {
    echo sprintf('Task "%s" expired at %s', $task->getName(), $expiredAt->format('c'));
});
```

#### Available Events

| Constant                       | Triggered when                                  |
|--------------------------------|-------------------------------------------------|
| `Scheduler::ON_TASK_SCHEDULED` | An operation is queued for future execution     |
| `Scheduler::ON_TASK_RUN`       | An operation starts running                     |
| `Scheduler::ON_TASK_DONE`      | An operation completes successfully             |
| `Scheduler::ON_TASK_FAILED`    | An operation throws or rejects                  |
| `Scheduler::ON_TASK_EXPIRED`   | A task has passed its configured end time       |
| `Scheduler::ON_TASK_CANCEL`    | A task is removed while an operation is pending |

### Removing a Task

Call `remove()` to cancel a scheduled task at any time. Any pending
operations are canceled and the `ON_TASK_CANCEL` event is emitted:

```php
$scheduler->remove($task);
```

To remove all scheduled tasks at once:

```php
$scheduler->removeTasks();
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of notable changes.

## License

`ipl/scheduler` is licensed under the terms of the [MIT License](LICENSE.md).
