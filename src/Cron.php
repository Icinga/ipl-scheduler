<?php

namespace ipl\Scheduler;

use Cron\CronExpression;
use DateTime;
use InvalidArgumentException;
use ipl\Scheduler\Contract\Frequency;
use ipl\Stdlib\Str;

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

        $this->alignExpressionParts($start);

        return $this;
    }

    /**
     * Modify the expression parts based on the given date time
     *
     * @param DateTime $time
     *
     * @return $this
     */
    public function alignExpressionParts(DateTime $time): self
    {
        $expression = $this->cron->getExpression();
        $aliases = CronExpression::getAliases();
        if ($expression !== $aliases['@minutely']) {
            $this->cron->setPart(static::PART_MINUTE, $time->format('i'));

            $parts = Str::trimSplit($this->getPart(static::PART_HOUR), '/');
            if ($parts[0] === '*' || $parts[0] === '0') {
                $part = $time->format('H');
                if (isset($parts[1]) && $parts[1] !== '1') {
                    $part = $time->format('H') . '/' . $parts[1];
                }

                $this->cron->setPart(static::PART_HOUR, $part);
            }

            if ($expression !== $aliases['@daily']) {
                $parts = Str::trimSplit($this->getPart(static::PART_DAY), '/');
                if ($parts[0] === '*' || $parts[0] === '1') {
                    $part = $time->format('j');
                    if (isset($parts[1]) && $parts[1] !== '1') {
                        $part = $time->format('j') . '/' . $parts[1];
                    }

                    $this->cron->setPart(static::PART_DAY, $part);
                }

                if ($expression !== $aliases['@monthly']) {
                    $parts = Str::trimSplit($this->getPart(static::PART_MONTH), '/');
                    if (! isset($parts[1]) && ($parts[0] === '*' || $parts[0] === '1')) {
                        // Cron expression doesn't allow to run every N months on a specific month, which is fine
                        $this->cron->setPart(static::PART_MONTH, $time->format('n'));
                    }

                    $weekDay = $this->getPart(static::PART_WEEKDAY);
                    if ($weekDay === '*') {
                        $this->cron->setPart(static::PART_WEEKDAY, $time->format('w'));
                    }
                }
            }
        }

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
