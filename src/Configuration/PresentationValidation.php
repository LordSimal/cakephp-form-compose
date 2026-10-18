<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Rendering\Attributes;
use InvalidArgumentException;

/** Validates presentation data without evaluating values, templates, or choice sources. */
final class PresentationValidation
{
    /** Check dynamic part names and application-owned attribute maps. */
    public static function validate(PresentationConfig $config): void
    {
        /** @var array<array-key, mixed> $templates PHP does not enforce array generic types at runtime. */
        $templates = $config->templates;
        /** @var mixed $element */
        foreach ($templates as $part => $element) {
            if (!is_string($part) || $part === '' || !is_string($element) || $element === '') {
                throw new InvalidArgumentException('Template parts and element names must be nonempty strings.');
            }
        }

        new Attributes($config->attributes);
        /** @var array<array-key, array<string, mixed>> $parts */
        $parts = $config->partAttributes;
        foreach ($parts as $part => $attributes) {
            if (!is_string($part) || $part === '') {
                throw new InvalidArgumentException('Attribute parts must be nonempty strings.');
            }

            new Attributes($attributes);
        }
    }
}
