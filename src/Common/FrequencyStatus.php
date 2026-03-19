<?php

namespace ipl\Scheduler\Common;

use DateTimeInterface;
use ipl\Scheduler\Contract\Frequency;

/**
 * Represents the lifecycle status of a frequency based on its start and end date
 */
enum FrequencyStatus
{
    /** The frequency has a start date which has not been reached yet */
    case PENDING;

    /** The start date has already been reached, and the end date has not been reached yet */
    case READY;

    /** The frequency has an end date which has already been reached */
    case EXPIRED;

    /**
     * Get the frequency status of the frequency to determine if it's ready to run
     *
     * @param Frequency $frequency
     * @param DateTimeInterface $dateTime The reference time to check the frequency's start and end dates against
     *
     * @return self
     */
    public static function fromFrequency(Frequency $frequency, DateTimeInterface $dateTime): self
    {
        // If the frequency has no end date, it will never expire.
        if ($frequency->getEnd() !== null && $frequency->getEnd() < $dateTime) {
            return self::EXPIRED;
        }

        // If the frequency has no start date, it will never be pending.
        if ($frequency->getStart() !== null && $frequency->getStart() > $dateTime) {
            return self::PENDING;
        }

        return self::READY;
    }

    /**
     * Get whether the frequency is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Get whether the frequency is ready
     *
     * @return bool
     */
    public function isReady(): bool
    {
        return $this === self::READY;
    }

    /**
     * Get whether the frequency is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this === self::EXPIRED;
    }
}
