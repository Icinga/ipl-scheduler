<?php

namespace ipl\Scheduler\Contract;

use DateTimeInterface;

interface Frequency
{
    /**
     * Get whether the frequency is due at the specified time
     *
     * @param DateTimeInterface $dateTime
     *
     * @return bool
     */
    public function isDue(DateTimeInterface $dateTime): bool;

    /**
     * Get the next due date relative to the given time
     *
     * @param DateTimeInterface $dateTime
     *
     * @return DateTimeInterface
     */
    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface;

    /**
     * Get whether the specified time is beyond the frequency's expiry time
     *
     * @param DateTimeInterface $dateTime
     *
     * @return bool
     */
    public function isExpired(DateTimeInterface $dateTime): bool;
}
