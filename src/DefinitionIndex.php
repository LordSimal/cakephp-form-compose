<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Indexes an immutable definition tree without resolving values or rendering.
 *
 * Includes embedded identities and removal history so layouts can distinguish
 * omitted fields from typos. Each child index remains relative to its own root.
 */
final readonly class DefinitionIndex
{
    /** @var list<\FormCompose\Field\FieldDefinition> Qualified leaves in presentation order. */
    public array $fields;

    /** @var array<string, \FormCompose\FormDefinition> Qualified embed paths to relative children. */
    public array $embeds;

    /** @var list<string> Active field and embed paths. */
    public array $paths;

    /** @var list<string> Explicitly omitted field and embed paths. */
    public array $removed;

    /**
     * Flatten child indexes and reject collisions across the whole tree.
     *
     * @param list<\FormCompose\Field\FieldDefinition|\FormCompose\EmbeddedDefinition> $entries Ordered children.
     * @param list<string> $removed Removal history local to this definition.
     */
    public function __construct(array $entries, array $removed)
    {
        $fields = [];
        $embeds = [];
        $paths = [];
        foreach ($entries as $entry) {
            $paths[] = $entry->path;
            if ($entry instanceof FieldDefinition) {
                $fields[] = $entry;
                continue;
            }

            $embeds[$entry->path] = $entry->definition;
            foreach ($entry->definition->index->embeds as $path => $child) {
                $embeds[$entry->path . '.' . $path] = $child;
            }

            foreach ($entry->definition->index->paths as $path) {
                $paths[] = $entry->path . '.' . $path;
            }

            foreach ($entry->definition->fields as $field) {
                $fields[] = new FieldDefinition($entry->path . '.' . $field->path, $field->options);
            }

            foreach ($entry->definition->index->removed as $path) {
                $removed[] = $entry->path . '.' . $path;
            }
        }

        if (count(array_unique($paths)) !== count($paths)) {
            throw new InvalidArgumentException('Form definitions cannot contain duplicate field paths or embed paths.');
        }

        $this->fields = $fields;
        $this->embeds = $embeds;
        $this->paths = $paths;
        $this->removed = array_values(array_unique($removed));
    }

    /**
     * Recognize removed controls without hiding newly added replacements.
     */
    public function wasRemoved(string $path): bool
    {
        return !in_array($path, $this->paths, true) && in_array($path, $this->removed, true);
    }
}
