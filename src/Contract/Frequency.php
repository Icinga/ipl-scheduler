<?php

namespace ipl\Scheduler\Contract;

use DateTime;

interface Frequency
{
    /**
     * Get whether the frequency is due at the specified time, where now is the default
     *
     * @param ?DateTime $dateTime
     *
     * @return bool
     */
    public function isDue(DateTime $dateTime = null): bool;

    /**
     * Get the next due date relative to the given time
     *
     * @param DateTime $dateTime
     *
     * @return DateTime
     */
    public function getNextDue(DateTime $dateTime): DateTime;

    /**
     * Set the start time of this frequency
     *
     * @param DateTime $start
     *
     * @return $this
     */
    public function startAt(DateTime $start): self;
}
