<?php
declare(strict_types=1);

namespace FormCompose\Field;

/**
 * Describes one submitted name and its optional immutable hidden value.
 */
final readonly class Submission
{
    /**
     * Describe a normal field, locked hidden value, or explicitly unlocked integration field.
     */
    public function __construct(public string $name, public bool $locked = true, public ?string $value = null)
    {
    }
}
