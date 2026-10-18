<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use Closure;
use FormCompose\Builder\EmbeddedBlueprintBuilder;
use FormCompose\Builder\FieldConfigBuilder;
use FormCompose\Builder\PresentationBuilder;
use FormCompose\Builder\RepeatedBlueprintBuilder;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Authors ordered field and embed declarations during one blueprint hook.
 * Child compilation is deferred until build(); qualified parent edits then overlay
 * compiled children. No binding, rendering, or submission state is stored here.
 */
class BlueprintBuilder
{
    use ChildDeclarationsTrait;

    /** @var array<string, FieldConfigBuilder|EmbeddedBlueprintBuilder|RepeatedBlueprintBuilder|EmbeddedDefinition|RepeatedDefinition> Ordered declarations. */
    protected array $entries = [];

    /** @var array<string, FieldConfigBuilder> Qualified child overrides. */
    protected array $edits = [];

    /** @var list<string> Explicit removals. */
    protected array $removed = [];

    /** @var list<string> Omission history retained from a compiled seed. */
    protected array $omitted = [];

    protected readonly PresentationBuilder $presentation;

    /**
     * @param \Closure(string, string): FormDefinition $resolver Scoped mount and blueprint identifier.
     * @param \Closure(RepeatedDefinition): RepeatedDefinition $repeated Scoped repeated compiler.
     */
    public function __construct(
        FormDefinition $definition,
        protected readonly Closure $resolver,
        protected readonly Closure $repeated,
    ) {
        foreach ($definition->entries as $entry) {
            $this->entries[$entry->path] = $entry instanceof FieldDefinition
                ? $this->createFieldBuilder($entry->config)
                : $entry;
        }

        $this->omitted = $definition->removed;
        $this->presentation = new PresentationBuilder($definition->presentation);
    }

    /** Append an attached field builder; duplicate declarations fail immediately. */
    public function add(string $path, FieldConfig|FieldConfigBuilder|null $config = null): FieldConfigBuilder
    {
        $this->assertAvailable($path);

        return $this->entries[$path] = $this->createFieldBuilder(FieldConfig::capture($config));
    }

    /** Edit a declared field or stage a qualified child override, validated during build(). */
    public function field(string $path): FieldConfigBuilder
    {
        $entry = $this->entries[$path] ?? null;
        if ($entry instanceof FieldConfigBuilder) {
            return $entry;
        }

        if ($entry !== null) {
            throw new InvalidArgumentException('Cannot configure an embed as a field: ' . $path);
        }

        return $this->edits[$path] ??= $this->createFieldBuilder(new FieldConfig());
    }

    /** Stage a removal, including qualified children, without rendering anything. */
    public function remove(string $path): static
    {
        $this->removed[] = $path;

        return $this;
    }

    /** Configure this blueprint's layout through the common presentation API. */
    public function presentation(): PresentationBuilder
    {
        return $this->presentation;
    }

    /** Compile children, apply parent edits, and return an independent immutable tree. */
    public function build(): FormDefinition
    {
        $entries = [];
        $removed = [];
        foreach ($this->entries as $path => $entry) {
            if (in_array($path, $this->removed, true)) {
                $removed[] = $path;
                continue;
            }

            $entries[] = match (true) {
                $entry instanceof FieldConfigBuilder => new FieldDefinition($path, $entry->config()),
                $entry instanceof EmbeddedBlueprintBuilder,
                $entry instanceof RepeatedBlueprintBuilder,
                    => $entry->build(),
                default => $entry,
            };
        }

        $definition = new FormDefinition($entries, $this->presentation->config(), [...$this->omitted, ...$removed]);
        foreach ($this->edits as $path => $edit) {
            $definition = $definition->with($path, $edit->config());
        }

        foreach (array_unique(array_diff($this->removed, $removed)) as $path) {
            $definition = $definition->remove($path);
        }

        return $definition;
    }

    /** Supply field authoring behavior through a protected factory. */
    protected function createFieldBuilder(FieldConfig $config): FieldConfigBuilder
    {
        return new FieldConfigBuilder($config);
    }

    /** Validate names before adding declarations or running application hooks. */
    protected function assertAvailable(string $path): void
    {
        new FieldDefinition($path);
        if (isset($this->entries[$path]) && !in_array($path, $this->removed, true)) {
            throw new InvalidArgumentException('Blueprint declarations cannot contain duplicate field paths or embed paths: '
                . $path);
        }

        $this->removed = array_values(array_diff($this->removed, [$path]));
    }
}
