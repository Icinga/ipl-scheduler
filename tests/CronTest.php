<?php

namespace ipl\Tests\Scheduler;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use ipl\Scheduler\Contract\Frequency;
use ipl\Scheduler\Cron;
use PHPUnit\Framework\TestCase;

class CronTest extends TestCase
{
    public function testRegisteredCustomAliases()
    {
        $this->assertTrue(Cron::isValid('@minutely'));
        $this->assertTrue(Cron::isValid('@quarterly'));
    }

    public function testGetPartWithInvalidPartThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Cron('* * * * *'))->getPart(1000);
    }

    public function testGetMinutePart()
    {
        $cron = new Cron('0 * * * *');

        $this->assertSame('0', $cron->getPart(Cron::PART_MINUTE));
    }

    public function testGetHourPart()
    {
        $cron = new Cron('* 2 * * *');

        $this->assertSame('2', $cron->getPart(Cron::PART_HOUR));
    }

    public function testGetWeekdayPart()
    {
        $cron = new Cron('* * * * SUN');

        $this->assertSame('SUN', $cron->getPart(Cron::PART_WEEKDAY));
    }

    public function testGetDayPart()
    {
        $cron = new Cron('* * 20 * *');

        $this->assertEquals('20', $cron->getPart(Cron::PART_DAY));
    }

    public function testGetMonthPart()
    {
        $cron = new Cron('* * 20 FEB *');

        $this->assertEquals('FEB', $cron->getPart(Cron::PART_MONTH));
    }

    public function testIsDueWithStartTime()
    {
        $cron = new Cron('* * * * *');

        $this->assertTrue($cron->isDue(new DateTime()));

        $cron->startAt(new DateTime('+1 week'));

        $this->assertFalse($cron->isDue(new DateTime()));
    }

    public function testIsDueWithEndTime()
    {
        $cron = new Cron('* * * * *');
        $cron->endAt(new DateTime('-1 week'));

        $this->assertFalse($cron->isDue(new DateTime()));
    }

    public function testGetNextDueWithStartTime()
    {
        $cron = new Cron('* * * * *');
        $now = new DateTime();
        // After resetting the seconds, we can just modify the expected next due like "+1 Minute"
        $now->setTime($now->format('H'), $now->format('i'));

        $cron->startAt($now);

        $this->assertEquals((clone $now)->modify('+1 minute'), $cron->getNextDue($now));
    }

    public function testGetNextDueWithEndTime()
    {
        $cron = new Cron('* * * * *');
        $now = new DateTime();

        $cron->endAt($now);

        $this->assertEquals($now, $cron->getNextDue(new DateTime('+2 hours')));
    }

    public function testIsExpiredWithoutEndTime()
    {
        $this->assertFalse((new Cron('* * * * *'))->isExpired(new DateTime()));
    }

    public function testIsExpiredWithEndTime()
    {
        $cron = new Cron('* * * * *');
        $cron->endAt(new DateTime('-2 hours'));

        $this->assertTrue($cron->isExpired(new DateTime()));
    }

    public function testJsonSerializeAndDeserialize()
    {
        $start = new DateTime();
        $cron = new Cron('@minutely');
        $cron->startAt($start);

        $fromJson = Cron::fromJson(json_encode($cron));

        $this->assertSame('@minutely', $fromJson->getExpression());
        $this->assertEquals($start, $fromJson->getStart());

        $end = (clone $start)->modify('+2 weeks');
        $cron->endAt($end);

        $fromJson = Cron::fromJson(json_encode($cron));

        $this->assertSame('@minutely', $fromJson->getExpression());
        $this->assertEquals($start, $fromJson->getStart());
        $this->assertEquals($end, $fromJson->getEnd());
    }

    public function testSerializeAndDeserializeHandleTimezonesCorrectly()
    {
        $oldTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $start = new DateTime('01-06-2023T12:00:00', new DateTimeZone('America/New_York'));
            $end = new DateTime('01-06-2024T07:00:00', new DateTimeZone('America/New_York'));

            $cron = Cron::fromJson(
                json_encode(
                    (new Cron('@minutely'))
                        ->startAt($start)
                        ->endAt($end)
                )
            );

            $this->assertEquals(
                new DateTime('2023-06-01T18:00:00'),
                $cron->getStart(),
                'Cron::jsonSerialize() does not restore the start date with a time zone correctly'
            );

            $this->assertEquals(
                new DateTime('2024-06-01T13:00:00'),
                $cron->getEnd(),
                'Cron::jsonSerialize() does not restore the end date with a time zone correctly'
            );
        } finally {
            date_default_timezone_set($oldTz);
        }
    }
}
