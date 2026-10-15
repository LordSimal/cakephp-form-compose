<?php
declare(strict_types=1);

namespace FormCompose\Field;

/**
 * Normalizes HTML input names for multiple selections and fallback fields.
 *
 * NativeAttributes adds one trailing [] for multiple values; ControlFallback needs
 * the name with that suffix removed. Nested brackets are preserved, so
 * base('comments[0][tags][]') returns 'comments[0][tags]'.
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
