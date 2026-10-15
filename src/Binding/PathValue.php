<?php
declare(strict_types=1);

namespace FormCompose\Binding;

/**
 * Presence-aware nested request value, including an explicitly submitted null.
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
