# Icinga PHP Library - Tasks Scheduler

![Build Status](https://github.com/Icinga/ipl-scheduler/workflows/PHP%20Tests/badge.svg?branch=main)

Framework-independent scheduler that executes tasks at regular intervals or once at specific times in an event loop.
The tasks are pure PHP code and the frequency is defined via cron expressions. Yields events for which you can install
listeners:

* when an operation of a task is scheduled,
* upon running an operation of a task,
* when an operation of a task is done or failed,
* and when a task is canceled or expired.

Scheduled or running tasks can be removed or canceled at any time.

## Installation

The recommended way to install this library is via [Composer](https://getcomposer.org):

```
composer require ipl/scheduler
```
