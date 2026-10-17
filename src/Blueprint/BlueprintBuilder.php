<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use Closure;
use FormCompose\Form\Field\FieldDefinition;

/**
 * Mutably assembles configuration during one blueprint's configure() hook.
 *
 * Operations replace an immutable FormDefinition internally. build() returns a
 * snapshot that subsequent builder changes cannot modify. No values or rendering
 * state are kept here. Subclasses may specialize operations used by a factory.
 */
class BlueprintBuilder
{
    /**
     * Assemble one blueprint with its required scoped compilation collaborators.
     *
     * @param \Closure(string, string): \FormCompose\Blueprint\FormDefinition $resolver Mount path and blueprint identifier.
     * @param \Closure(\FormCompose\Blueprint\RepeatedDefinition): \FormCompose\Blueprint\RepeatedDefinition $repeated Compile one repeated occurrence.
     */
    public function __construct(
        protected FormDefinition $definition,
        protected readonly Closure $resolver,
        protected readonly Closure $repeated,
    ) {
    }

    /**
     * Append a field; duplicate paths are rejected.
     *
     * @param array<string, mixed> $options Field configuration.
     */
    public function add(string $path, array $options = []): static
    {
        $this->definition = $this->definition->add(new FieldDefinition($path, $options));

        return $this;
    }

    /**
     * Append a child blueprint in the current hook's scoped context.
     *
     * @param array<string, mixed> $options Occurrence presentation.
     */
    public function embed(string $path, string $blueprint, array $options = []): static
    {
        $child = ($this->resolver)($path, $blueprint);
        $this->definition = $this->definition->embed($path, $child, $options);

        return $this;
    }

    /**
     * Append repeated rows and make their qualified fields available to the current hook.
     *
     * @param list<int|string>|null $keys Explicit row keys, otherwise inferred.
     * @param array<string, mixed> $options Repeated-embed presentation.
     */
    public function embedMany(
        string $path,
        string $blueprint,
        ?array $keys = null,
        array $options = [],
    ): static {
        $compiled = ($this->repeated)(new RepeatedDefinition($path, $blueprint, $keys, $options));
        $this->definition = $this->definition->add($compiled);

        return $this;
    }

    /**
     * Select presentation for this blueprint's layout.
     *
     * @param array<string, mixed> $options Blueprint layout presentation.
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
     *
     * @internal Compiler snapshot; applications configure the supplied builder.
     */
    public function build(): FormDefinition
    {
        return $this->definition;
    }
}
