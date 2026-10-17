<?php
declare(strict_types=1);

namespace FormCompose\Field;

use BackedEnum;
use DateTimeInterface;
use FormCompose\Rendering\Attributes;
use InvalidArgumentException;
use Stringable;

/**
 * Formats bound PHP values for use in standard browser controls.
 *
 * NativeAttributes and NativeType use these conversions when preparing templates.
 * Backed enums use their backing values, date/time objects use HTML input formats,
 * false becomes the string zero, and unsupported composite values are rejected. Password and file inputs receive no
 * prefilled value. Escaping is performed when templates render the prepared value.
 */
final class NativeValue
{
    /**
     * Preserve false and zero while rejecting accidental non-scalar native input values.
     */
    public static function scalar(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value === false) {
            return '0';
        }

        if ($value !== null && !is_scalar($value) && !$value instanceof Stringable) {
            throw new InvalidArgumentException('Native input values must be scalar or stringable.');
        }

        return (string)$value;
    }

    /**
     * Format native date/time values and prevent password or file values from being echoed.
     */
    public static function input(FieldState $state, ?Attributes $attributes = null): ?string
    {
        if (in_array($state->type, ['file', 'password'], true) || $state->value === null) {
            return null;
        }

        if (in_array($state->type, ['date', 'month', 'week', 'time', 'datetime-local'], true)) {
            return TemporalValue::format($state, $attributes ?? new Attributes($state->constraints));
        }

        if ($state->value instanceof DateTimeInterface) {
            return $state->value->format('Y-m-d\TH:i:s');
        }

        return self::scalar($state->value);
    }
}
