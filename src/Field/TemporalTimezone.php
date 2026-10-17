<?php
declare(strict_types=1);

namespace FormCompose\Field;

use Cake\Chronos\ChronosDate;
use Cake\Chronos\ChronosTime;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Normalizes explicit timezone configuration before temporal values are formatted.
 *
 * TemporalValue uses this utility to accept names and DateTimeZone objects while
 * reporting invalid application configuration through a consistent exception.
 */
final class TemporalTimezone
{
    /**
     * Convert complete date/time objects without mutating application-owned values.
     *
     * @throws \InvalidArgumentException When timezone conversion lacks a complete date and time.
     */
    public static function convert(
        ChronosDate|ChronosTime|DateTimeInterface $value,
        ?DateTimeZone $timezone,
    ): ChronosDate|ChronosTime|DateTimeInterface {
        if (!$timezone instanceof DateTimeZone) {
            return $value;
        }

        if (!$value instanceof DateTimeInterface) {
            throw new InvalidArgumentException('Date-only and time-only values cannot change timezone.');
        }

        return DateTimeImmutable::createFromInterface($value)->setTimezone($timezone);
    }

    /**
     * Resolve an optional timezone without using the process's default timezone.
     *
     * @throws \InvalidArgumentException For unsupported values or timezone names.
     */
    public static function resolve(mixed $timezone): ?DateTimeZone
    {
        if ($timezone === null || $timezone instanceof DateTimeZone) {
            return $timezone;
        }

        if (!is_string($timezone)) {
            throw new InvalidArgumentException('The timezone option must be a timezone name or DateTimeZone.');
        }

        try {
            return new DateTimeZone($timezone);
        } catch (DateInvalidTimeZoneException $dateInvalidTimeZoneException) {
            throw new InvalidArgumentException(
                'Invalid temporal input timezone.',
                code: 0,
                previous: $dateInvalidTimeZoneException,
            );
        }
    }
}
