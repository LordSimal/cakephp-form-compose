<?php
declare(strict_types=1);

namespace FormCompose;

use Closure;
use FormCompose\Field\FieldDefinition;
use LogicException;

/**
 * Mutably assembles configuration during one blueprint's configure() hook.
 *
 * Operations replace an immutable FormDefinition internally. build() returns a
 * snapshot that subsequent builder changes cannot modify. No values or rendering
 * state are kept here. Subclasses may specialize operations used by a factory.
 */
class FormBuilder
{
    /** @var \Closure(string, string): \FormCompose\FormDefinition|null Scoped blueprint construction. */
    protected ?Closure $resolver = null;

    /**
     * Supply scoped blueprint construction without replacing application builder subclasses.
     *
     * @param \Closure(string, string): \FormCompose\FormDefinition $resolver Mount path and identifier to configuration.
     */
    public function withResolver(Closure $resolver): static
    {
        $builder = clone $this;
        $builder->resolver = $resolver;

        return $builder;
    }

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
     * Append an association definition, resolving strings in the current hook's context.
     */
    public function embed(string $path, FormDefinition|string $definition): static
    {
        if (is_string($definition)) {
            if (!$this->resolver instanceof Closure) {
                throw new LogicException('Blueprint embedding requires a builder supplied by BlueprintFactory.');
            }

            $definition = ($this->resolver)($path, $definition);
        }

        $this->definition = $this->definition->embed($path, $definition);

        return $this;
    }

    /**
     * Select presentation for this resolved definition.
     *
     * @param array<string, mixed> $options Definition layout presentation.
     */
    public function presentation(array $options): static
    {
        $this->definition = $this->definition->presentation($options);

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
