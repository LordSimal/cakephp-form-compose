<?php
declare(strict_types=1);

namespace FormCompose;

/**
 * Qualifies immutable child-index maps without changing their values or ordering.
 */
final class DefinitionChildren
{
    /**
     * Prefix keys while retaining typed configuration and presentation values.
     *
     * @template T
     * @param array<array-key, T> $children Relative child metadata.
     * @return array<string, T>
     */
    public static function qualify(array $children, string $prefix): array
    {
        $result = [];
        foreach ($children as $path => $child) {
            $result[$prefix . '.' . $path] = $child;
        }

        return $result;
    }
}
