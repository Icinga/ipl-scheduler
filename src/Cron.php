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

        return $this->getNextRunDate($dateTime);
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

    protected function getNextRunDate(DateTime $dateTime)
    {
        $nextDue = $this->cron->getNextRunDate($dateTime);
        if ($this->start === null) {
            return $nextDue;
        }

        $hours = $nextDue->format('H');
        $minutes = $nextDue->format('i');
        $seconds = $this->start->format('s');

        $expression = $this->cron->getExpression();
        $aliases = CronExpression::getAliases();
        if ($expression !== $aliases['@minutely']) {
            $minutes = $this->start->format('i');

            if ($expression !== $aliases['@hourly']) {
                $hours = $this->start->format('H');

                if ($expression !== $aliases['@daily']) {
                    $weekday = $this->getPart(static::PART_WEEKDAY);
                    $dayPart = $this->getPart(static::PART_DAY);
                    $monthPart = $this->getPart(static::PART_MONTH);
                    $weeklyRange = strpos($weekday, ',') !== false;

                    if (! $this->isOrdinal() && (! $weeklyRange || strpos($dayPart, ',') === false)) {
                        $dayPart = Str::trimSplit($dayPart, '/');
                        if (
                            ! $weeklyRange
                            && $expression === $aliases['@weekly']
                            || (
                                ! isset($dayPart[1])
                                && $monthPart === '*'
                                && $weekday === '*'
                            )
                        ) {
                            // If there is no a specific weekday selected, or it's an aliased
                            // weekly expression we can just forward to next day specified
                            // with the start time.
                            $nextDue->modify("next {$this->start->format('D')}");
                        }

                        $hourParts = Str::trimSplit($this->getPart(static::PART_HOUR), '/');
                        $isHourlyRange = isset($hourParts[1]);
                        if ($isHourlyRange && (int) $hourParts[1] > 24) {
                            $weeks = ($hourParts[1] / 7 / 24) - 1;
                            $nextDue->modify("+{$weeks} weeks");
                        }

                        if (
                            ! $isHourlyRange
                            && ! isset($dayPart[1])
                            && $weekday === '*'
                            && (
                                $expression === $aliases['@quarterly']
                                || $expression === $aliases['@annually']
                                || strpos($monthPart, '/') === false
                            )
                            && (
                                $dayPart[0] === '*'
                                || $dayPart[0] === '1'
                            )
                        ) {
                            $day = $this->start->format('j');
                            $nextDue->setDate($nextDue->format('Y'), $nextDue->format('m'), $day);
                        }
                    }
                }
            }
        }

        $nextDue->setTime($hours, $minutes, $seconds);

        return $nextDue;
    }

    /**
     * Get whether this cron has a specific day/weekday.. selected
     *
     * @return bool
     */
    protected function isOrdinal(): bool
    {
        $day = $this->getPart(static::PART_DAY);
        $weekday = $this->getPart(static::PART_WEEKDAY);
        if ((int) $day >= 1 && $weekday === '?') { // On the first/second/third... day
            return true;
        }

        if ($day === '?' && strpos($weekday, '#') !== false) { // On the first/second/third.. monday
            return true;
        }

        if (strpos($weekday, 'W') && $weekday === '*') { // On the first/second/third.. weekday
            return true;
        }

        return strpos($weekday, 'L') !== false && $day === '?' || ($day === 'L' && $weekday === '*');
    }
}
