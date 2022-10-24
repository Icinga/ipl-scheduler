<?php

namespace ipl\Scheduler;

use Cron\CronExpression;
use DateTime;
use InvalidArgumentException;
use ipl\Scheduler\Contract\Frequency;

class Cron implements Frequency
{
    public const PART_MINUTE = 0;
    public const PART_HOUR = 1;
    public const PART_DAY = 2;
    public const PART_MONTH = 3;
    public const PART_WEEKDAY = 4;

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

    /**
     * Get the given part of the underlying cron expression
     *
     * @param int $part One of the classes `PART_*` constants
     *
     * @return string
     *
     * @throws InvalidArgumentException If the given part is invalid
     */
    public function getPart(int $part): string
    {
        $value = $this->cron->getExpression($part);
        if ($value === null) {
            throw new InvalidArgumentException(sprintf('Invalid expression part specified: %d', $part));
        }

        return $value;
    }

    /**
     * Get the parts of the underlying cron expression as an array
     *
     * @return string[]
     */
    public function getParts(): array
    {
        return $this->cron->getParts();
    }
}
