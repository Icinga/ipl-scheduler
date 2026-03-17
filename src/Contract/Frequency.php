<?php

namespace ipl\Scheduler\Contract;

use DateTimeInterface;
use JsonSerializable;

interface Frequency extends JsonSerializable
{
    /** @var string Format for representing datetimes when serializing the frequency to JSON */
    public const SERIALIZED_DATETIME_FORMAT = 'Y-m-d\TH:i:s.ue';

    /**
     * Get whether the frequency is due.
     *
     * If $lastRun is set, the last run time is used to determine if the frequency is due at the
     * specified $dateTime or was due since the last run. This can be used to catch up missed runs.
     *
     * To only check if the frequency is due at the specified $dateTime, regardless of the last run time,
     * pass the same time to both params.
     *
     * This method should only be called if $dateTime falls within the scheduled frequency window,
     * i.e., the {@see FrequencyStatus} is {@see FrequencyStatus::READY}.
     *
     * @param DateTimeInterface $dateTime
     * @param ?DateTimeInterface $lastRun If null is provided, the frequency is definitely due
     *
     * @return bool
     */
    public function isDue(DateTimeInterface $dateTime, ?DateTimeInterface $lastRun = null): bool;

    /**
     * Get the next due date relative to the given time
     *
     * @param DateTimeInterface $dateTime
     *
     * @return DateTimeInterface
     */
    public function getNextDue(DateTimeInterface $dateTime): DateTimeInterface;

    /**
     * Get the start time of this frequency
     *
     * @return ?DateTimeInterface
     */
    public function getStart(): ?DateTimeInterface;

    /**
     * Get the end time of this frequency
     *
     * @return ?DateTimeInterface
     */
    public function getEnd(): ?DateTimeInterface;

    /**
     * Create frequency from its stored JSON representation previously encoded with {@see json_encode()}
     *
     * @param string $json
     *
     * @return $this
     */
    public static function fromJson(string $json): static;
}
