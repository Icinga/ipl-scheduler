<?php

namespace ipl\Tests\Scheduler\Lib;

use DateTimeInterface;
use ipl\Scheduler\Contract\Frequency;
use LogicException;

abstract class BaseTestFrequency implements Frequency
{
    public function isDue(DateTimeInterface $dateTime): bool
    {
        return true;
    }

    public function isExpired(DateTimeInterface $dateTime): bool
    {
        return false;
    }

    public function getStart(): ?DateTimeInterface
    {
        return null;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return null;
    }

    public static function fromJson(string $json): Frequency
    {
        throw new LogicException('Not implemented');
    }

    public function jsonSerialize()
    {
        throw new LogicException('Not implemented');
    }
}
