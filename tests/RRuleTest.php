<?php

namespace ipl\Tests\Scheduler;

use DateTime;
use ipl\Scheduler\Contract\Frequency;
use ipl\Scheduler\RRule;
use PHPUnit\Framework\TestCase;
use Recurr\Exception\InvalidRRule;

class RRuleTest extends TestCase
{
    public function testIsDueWithoutEndTime()
    {
        $start = new DateTime();
        $rrule = RRule::fromFrequency(RRule::MINUTELY)
            ->startAt($start);

        $start->setTime($start->format('H'), $start->format('i'), $start->format('s'));

        $this->assertTrue($rrule->isDue((clone $start)->modify('+1 minute')));
    }

    public function testIsDueWithEndTime()
    {
        $start = new DateTime();
        $start->setTime($start->format('H'), $start->format('i'), $start->format('s'));

        $end = clone $start;
        $end->modify('+2 minute');

        $rrule = RRule::fromFrequency(RRule::MINUTELY)
            ->startAt($start)
            ->endAt($end);

        $this->assertTrue($rrule->isDue((clone $start)->modify('+1 minute')));
        $this->assertFalse($rrule->isDue((clone $end)->modify('+1 minute')));
    }

    public function testIsExpiredWithoutEndTime()
    {
        $rrule = RRule::fromFrequency(RRule::MINUTELY)
            ->startAt(new DateTime());

        $this->assertFalse($rrule->isExpired(new DateTime('+1 day')));
    }

    public function testIsExpiredWithEndTime()
    {
        $rrule = RRule::fromFrequency(RRule::MINUTELY)
            ->startAt(new DateTime())
            ->endAt(new DateTime('+5 minute'));

        $this->assertFalse($rrule->isExpired(new DateTime('+2 minute')));
        $this->assertTrue($rrule->isExpired(new DateTime('+10 minute')));
    }

    public function testGetNextDueWithoutEndTime()
    {
        $start = new DateTime();
        $start->setTime($start->format('H'), $start->format('i'), $start->format('s'));

        $rrule = RRule::fromFrequency(RRule::MINUTELY)
            ->startAt($start);

        $this->assertEquals((clone $start)->modify('+1 minute'), $rrule->getNextDue($start));
    }

    public function testGetNextDueWithEndTime()
    {
        $end = new DateTime('+5 minutes');
        $end->setTime($end->format('H'), $end->format('i'), $end->format('s'));
        $rrule = RRule::fromFrequency(RRule::MINUTELY)
            ->startAt(new DateTime())
            ->endAt($end);

        $this->assertEquals($end, $rrule->getNextDue(new DateTime('+2 hours')));
    }

    public function testGetNextRecurrencesWithDefaultLimit()
    {
        $rrule = RRule::fromFrequency(RRule::MINUTELY)
            ->startAt(new DateTime('-1 hour'));

        $this->assertCount(1, iterator_to_array($rrule->getNextRecurrences(new DateTime())));
    }

    public function testGetNextRecurrencesWithCustomResultSetLimit()
    {
        $start = new DateTime('-2 day');
        $rrule = RRule::fromFrequency(RRule::MINUTELY)
            ->startAt($start);

        $this->assertCount(5, iterator_to_array($rrule->getNextRecurrences(new DateTime(), 5)));

        $start->modify('+2 day');
        $rrule->startAt($start);

        $this->assertCount(5, iterator_to_array($rrule->getNextRecurrences(new DateTime(), 5)));
    }

    public function testGetNextRecurrencesWithExpiredStartTime()
    {
        $rrule = RRule::fromFrequency(RRule::MINUTELY)
            ->endAt(new DateTime('-2 day'))
            ->startAt(new DateTime());

        $this->assertCount(0, iterator_to_array($rrule->getNextRecurrences(new DateTime(), 2)));
    }

    public function testMonthlyOn31DoesNotSkippFebruaryAndOtherMonths()
    {
        $start = new DateTime('31 January this year');
        if ($start < new DateTime()) {
            $start->modify('next year');
        }

        $recurrences = RRule::fromFrequency(RRule::MONTHLY)
            ->startAt($start)
            ->getNextRecurrences((clone $start)->modify('+5 days'));

        $this->assertEquals($start->modify('last day of next month'), $recurrences->current());
    }

    public function testQuarterlyFrequency()
    {
        $start = new DateTime('first day of next month');
        $start->setTime($start->format('H'), $start->format('i'), $start->format('s'));
        $recurrences = RRule::fromFrequency(RRule::QUARTERLY)
            ->startAt($start)
            ->getNextRecurrences((clone $start)->modify('+2 days'));

        $this->assertEquals($start->modify('+3 months'), $recurrences->current());
    }

    public function testJsonSerializeAndDeserialize()
    {
        $rrule = RRule::fromFrequency(RRule::QUARTERLY)
            ->startAt(new DateTime('tomorrow'))
            ->endAt(new DateTime('next week'));

        $this->assertEquals($rrule, RRule::fromJson(json_encode($rrule)));

        $rrule = RRule::fromFrequency(RRule::QUARTERLY)
            ->endAt(new DateTime('next week'));

        $this->assertEquals($rrule, RRule::fromJson(json_encode($rrule)));

        $rrule = RRule::fromFrequency(RRule::QUARTERLY);

        $this->assertEquals($rrule, RRule::fromJson(json_encode($rrule)));

        $rrule = RRule::fromFrequency(RRule::MINUTELY)
            ->startAt(new DateTime('tomorrow'))
            ->endAt(new DateTime('next week'));

        $this->assertEquals($rrule, RRule::fromJson(json_encode($rrule)));
    }
}
