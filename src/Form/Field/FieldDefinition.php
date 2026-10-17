<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use InvalidArgumentException;

/**
 * Holds one field's unbound configuration in the compiled form tree.
 *
 * Blueprint declarations and builders create these records; binding combines
 * their options with per-call options before resolving field state.
 *
 * @internal Application code declares fields through controls or blueprints.
 */
final readonly class FieldDefinition
{
    /**
     * Define a field by its dot-separated context path.
     *
     * @param array<string, mixed> $options Field behavior and presentation options.
     */
    public function __construct(public string $path, public array $options = [])
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+(?:\.[a-zA-Z0-9_-]+)*$/D', $path)) {
            throw new InvalidArgumentException('Use a nonempty dot-separated field path.');
        }
    }

    /**
     * Return a definition with explicit option overrides.
     *
     * @param array<string, mixed> $options Field options.
     */
    public function with(array $options): self
    {
        return new self($this->path, array_replace($this->options, $options));
    }
}
