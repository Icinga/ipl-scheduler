<?php

namespace ipl\Tests\Scheduler;

use DateTime;
use InvalidArgumentException;
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

    public function testGetWeekDayPart()
    {
        $cron = new Cron('* * * * SUN');

        $this->assertSame('SUN', $cron->getPart(Cron::PART_WEEKDAY));
    }

    public function testGetDayMonthPart()
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

        $this->assertTrue($cron->isDue());

        $cron->startAt(new DateTime('+1 week'));

        $this->assertFalse($cron->isDue());
    }

    public function testIsDueWithEndTime()
    {
        $cron = new Cron('* * * * *');
        $cron->endAt(new DateTime('-1 week'));

        $this->assertFalse($cron->isDue());
    }

    public function testGetNextDueWithStartTime()
    {
        $cron = new Cron('* * * * *');
        $now = new DateTime();
        // After resetting the seconds, we can just modify the expected next due like "+1 Minute"
        $now->setTime($now->format('H'), $now->format('i'));

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
}
