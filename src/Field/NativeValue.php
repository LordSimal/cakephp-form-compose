<?php
declare(strict_types=1);

namespace FormCompose\Field;

use DateTimeInterface;
use InvalidArgumentException;
use Stringable;

/**
 * Converts resolved values into browser representations without reading request state.
 */
final class NativeValue
{
    /**
     * Preserve false and zero while rejecting accidental non-scalar native input values.
     */
    public static function scalar(mixed $value): string
    {
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
    public static function input(FieldState $state): ?string
    {
        if (in_array($state->type, ['file', 'password'], true) || $state->value === null) {
            return null;
        }
        if ($state->value instanceof DateTimeInterface) {
            $format = match ($state->type) {
                'date' => 'Y-m-d',
                'month' => 'Y-m',
                'time' => 'H:i:s',
                default => 'Y-m-d\TH:i:s',
            };

            return $state->value->format($format);
        }

        return self::scalar($state->value);
    }
}
