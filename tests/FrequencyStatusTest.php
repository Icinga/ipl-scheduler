<?php

namespace ipl\Tests\Scheduler;

use DateTime;
use ipl\Scheduler\Common\FrequencyStatus;
use ipl\Scheduler\Cron;
use ipl\Scheduler\OneOff;
use ipl\Scheduler\RRule;
use PHPUnit\Framework\TestCase;

class FrequencyStatusTest extends TestCase
{
    public function testRRuleWithoutStartAndEnd(): void
    {
        $rrule = RRule::fromFrequency(RRule::HOURLY);

        // No start and end date means always ready, regardless of the date
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($rrule, new DateTime('1970-01-01'))
        );

        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($rrule, new DateTime('9999-12-31'))
        );
    }

    public function testRRuleWithStartOnly(): void
    {
        $start = new DateTime('12:00');
        $rrule = RRule::fromFrequency(RRule::HOURLY)
            ->startAt($start);

        // One hour before the start date
        $this->assertSame(
            FrequencyStatus::PENDING,
            FrequencyStatus::fromFrequency($rrule, (clone $start)->modify('-1 hour'))
        );

        // Exactly the start date (start date is inclusive, so it's ready)
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($rrule, $start)
        );

        // One hour after the start date (no end date, so still ready)
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($rrule, (clone $start)->modify('+1 hour'))
        );
    }

    public function testRRuleWithEndOnly(): void
    {
        $end = new DateTime('14:00');
        $rrule = RRule::fromFrequency(RRule::HOURLY)
            ->endAt($end);

        // One hour before the end date
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($rrule, (clone $end)->modify('-1 hour'))
        );

        // Exactly the end date (end date is inclusive, so it's ready)
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($rrule, $end)
        );

        // One hour after the end date
        $this->assertSame(
            FrequencyStatus::EXPIRED,
            FrequencyStatus::fromFrequency($rrule, (clone $end)->modify('+1 hour'))
        );
    }

    public function testRRuleWithStartAndEnd(): void
    {
        $start = new DateTime('12:00');
        $end = new DateTime('14:00');
        $rrule = RRule::fromFrequency(RRule::HOURLY)
            ->startAt($start)
            ->endAt($end);

        // One hour before the start date
        $this->assertSame(
            FrequencyStatus::PENDING,
            FrequencyStatus::fromFrequency($rrule, new DateTime('11:00'))
        );

        // Exactly the start date (start date is inclusive, so it's ready)
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($rrule, $start)
        );

        // Between start and end date
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($rrule, new DateTime('13:00'))
        );

        // Exactly the end date (end date is inclusive, so it's ready)
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($rrule, $end)
        );

        // One hour after the end date
        $this->assertSame(
            FrequencyStatus::EXPIRED,
            FrequencyStatus::fromFrequency($rrule, new DateTime('15:00'))
        );
    }

    public function testCronWithoutStartAndEnd(): void
    {
        $cron = (new Cron('0 * * * *'));

        // No start and end date means always ready, regardless of the date
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($cron, new DateTime('1970-01-01'))
        );

        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($cron, new DateTime('9999-12-31'))
        );
    }

    public function testCronWithStartOnly(): void
    {
        $start = new DateTime('12:00');
        $cron = (new Cron('0 * * * *'))
            ->startAt($start);

        // One hour before the start date
        $this->assertSame(
            FrequencyStatus::PENDING,
            FrequencyStatus::fromFrequency($cron, (clone $start)->modify('-1 hour'))
        );

        // Exactly the start date (start date is inclusive, so it's ready)
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($cron, $start)
        );

        // One hour after the start date (no end date, so still ready)
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($cron, (clone $start)->modify('+1 hour'))
        );
    }

    public function testCronWithEndOnly(): void
    {
        $end = new DateTime('14:00');
        $cron = (new Cron('0 * * * *'))
            ->endAt($end);

        // One hour before the end date
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($cron, (clone $end)->modify('-1 hour'))
        );

        // Exactly the end date (end date is inclusive, so it's ready)
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($cron, $end)
        );

        // One hour after the end date
        $this->assertSame(
            FrequencyStatus::EXPIRED,
            FrequencyStatus::fromFrequency($cron, (clone $end)->modify('+1 hour'))
        );
    }

    public function testCronWithStartAndEnd(): void
    {
        $start = new DateTime('12:00');
        $end = new DateTime('14:00');
        $cron = (new Cron('0 * * * *'))
            ->startAt($start)
            ->endAt($end);

        // One hour before the start date
        $this->assertSame(
            FrequencyStatus::PENDING,
            FrequencyStatus::fromFrequency($cron, new DateTime('11:00'))
        );

        // Exactly the start date (start date is inclusive, so it's ready)
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($cron, $start)
        );

        // Between start and end date
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($cron, new DateTime('13:00'))
        );

        // Exactly the end date (end date is inclusive, so it's ready)
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($cron, $end)
        );

        // One hour after the end date
        $this->assertSame(
            FrequencyStatus::EXPIRED,
            FrequencyStatus::fromFrequency($cron, new DateTime('15:00'))
        );
    }

    public function testOneOff(): void
    {
        $start = new DateTime('12:00');
        $oneOff = new OneOff($start);

        // One hour before the scheduled time
        $this->assertSame(
            FrequencyStatus::PENDING,
            FrequencyStatus::fromFrequency($oneOff, (clone $start)->modify('-1 hour'))
        );

        // Exactly the scheduled time
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($oneOff, $start)
        );

        // One hour after the scheduled time (no end date, so still ready)
        $this->assertSame(
            FrequencyStatus::READY,
            FrequencyStatus::fromFrequency($oneOff, (clone $start)->modify('+1 hour'))
        );
    }
}
