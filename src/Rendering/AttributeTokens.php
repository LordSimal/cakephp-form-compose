<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

/**
 * Normalizes literal and conditional class names or ID references.
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
