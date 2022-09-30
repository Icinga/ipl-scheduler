<?php

namespace ipl\Scheduler;

use Cron\CronExpression;
use DateTime;
use InvalidArgumentException;
use ipl\Scheduler\Contract\Frequency;

class Cron implements Frequency
{
    /** @var CronExpression */
    protected $cron;

    /** @var DateTime Start time of this frequency */
    protected $start;

    /**
     * Create frequency from the specified cron expression
     *
     * @param string $expression
     *
     * @throws InvalidArgumentException If expression is not a valid cron expression
     */
    public function __construct(string $expression)
    {
        $this->cron = new CronExpression($expression);
    }

    public function isDue(DateTime $dateTime = null): bool
    {
        if (! $dateTime) {
            $dateTime = new DateTime();
        }

        if ($dateTime < $this->start) {
            return false;
        }

        return $this->cron->isDue($dateTime);
    }

    public function getNextDue(DateTime $dateTime): DateTime
    {
        if ($dateTime < $this->start) {
            return $this->start;
        }

        return $this->cron->getNextRunDate($dateTime);
    }

    public function startAt(DateTime $start): Frequency
    {
        $this->start = $start;

        return $this;
    }
}
