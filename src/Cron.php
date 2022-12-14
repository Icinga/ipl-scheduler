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

    /** @var DateTime End time of this frequency */
    protected $end;

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

    public function isDue(DateTime $dateTime): bool
    {
        if ($this->isExpired($dateTime) || $dateTime < $this->start) {
            return false;
        }

        return $this->cron->isDue($dateTime);
    }

    public function getNextDue(DateTime $dateTime): DateTime
    {
        if ($this->isExpired($dateTime)) {
            return $this->end;
        }

        if ($dateTime < $this->start) {
            return $this->start;
        }

        return $this->cron->getNextRunDate($dateTime);
    }

    public function isExpired(DateTime $dateTime): bool
    {
        return $this->end !== null && $this->end < $dateTime;
    }

    /**
     * Set the start time of this frequency
     *
     * @param DateTime $start
     *
     * @return $this
     */
    public function startAt(DateTime $start): self
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Set the end time of this frequency
     *
     * @param DateTime $end
     *
     * @return $this
     */
    public function endAt(DateTime $end): Frequency
    {
        $this->end = $end;

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

    /**
     * Get whether the given cron expression is valid
     *
     * @param string $expression
     *
     * @return bool
     */
    public static function isValid(string $expression): bool
    {
        return CronExpression::isValidExpression($expression);
    }
}
