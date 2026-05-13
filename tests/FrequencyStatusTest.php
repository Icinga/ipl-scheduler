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
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, new DateTime('1970-01-01'))->isReady());

        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, new DateTime('9999-12-31'))->isReady());
    }

    public function testRRuleWithStartOnly(): void
    {
        $start = new DateTime('12:00');
        $rrule = RRule::fromFrequency(RRule::HOURLY)
            ->startAt($start);

        // One hour before the start date
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, (clone $start)->modify('-1 hour'))->isPending());

        // Exactly the start date (start date is inclusive, so it's ready)
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, $start)->isReady());

        // One hour after the start date (no end date, so still ready)
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, (clone $start)->modify('+1 hour'))->isReady());
    }

    public function testRRuleWithEndOnly(): void
    {
        $end = new DateTime('14:00');
        $rrule = RRule::fromFrequency(RRule::HOURLY)
            ->endAt($end);

        // One hour before the end date
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, (clone $end)->modify('-1 hour'))->isReady());

        // Exactly the end date (end date is inclusive, so it's ready)
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, $end)->isReady());

        // One hour after the end date
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, (clone $end)->modify('+1 hour'))->isExpired());
    }

    public function testRRuleWithStartAndEnd(): void
    {
        $start = new DateTime('12:00');
        $end = new DateTime('14:00');
        $rrule = RRule::fromFrequency(RRule::HOURLY)
            ->startAt($start)
            ->endAt($end);

        // One hour before the start date
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, new DateTime('11:00'))->isPending());

        // Exactly the start date (start date is inclusive, so it's ready)
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, $start)->isReady());

        // Between start and end date
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, new DateTime('13:00'))->isReady());

        // Exactly the end date (end date is inclusive, so it's ready)
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, $end)->isReady());

        // One hour after the end date
        $this->assertTrue(FrequencyStatus::fromFrequency($rrule, new DateTime('15:00'))->isExpired());
    }

    public function testCronWithoutStartAndEnd(): void
    {
        $cron = (new Cron('0 * * * *'));

        // No start and end date means always ready, regardless of the date
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, new DateTime('1970-01-01'))->isReady());

        $this->assertTrue(FrequencyStatus::fromFrequency($cron, new DateTime('9999-12-31'))->isReady());
    }

    public function testCronWithStartOnly(): void
    {
        $start = new DateTime('12:00');
        $cron = (new Cron('0 * * * *'))
            ->startAt($start);

        // One hour before the start date
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, (clone $start)->modify('-1 hour'))->isPending());

        // Exactly the start date (start date is inclusive, so it's ready)
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, $start)->isReady());

        // One hour after the start date (no end date, so still ready)
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, (clone $start)->modify('+1 hour'))->isReady());
    }

    public function testCronWithEndOnly(): void
    {
        $end = new DateTime('14:00');
        $cron = (new Cron('0 * * * *'))
            ->endAt($end);

        // One hour before the end date
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, (clone $end)->modify('-1 hour'))->isReady());

        // Exactly the end date (end date is inclusive, so it's ready)
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, $end)->isReady());

        // One hour after the end date
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, (clone $end)->modify('+1 hour'))->isExpired());
    }

    public function testCronWithStartAndEnd(): void
    {
        $start = new DateTime('12:00');
        $end = new DateTime('14:00');
        $cron = (new Cron('0 * * * *'))
            ->startAt($start)
            ->endAt($end);

        // One hour before the start date
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, new DateTime('11:00'))->isPending());

        // Exactly the start date (start date is inclusive, so it's ready)
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, $start)->isReady());

        // Between start and end date
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, new DateTime('13:00'))->isReady());

        // Exactly the end date (end date is inclusive, so it's ready)
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, $end)->isReady());

        // One hour after the end date
        $this->assertTrue(FrequencyStatus::fromFrequency($cron, new DateTime('15:00'))->isExpired());
    }

    public function testOneOff(): void
    {
        $start = new DateTime('12:00');
        $oneOff = new OneOff($start);

        // One hour before the scheduled time
        $this->assertTrue(FrequencyStatus::fromFrequency($oneOff, (clone $start)->modify('-1 hour'))->isPending());

        // Exactly the scheduled time
        $this->assertTrue(FrequencyStatus::fromFrequency($oneOff, $start)->isReady());

        // One hour after the scheduled time (no end date, so still ready)
        $this->assertTrue(FrequencyStatus::fromFrequency($oneOff, (clone $start)->modify('+1 hour'))->isReady());
    }
}
