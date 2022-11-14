<?php

namespace ipl\Scheduler\Contract;

use DateTime;

interface Frequency
{
    /**
     * Get whether the frequency is due at the specified time
     *
     * @param DateTime $dateTime
     *
     * @return bool
     */
    public function isDue(DateTime $dateTime): bool;

    /**
     * Get the next due date relative to the given time
     *
     * @param DateTime $dateTime
     *
     * @return DateTime
     */
    public function getNextDue(DateTime $dateTime): DateTime;

    /**
     * Get whether the specified time is beyond the frequency's expiry time
     *
     * @param DateTime $dateTime
     *
     * @return bool
     */
    public function isExpired(DateTime $dateTime): bool;
}
