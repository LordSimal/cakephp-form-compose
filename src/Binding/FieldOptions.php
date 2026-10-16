<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use BackedEnum;
use InvalidArgumentException;

/**
 * Checks built-in configuration bags before binding while leaving extension keys open.
 *
 * FieldResolver applies this after merging definitions and local options. This
 * validates configuration structure only; CakePHP owns submitted-data validation.
 */
final class FieldOptions
{
    /**
     * Reject malformed core bags before conventions or templates consume them.
     *
     * @param array<string, mixed> $options Merged field configuration.
     */
    public static function validate(array $options): void
    {
        foreach (['attributes', 'partAttributes', 'templates'] as $key) {
            if (!array_key_exists($key, $options)) {
                continue;
            }

            /** @var mixed $bag */
            $bag = $options[$key];
            if (!is_array($bag)) {
                throw new InvalidArgumentException('The ' . $key . ' option must be an array.');
            }

            self::validateBag($key, $bag);
        }

        if (isset($options['options'])) {
            /** @var mixed $choices */
            $choices = $options['options'];
            if (!is_iterable($choices) && (!is_string($choices) || !is_subclass_of($choices, BackedEnum::class))) {
                throw new InvalidArgumentException('The options option must be iterable or a backed enum class name.');
            }
        }
    }

    /**
     * Check keys and nested contracts without restricting custom attribute values or part names.
     *
     * @param array<array-key, mixed> $bag A built-in configuration bag.
     */
    private static function validateBag(string $key, array $bag): void
    {
        /** @var mixed $value */
        foreach ($bag as $name => $value) {
            if (!is_string($name) || $name === '') {
                throw new InvalidArgumentException('The ' . $key . ' option must use nonempty string keys.');
            }

            if ($key === 'partAttributes') {
                if (!is_array($value)) {
                    throw new InvalidArgumentException('Each partAttributes entry must be an attribute array.');
                }

                self::validateBag('attributes', $value);
            }

            if ($key === 'templates' && (!is_string($value) || $value === '')) {
                throw new InvalidArgumentException('Each templates entry must be a nonempty element name.');
            }
        }
    }
}
