<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

/**
 * Converts literal or conditional attribute tokens into a space-separated string.
 *
 * Attributes uses this for classes and merged ARIA ID references. Numeric array
 * entries supply tokens; named entries include their key when the value is truthy.
 * Array token lists are deduplicated, preserving their first occurrence.
 *
 * ```php
 * AttributeTokens::normalize(['control', 'invalid' => true, 'hidden' => false]);
 * // Returns 'control invalid'.
 * ```
 */
final class AttributeTokens
{
    /**
     * Normalize conditional or literal tokens without interpreting CSS utility conflicts.
     */
    public static function normalize(mixed $value): string
    {
        if (!is_array($value)) {
            return trim((string)$value);
        }
        $tokens = [];
        /** @var mixed $enabled */
        foreach ($value as $key => $enabled) {
            if (is_int($key)) {
                $tokens[] = self::normalize($enabled);
            } elseif ($enabled) {
                $tokens[] = $key;
            }
        }
        $parts = preg_split('/\s+/', trim(implode(' ', $tokens))) ?: [];

        return implode(' ', array_unique(array_filter($parts, static fn(string $part): bool => $part !== '')));
    }
}
