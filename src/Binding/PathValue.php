<?php
declare(strict_types=1);

namespace FormCompose\Binding;

/**
 * Represents a nested request lookup as both a presence flag and a value.
 *
 * ValueResolver uses found to distinguish a missing key from an explicitly
 * submitted null, false, zero, or empty string. Paths use dot notation.
 *
 * ```php
 * $result = PathValue::lookup(['profile' => ['name' => null]], 'profile.name');
 * // $result->found is true; $result->value is null.
 * ```
 */
final readonly class PathValue
{
    /**
     * Distinguish absence from the full range of submitted values.
     */
    public function __construct(public bool $found, public mixed $value = null)
    {
    }

    /**
     * Follow a dot path using key presence rather than isset semantics.
     *
     * @param array<array-key, mixed> $data Request data.
     */
    public static function lookup(array $data, string $path): self
    {
        $value = $data;
        foreach (explode('.', $path) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return new self(false);
            }
            /** @var mixed $value */
            $value = $value[$part];
        }

        return new self(true, $value);
    }
}
