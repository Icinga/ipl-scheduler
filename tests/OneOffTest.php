<?php

namespace ipl\Tests\Scheduler;

use DateTime;
use DateTimeZone;
use ipl\Scheduler\OneOff;
use PHPUnit\Framework\TestCase;

class OneOffTest extends TestCase
{
    public function testIsDue()
    {
        $start = new DateTime();
        $oneOff = new OneOff($start);

        $now = $start;
        // No last run provided (null), so the frequency should definitely be due per the interface contract
        $this->assertTrue($oneOff->isDue($now));

        // Last run is provided ($start). Because a one-off should only be due once,
        // it should not be due again if a last run is provided.
        $now = new DateTime();
        $this->assertFalse($oneOff->isDue($now, $start));
    }

    public function testGetNextDue()
    {
        $now = new DateTime();
        $oneOff = new OneOff($now);

        $this->assertEquals($now, $oneOff->getNextDue(clone $now));
    }

    public function testJsonSerialize()
    {
        $now = new DateTime();
        $oneOff = new OneOff($now);

        $fromJson = OneOff::fromJson(json_encode($oneOff));

        $this->assertEquals($oneOff, $fromJson);
        $this->assertEquals($now, $fromJson->getStart());
    }

    public function testSerializeAndDeserializeHandleTimezonesCorrectly()
    {
        $oldTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $dateTime = new DateTime('01-06-2023T12:00:00', new DateTimeZone('America/New_York'));
            $oneOff = OneOff::fromJson(json_encode(new OneOff($dateTime)));

            $this->assertEquals(
                new DateTime('2023-06-01T18:00:00'),
                $oneOff->getStart(),
                'OneOff::jsonSerialize() does not restore the start date with a time zone correctly'
            );
        } finally {
            date_default_timezone_set($oldTz);
        }
    }
}
