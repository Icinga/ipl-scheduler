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

    /** The start date has already been reached, and the end date has not been reached yet. */
    case READY;

    /** The frequency has an end date which has already been reached */
    case EXPIRED;

    /**
     * Get the frequency status of the frequency to determine if it's ready to run.
     *
     * @param Frequency $frequency
     * @param DateTimeInterface $dateTime The reference time to check the frequency's start and end dates against
     *
     * @return self
     */
    public static function fromFrequency(Frequency $frequency, DateTimeInterface $dateTime): self
    {
        // If the end date of the frequency has already been reached, it's expired.
        // If the end date is null, it can't expire.
        if ($frequency->getEnd() !== null && $frequency->getEnd() < $dateTime) {
            return self::EXPIRED;
        }

        // If the start date of the frequency has not been reached yet, it's pending.
        // If the start date is null, it has no lower bound and is instantly ready.
        if ($frequency->getStart() !== null && $frequency->getStart() > $dateTime) {
            return self::PENDING;
        }

        // If none of the earlier checks have been triggered, the frequency is ready to run.
        return self::READY;
    }
}
