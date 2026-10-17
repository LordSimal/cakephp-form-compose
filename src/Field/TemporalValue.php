<?php
declare(strict_types=1);

namespace FormCompose\Field;

use Cake\Chronos\ChronosDate;
use Cake\Chronos\ChronosTime;
use DateTimeImmutable;
use DateTimeInterface;
use FormCompose\Rendering\Attributes;
use InvalidArgumentException;

/**
 * Formats temporal values for native date, month, week, time, and datetime inputs.
 *
 * NativeValue delegates temporal controls here so CakePHP's localized Date and
 * Time string representations never become input values. Date/time objects use
 * machine formats, integer timestamps use UTC unless a timezone is configured,
 * and strings retain their submitted wall-clock representation. Fractional steps
 * render milliseconds, the precision supported by native browser inputs.
 */
final class TemporalValue
{
    /**
     * Format objects without mutation and preserve submitted strings without parsing.
     *
     * @throws \InvalidArgumentException For incompatible objects or invalid timezones.
     */
    public static function format(FieldState $state, Attributes $attributes): string
    {
        $timezone = TemporalTimezone::resolve($state->options['timezone'] ?? null);
        /** @var mixed $value */
        $value = $state->value;
        if (is_int($value)) {
            $value = new DateTimeImmutable('@' . $value);
        }

        if ($value instanceof ChronosDate || $value instanceof ChronosTime) {
            $compatible = $value instanceof ChronosDate
                ? in_array($state->type, ['date', 'month', 'week'], true)
                : $state->type === 'time';
            if (!$compatible) {
                throw new InvalidArgumentException('Use a date/time value compatible with the temporal input type.');
            }
        } elseif (!$value instanceof DateTimeInterface) {
            return NativeValue::scalar($value);
        }

        $value = TemporalTimezone::convert($value, $timezone);
        /** @var mixed $step */
        $step = $attributes->values['step'] ?? null;
        $fractional = $step === 'any' || (is_numeric($step) && (float)$step > 0 && fmod((float)$step, 1.0) !== 0.0);
        $seconds = $fractional ? 's.v' : 's';
        $formats = [
            'date' => 'Y-m-d',
            'month' => 'Y-m',
            'week' => 'o-\WW',
            'time' => 'H:i:' . $seconds,
        ];
        $format = $formats[$state->type] ?? 'Y-m-d\TH:i:' . $seconds;

        return $value->format($format);
    }
}
