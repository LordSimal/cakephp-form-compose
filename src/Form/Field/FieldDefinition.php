<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Configuration\FieldConfig;
use InvalidArgumentException;

/**
 * Holds one field's unbound configuration in the compiled form tree.
 *
 * Blueprint declarations and builders create these records; binding combines
 * their settings with per-call configuration before resolving field state.
 *
 * @internal Application code declares fields through controls or blueprints.
 */
final readonly class FieldDefinition
{
    /**
     * Define a field by its dot-separated context path.
     *
     */
    public function __construct(public string $path, public FieldConfig $config = new FieldConfig())
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+(?:\.[a-zA-Z0-9_-]+)*$/D', $path)) {
            throw new InvalidArgumentException('Use a nonempty dot-separated field path.');
        }
    }

    /**
     * Return a definition with explicit configuration overrides.
     *
     */
    public function with(FieldConfig $config): self
    {
        return new self($this->path, $this->config->merge($config));
    }
}
