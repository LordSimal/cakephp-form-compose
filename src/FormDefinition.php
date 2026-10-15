<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Stores an immutable, ordered composition of fields and resolved embeds.
 *
 * entries preserves the rendering tree; fields exposes flattened, qualified
 * leaves for existing binding defaults and field lookup. Presentation selects
 * an optional PHP layout, without storing bound values or submission state.
 */
final readonly class FormDefinition
{
    /** @var list<\FormCompose\Field\FieldDefinition|\FormCompose\EmbeddedDefinition> Ordered relative children. */
    public array $entries;

    /** @var list<\FormCompose\Field\FieldDefinition> Flattened field configuration. */
    public array $fields;

    public DefinitionIndex $index;

    /**
     * Collect unique entries, retaining compatibility with flat collections.
     *
     * @param list<\FormCompose\Field\FieldDefinition|\FormCompose\EmbeddedDefinition> $fields Ordered entries.
     * @param array<string, mixed> $options Definition presentation options.
     * @param list<string> $removed Explicitly removed paths for conditional layouts.
     */
    public function __construct(array $fields, public array $options = [], public array $removed = [])
    {
        $this->entries = array_values($fields);
        $this->index = new DefinitionIndex($this->entries, $removed);
        $this->fields = $this->index->fields;
    }

    /**
     * Start an empty collection for fluent construction.
     */
    public static function make(): self
    {
        return new self([]);
    }

    /**
     * Append a field without changing the original collection.
     */
    public function add(FieldDefinition $field): self
    {
        return new self([...$this->entries, $field], $this->options, $this->removed);
    }

    /**
     * Append a resolved child at a relative association or indexed path.
     */
    public function embed(string $path, self $definition): self
    {
        return new self(
            [...$this->entries, new EmbeddedDefinition($path, $definition)],
            $this->options,
            $this->removed,
        );
    }

    /**
     * Override definition presentation without changing field configuration.
     *
     * @param array<string, mixed> $options Template, templates, templateData, variant, or attributes.
     */
    public function presentation(array $options): self
    {
        return new self($this->entries, array_replace($this->options, $options), $this->removed);
    }

    /**
     * Retrieve a field without binding or rendering it.
     *
     * @throws \InvalidArgumentException When the field is unavailable.
     */
    public function field(string $path): FieldDefinition
    {
        foreach ($this->fields as $field) {
            if ($field->path === $path) {
                return $field;
            }
        }

        throw new InvalidArgumentException(sprintf('Field "%s" is not in this definition.', $path));
    }

    /**
     * Retrieve an already compiled embed with local paths and presentation.
     */
    public function embedded(string $path): self
    {
        return (
            $this->index->embeds[$path]
                ?? throw new InvalidArgumentException(sprintf('Embed "%s" is not in this definition.', $path))
        );
    }

    /**
     * Check final field or embed membership without rendering side effects.
     */
    public function has(string $path): bool
    {
        return in_array($path, $this->index->paths, true);
    }

    /**
     * Remove a field or entire embed, retaining omitted control identities.
     *
     * @throws \InvalidArgumentException When the path is unavailable.
     */
    public function remove(string $path): self
    {
        if (!$this->has($path)) {
            throw new InvalidArgumentException(sprintf('Path "%s" is not in this definition.', $path));
        }

        $removed = isset($this->index->embeds[$path])
            ? array_values(array_filter(
                [...$this->index->paths, ...$this->index->removed],
                static fn(string $child): bool => str_starts_with($child, $path . '.'),
            ))
            : [];
        $entries = DefinitionEntries::change(
            $this->entries,
            $path,
            static fn(FieldDefinition|EmbeddedDefinition $entry): null => null,
            static fn(self $child, string $relative): self => $child->remove($relative),
        );

        return new self($entries, $this->options, [...$this->removed, $path, ...$removed]);
    }

    /**
     * Override a field, including an embedded leaf, without moving it.
     *
     * @param array<string, mixed> $options Field options, replacing matching top-level keys.
     * @throws \InvalidArgumentException When the field is unavailable.
     */
    public function with(string $path, array $options): self
    {
        $replacement = $this->field($path)->with($options);
        $entries = DefinitionEntries::change(
            $this->entries,
            $path,
            static fn(FieldDefinition|EmbeddedDefinition $entry): FieldDefinition => new FieldDefinition(
                $entry->path,
                $replacement->options,
            ),
            static fn(self $child, string $relative): self => $child->with($relative, $options),
        );

        return new self($entries, $this->options, $this->removed);
    }
}
