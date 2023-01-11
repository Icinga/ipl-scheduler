<?php

namespace ipl\Scheduler;

use BadMethodCallException;
use DateTime;
use DateTimeInterface;
use Generator;
use ipl\Scheduler\Contract\Frequency;
use Recurr\Rule as RecurrRule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\ArrayTransformerConfig;
use Recurr\Transformer\Constraint\AfterConstraint;

use function ipl\Stdlib\get_php_type;

class RRule implements Frequency
{
    /** @var string Run once a year */
    public const YEARLY = 'YEARLY';

    /** @var string Run every 3 month starting from the given start time */
    public const QUARTERLY = 'QUARTERLY';

    /** @var string Run once a month */
    public const MONTHLY = 'MONTHLY';

    /** @var string Run once a week based on the specified start time */
    public const WEEKLY = 'WEEKLY';

    /** @var string Run once a day at the specified start time */
    public const DAILY = 'DAILY';

    /** @var string Run once an hour */
    public const HOURLY = 'HOURLY';

    /** @var string Run once a minute */
    public const MINUTELY = 'MINUTELY';

    /** @var int Maximum time difference between the current date and the start date in years */
    public const MAX_YEAR_DIFF = -5;

    /** @var RecurrRule */
    protected $rrule;

    /** @var ArrayTransformer */
    protected $transformer;

    public function __construct(string $rule, DateTimeInterface $start)
    {
        $this->rrule = new RecurrRule($rule);
        $this->transformer = new ArrayTransformer();

        $this->startAt($start);
        $this->limitTransformer(3);
    }

    public function isDue(DateTime $dateTime): bool
    {
        if ($dateTime < $this->rrule->getStartDate() || $this->isExpired($dateTime)) {
            return false;
        }

        $nextDue = $this->getNextRecurrences($dateTime);
        if (! $nextDue->valid()) {
            return false;
        }

        return $nextDue->current() == $dateTime;
    }

    public function getNextDue(DateTime $dateTime): DateTime
    {
        if ($this->isExpired($dateTime)) {
            return $this->rrule->getEndDate();
        }

        $nextDue = $this->getNextRecurrences($dateTime, false);
        if (! $nextDue->valid()) {
            return $dateTime;
        }

        return $nextDue->current();
    }

    public function isExpired(DateTime $dateTime): bool
    {
        if ($this->rrule->repeatsIndefinitely()) {
            return false;
        }

        $end = $this->rrule->getEndDate();
        $end = $end ?: $this->rrule->getUntil();

        return $end !== null && $end < $dateTime;
    }

    /**
     * Set the start time of this frequency
     *
     * @param DateTimeInterface $start
     *
     * @return $this
     */
    public function startAt(DateTimeInterface $start): self
    {
        $startDate = clone $start;
        // When the start time contains microseconds, the first recurrence will always be skipped, as
        // the transformer operates only up to seconds level. See also the upstream issue #155
        $startDate->setTime($start->format('H'), $start->format('i'), $start->format('s'));

        $now = new DateTime();
        $diff = (int) $now->diff($startDate)->format('%R%y');
        // The maximum time difference between the current time and the start time must not exceed 5 years,
        // otherwise the transformer would spend forever generating all the recurrences from the start time
        // till now pointlessly.
        if ($diff < static::MAX_YEAR_DIFF) {
            $startDate->setDate($now->format('Y'), $start->format('m'), $start->format('d'));
        }

        $this->rrule->setStartDate($startDate, true);

        return $this;
    }

    /**
     * Set the end time of this frequency
     *
     * @param DateTimeInterface $end
     *
     * @return $this
     */
    public function endAt(DateTimeInterface $end): self
    {
        $this->rrule->setEndDate($end);

        return $this;
    }

    /**
     * Get the RRULE as a string
     *
     * @return string
     */
    public function getRuleString(): string
    {
        return $this->rrule->getString();
    }

    /**
     * Get the frequency of this rule
     *
     * @return string
     */
    public function getRepeat(): string
    {
        return $this->rrule->getFreqAsText();
    }

    /**
     * Get a set of recurrences relative to the given time and bounded to the configured generator's limit
     *
     * @param DateTimeInterface $dateTime
     * @param bool $include Whether to include the passed time in the result set
     *
     * @return Generator
     */
    public function getNextRecurrences(DateTimeInterface $dateTime, bool $include = true): Generator
    {
        // Setting the start date to a date time smaller than now causes the underlying library
        // not to generate any recurrences when using the regular frequencies such as `MINUTELY` etc.
        // and the `$countConstraintFailures` is set to true. We need also to tell the transformer
        // not to count the recurrences that fail the constraint's test!
        $recurrences = $this->transformer->transform($this->rrule, new AfterConstraint($dateTime, $include), false);
        foreach ($recurrences as $recurrence) {
            yield $recurrence->getStart();
        }
    }

    /**
     * Limit the underlying recurrence generator to produce only up to the passed limit
     *
     * @param int $limit
     *
     * @return $this
     */
    public function limitTransformer(int $limit): self
    {
        // Limit the generated recurrences to the provided counts, otherwise, the transformer will
        // generate meaningless "732" recurrences each time by default
        $config = new ArrayTransformerConfig();
        $config->setVirtualLimit($limit);

        // If the run day isn't set explicitly, we can enable the last day of month
        // fix, so that it doesn't skip some months which doesn't have e.g. 29,30,31 days.
        if ($this->getRepeat() === static::MONTHLY && ! $this->rrule->getByDay() && ! $this->rrule->getByMonthDay()) {
            $config->enableLastDayOfMonthFix();
        }

        $this->transformer->setConfig($config);

        return $this;
    }

    /**
     * Redirect all public method calls to the underlying rrule object
     *
     * @param string $methodName
     * @param array $args
     *
     * @return mixed
     *
     * @throws BadMethodCallException If the given method doesn't exist
     */
    public function __call(string $methodName, array $args)
    {
        if (! method_exists($this->rrule, $methodName)) {
            throw new BadMethodCallException(
                sprintf('Call to undefined method %s::%s()', get_php_type($this->rrule), $methodName)
            );
        }

        return call_user_func_array([$this->rrule, $methodName], $args);
    }
}
