<?php

namespace ipl\Scheduler;

use BadMethodCallException;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use InvalidArgumentException;
use ipl\Scheduler\Contract\Frequency;
use Recurr\Exception\InvalidRRule;
use Recurr\Rule as RecurrRule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\ArrayTransformerConfig;
use Recurr\Transformer\Constraint\AfterConstraint;
use Recurr\Transformer\Constraint\BetweenConstraint;
use stdClass;

use function ipl\Stdlib\get_php_type;

/**
 * Support scheduling a task based on expressions in iCalendar format
 */
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

    /** @var int Default limit of the recurrences to be generated by the transformer */
    private const DEFAULT_LIMIT = 1;

    /** @var RecurrRule */
    protected $rrule;

    /** @var ArrayTransformer */
    protected $transformer;

    /** @var ArrayTransformerConfig */
    protected $transformerConfig;

    /** @var string */
    protected $frequency;

    /**
     * Construct a new rrule instance
     *
     * @param string|array<string, mixed> $rule
     *
     * @throws InvalidRRule
     */
    public function __construct($rule)
    {
        $this->rrule = new RecurrRule($rule);
        $this->frequency = $this->rrule->getFreqAsText();
        $this->transformerConfig = new ArrayTransformerConfig();
        $this->transformerConfig->setVirtualLimit(self::DEFAULT_LIMIT);

        // If the run day isn't set explicitly, we can enable the last day of month
        // fix, so that it doesn't skip some months which doesn't have e.g. 29,30,31 days.
        if (
            $this->getFrequency() === static::MONTHLY
            && ! $this->rrule->getByDay()
            && ! $this->rrule->getByMonthDay()
        ) {
            $this->transformerConfig->enableLastDayOfMonthFix();
        }

        $this->transformer = new ArrayTransformer($this->transformerConfig);
    }

    /**
     * Get an RRule instance from the provided frequency
     *
     * @param string $frequency
     *
     * @return $this
     */
    public static function fromFrequency(string $frequency): self
    {
        $frequencies = array_flip([
            static::MINUTELY,
            static::HOURLY,
            static::DAILY,
            static::WEEKLY,
            static::MONTHLY,
            static::QUARTERLY,
            static::YEARLY
        ]);

        if (! isset($frequencies[$frequency])) {
            throw new InvalidArgumentException(sprintf('Unknown frequency provided: %s', $frequency));
        }

        if ($frequency === static::QUARTERLY) {
            $repeat = static::MONTHLY;
            $rule = "FREQ=$repeat;INTERVAL=3";
        } else {
            $rule = "FREQ=$frequency";
        }

        $self = new static($rule);
        $self->frequency = $frequency;

        return $self;
    }

    public static function fromJson(string $json): Frequency
    {
        /** @var stdClass $data */
        $data = json_decode($json);
        $self = new static($data->rrule);
        $self->frequency = $data->frequency;
        if (isset($data->start)) {
            $start = DateTime::createFromFormat(static::SERIALIZED_DATETIME_FORMAT, $data->start);
            if (! $start) {
                throw new InvalidArgumentException(sprintf('Cannot deserialize start time: %s', $data->start));
            }

            $self->startAt($start);
        }

        return $self;
    }

    public function isDue(DateTimeInterface $dateTime): bool
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

    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface
    {
        if ($this->isExpired($dateTime)) {
            return $this->getEnd();
        }

        $nextDue = $this->getNextRecurrences($dateTime, 1, false);
        if (! $nextDue->valid()) {
            return $dateTime;
        }

        return $nextDue->current();
    }

    public function isExpired(DateTimeInterface $dateTime): bool
    {
        if ($this->rrule->repeatsIndefinitely()) {
            return false;
        }

        return $this->getEnd() !== null && $this->getEnd() < $dateTime;
    }

    /**
     * Set the start time of this frequency
     *
     * The given datetime will be cloned and microseconds removed since iCalendar datetimes only work to the second.
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
        // In case start time uses a different tz than what the rrule internally does, we force it to use the same
        $startDate->setTimezone(new DateTimeZone($this->rrule->getTimezone()));

        $this->rrule->setStartDate($startDate);

        return $this;
    }

    public function getStart(): ?DateTimeInterface
    {
        return $this->rrule->getStartDate();
    }

    /**
     * Set the time until this frequency lasts
     *
     * The given datetime will be cloned and microseconds removed since iCalendar datetimes only work to the second.
     *
     * @param DateTimeInterface $end
     *
     * @return $this
     */
    public function endAt(DateTimeInterface $end): self
    {
        $end = clone $end;
        $end->setTime($end->format('H'), $end->format('i'), $end->format('s'));

        $this->rrule->setUntil($end);

        return $this;
    }

    public function getEnd(): ?DateTimeInterface
    {
        return $this->rrule->getEndDate() ?? $this->rrule->getUntil();
    }

    /**
     * Get the frequency of this rule
     *
     * @return string
     */
    public function getFrequency(): string
    {
        return $this->frequency;
    }

    /**
     * Get a set of recurrences relative to the given time
     *
     * @param DateTimeInterface $dateTime
     * @param int $limit Limit the recurrences to be generated to the given value
     * @param bool $include Whether to include the passed time in the result set
     *
     * @return Generator<DateTimeInterface>
     */
    public function getNextRecurrences(
        DateTimeInterface $dateTime,
        int $limit = self::DEFAULT_LIMIT,
        bool $include = true
    ): Generator {
        $resetTransformerConfig = function (int $limit = self::DEFAULT_LIMIT): void {
            $this->transformerConfig->setVirtualLimit($limit);
            $this->transformer->setConfig($this->transformerConfig);
        };

        if ($limit > self::DEFAULT_LIMIT) {
            $resetTransformerConfig($limit);
        }

        $constraint = new AfterConstraint($dateTime, $include);
        if (! $this->rrule->repeatsIndefinitely()) {
            // When accessing this method externally (not by using `getNextDue()`), the transformer may
            // generate recurrences beyond the configured end time.
            $constraint = new BetweenConstraint($dateTime, $this->getEnd(), $include);
        }

        // Setting the start date to a date time smaller than now causes the underlying library
        // not to generate any recurrences when using the regular frequencies such as `MINUTELY` etc.
        // and the `$countConstraintFailures` is set to true. We need also to tell the transformer
        // not to count the recurrences that fail the constraint's test!
        $recurrences = $this->transformer->transform($this->rrule, $constraint, false);
        foreach ($recurrences as $recurrence) {
            yield $recurrence->getStart();
        }

        if ($limit > self::DEFAULT_LIMIT) {
            $resetTransformerConfig();
        }
    }

    public function jsonSerialize(): array
    {
        $data = [
            'rrule'     => $this->rrule->getString(RecurrRule::TZ_FIXED),
            'frequency' => $this->frequency
        ];

        $start = $this->getStart();
        if ($start) {
            $data['start'] = $start->format(static::SERIALIZED_DATETIME_FORMAT);
        }

        return $data;
    }

    /**
     * Redirect all public method calls to the underlying rrule object
     *
     * @param string $methodName
     * @param array<mixed> $args
     *
     * @return mixed
     *
     * @throws BadMethodCallException If the given method doesn't exist or when setter method is called
     */
    public function __call(string $methodName, array $args)
    {
        if (! method_exists($this->rrule, $methodName)) {
            throw new BadMethodCallException(
                sprintf('Call to undefined method %s::%s()', get_php_type($this->rrule), $methodName)
            );
        }

        if (strtolower(substr($methodName, 0, 3)) !== 'get') {
            throw new BadMethodCallException(
                sprintf('Dynamic method %s is not supported. Only getters (get*) are', $methodName)
            );
        }

        return call_user_func_array([$this->rrule, $methodName], $args);
    }
}
