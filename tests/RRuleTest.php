<?php

namespace ipl\Tests\Scheduler;

use DateTime;
use ipl\Scheduler\RRule;
use PHPUnit\Framework\TestCase;

class RRuleTest extends TestCase
{
    public function testIsDueWithoutEndTime()
    {
        $start = new DateTime();
        $rrule = new RRule('FREQ=MINUTELY', $start);

        $start->setTime($start->format('H'), $start->format('i'), $start->format('s'));

        $this->assertTrue($rrule->isDue((clone $start)->modify('+1 minute')));
    }

    public function testIsDueWithEndTime()
    {
        $start = new DateTime();
        $start->setTime($start->format('H'), $start->format('i'), $start->format('s'));

        $end = clone $start;
        $end->modify('+2 minute');

        $rrule = new RRule('FREQ=MINUTELY', $start);
        $rrule->endAt($end);

        $this->assertTrue($rrule->isDue((clone $start)->modify('+1 minute')));
        $this->assertFalse($rrule->isDue((clone $end)->modify('+1 minute')));
    }

    public function testIsExpiredWithoutEndTime()
    {
        $start = new DateTime();
        $rrule = new RRule('FREQ=MINUTELY', $start);

        $this->assertFalse($rrule->isExpired(new DateTime('+1 day')));
    }

    public function testIsExpiredWithEndTime()
    {
        $start = new DateTime();
        $rrule = new RRule('FREQ=MINUTELY', $start);
        $rrule->endAt(new DateTime('+5 minute'));

        $this->assertFalse($rrule->isExpired(new DateTime('+2 minute')));
        $this->assertTrue($rrule->isExpired(new DateTime('+10 minute')));
    }

    public function testGetNextDueWithoutEndTime()
    {
        $start = new DateTime();
        $start->setTime($start->format('H'), $start->format('i'), $start->format('s'));

        $rrule = new RRule('FREQ=MINUTELY', $start);

        $this->assertEquals((clone $start)->modify('+1 minute'), $rrule->getNextDue($start));
    }

    public function testGetNextDueWithEndTime()
    {
        $start = new DateTime();
        $end = new DateTime('+5 minutes');

        $rrule = new RRule('FREQ=MINUTELY', $start);
        $rrule->endAt($end);

        $this->assertEquals($end, $rrule->getNextDue(new DateTime('+2 hours')));
    }

    public function testGetNextRecurrencesWithDefaultLimit()
    {
        $start = new DateTime('-1 hour');
        $rrule = new RRule('FREQ=MINUTELY', $start);

        $this->assertCount(3, iterator_to_array($rrule->getNextRecurrences(new DateTime())));
    }

    public function testGetNextRecurrencesWithCustomResultSetLimit()
    {
        $start = new DateTime('-2 day');
        $rrule = new RRule('FREQ=MINUTELY', $start);
        $rrule->limitTransformer(5);

        $this->assertCount(5, iterator_to_array($rrule->getNextRecurrences(new DateTime())));

        $start->modify('+2 day');
        $rrule->startAt($start);

        $this->assertCount(5, iterator_to_array($rrule->getNextRecurrences(new DateTime())));
    }
}
