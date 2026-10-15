<?php
declare(strict_types=1);

namespace FormCompose\Field;

/**
 * Maintains valid bracket notation when preparing multiple and fallback inputs.
 */
final class InputNames
{
    /**
     * Remove one array suffix while retaining brackets belonging to nested paths.
     */
    public static function base(string $name): string
    {
        return str_ends_with($name, '[]') ? substr($name, 0, -2) : $name;
    }
}
