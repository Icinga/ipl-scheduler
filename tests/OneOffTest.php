<?php

namespace ipl\Tests\Scheduler;

use DateTime;
use ipl\Scheduler\Contract\Frequency;
use ipl\Scheduler\OneOff;
use PHPUnit\Framework\TestCase;

class OneOffTest extends TestCase
{
    public function testIsDue()
    {
        $now = new DateTime();
        $oneOff = new OneOff($now);

        $this->assertTrue($oneOff->isDue($now));
        $this->assertFalse($oneOff->isDue(new DateTime()));
    }

    public function testGetNextDue()
    {
        $now = new DateTime();
        $oneOff = new OneOff($now);

        $this->assertEquals($now, $oneOff->getNextDue(clone $now));
    }

    public function testIsExpired()
    {
        $now = new DateTime();
        $oneOff = new OneOff($now);

        $this->assertFalse($oneOff->isExpired($now));
        $this->assertTrue($oneOff->isExpired(new DateTime()));
    }

    public function testJsonSerialize()
    {
        $now = new DateTime();
        $oneOff = new OneOff($now);

        $this->assertSame(
            sprintf('"%s"', $now->format(Frequency::SERIALIZED_DATETIME_FORMAT)),
            $oneOff->jsonSerialize()
        );
    }

    public function testFromJsonWithoutDateTime()
    {
        $this->expectException(\InvalidArgumentException::class);

        OneOff::fromJson('{}');
    }

    public function testFromJsonWithValidDateTime()
    {
        $now = new DateTime();
        $oneOff = OneOff::fromJson(sprintf('"%s"', $now->format(Frequency::SERIALIZED_DATETIME_FORMAT)));

        $this->assertEquals($now, $oneOff->getStart());
    }
}
