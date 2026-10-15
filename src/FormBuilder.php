<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Field\FieldDefinition;

/**
 * Mutably assembles configuration during one blueprint's configure() hook.
 *
 * Operations replace an immutable FormDefinition internally. build() returns a
 * snapshot that subsequent builder changes cannot modify. No values or rendering
 * state are kept here. Subclasses may specialize operations used by a factory.
 */
class FormBuilder
{
    /**
     * Start from the ordered attribute declarations.
     */
    public function __construct(protected FormDefinition $definition = new FormDefinition([]))
    {
    }

    /**
     * Append a field; duplicate paths are rejected.
     */
    public function add(FieldDefinition $field): static
    {
        $this->definition = $this->definition->add($field);

        return $this;
    }

    /**
     * Override an existing field's top-level options without moving it.
     *
     * @param array<string, mixed> $options Field overrides.
     */
    public function with(string $path, array $options): static
    {
        $this->definition = $this->definition->with($path, $options);

        return $this;
    }

    /**
     * Remove an existing field from the collection; unknown paths are rejected.
     */
    public function remove(string $path): static
    {
        $this->definition = $this->definition->remove($path);

        return $this;
    }

    /**
     * Return the current immutable configuration, independently of further changes.
     */
    public function build(): FormDefinition
    {
        return $this->definition;
    }
}
