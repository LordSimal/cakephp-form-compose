<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use Closure;
use FormCompose\Builder\EmbeddedBlueprintBuilder;
use FormCompose\Builder\FieldDeclarationBuilder;
use FormCompose\Builder\RepeatedBlueprintBuilder;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Configuration\PresentationMethodsTrait;
use FormCompose\Configuration\ReplacementMethodsTrait;
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
    use PresentationMethodsTrait;
    use ReplacementMethodsTrait;

    /** @var array<string, FieldDeclarationBuilder|EmbeddedBlueprintBuilder|RepeatedBlueprintBuilder|EmbeddedDefinition|RepeatedDefinition> Ordered declarations. */
    protected array $entries = [];

    /** @var array<string, FieldDeclarationBuilder> Qualified child overrides. */
    protected array $edits = [];

    /** @var list<string> Explicit removals. */
    protected array $removed = [];

    /** @var list<string> Omission history retained from a compiled seed. */
    protected array $omitted = [];

    protected PresentationConfig $layout;

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
                ? $this->createFieldBuilder($entry->path, $entry->config)
                : $entry;
        }

        $this->omitted = $definition->removed;
        $this->layout = $definition->presentation;
    }

    /** Append an attached field builder; duplicate declarations fail immediately. */
    public function add(string $path, ?FieldConfig $config = null): FieldDeclarationBuilder
    {
        $this->assertAvailable($path);

        return $this->entries[$path] = $this->createFieldBuilder($path, $config ?? new FieldConfig());
    }

    /** Edit a declared field or stage a qualified child override, validated during build(). */
    public function field(string $path): FieldDeclarationBuilder
    {
        $entry = $this->entries[$path] ?? null;
        if ($entry instanceof FieldDeclarationBuilder) {
            return $entry;
        }

        if ($entry !== null) {
            throw new InvalidArgumentException('Cannot configure an embed as a field: ' . $path);
        }

        return $this->edits[$path] ??= $this->createFieldBuilder($path, new FieldConfig());
    }

    /** Stage a removal, including qualified children, without rendering anything. */
    public function remove(string $path): static
    {
        $this->removed[] = $path;

        return $this;
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
                $entry instanceof FieldDeclarationBuilder => $entry->build(),
                $entry instanceof EmbeddedBlueprintBuilder,
                $entry instanceof RepeatedBlueprintBuilder,
                    => $entry->build(),
                default => $entry,
            };
        }

        $definition = new FormDefinition($entries, $this->layout, [...$this->omitted, ...$removed]);
        foreach ($this->edits as $path => $edit) {
            $definition = $definition->with($path, $edit->config());
        }

        foreach (array_unique(array_diff($this->removed, $removed)) as $path) {
            $definition = $definition->remove($path);
        }

        return $definition;
    }

    /** Supply field authoring behavior through a protected factory. */
    protected function createFieldBuilder(string $path, FieldConfig $config): FieldDeclarationBuilder
    {
        return new FieldDeclarationBuilder($path, $config);
    }

    /** Read layout presentation without a separate configuration builder. */
    protected function currentPresentation(): PresentationConfig
    {
        return $this->layout;
    }

    /** Update the attached blueprint layout. */
    protected function updatePresentation(PresentationConfig $presentation): static
    {
        $this->layout = $presentation;

        return $this;
    }

    /** Blueprint presentation selects its collection control template. */
    protected function primaryPart(): string
    {
        return 'control';
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
