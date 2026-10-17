<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use FormCompose\Form\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Indexes an immutable definition tree without resolving values or rendering.
 *
 * Includes embedded identities and removal history so layouts can distinguish
 * omitted fields from typos. Each child index remains relative to its own root.
 */
final readonly class DefinitionIndex
{
    /** @var list<\FormCompose\Form\Field\FieldDefinition> Qualified leaves in presentation order. */
    public array $fields;

    /** @var array<string, \FormCompose\Blueprint\FormDefinition> Qualified embed paths to relative children. */
    public array $embeds;

    /** @var array<string, array<string, mixed>> Presentation overrides per embed occurrence. */
    public array $embedOptions;

    /** @var array<string, \FormCompose\Blueprint\RepeatedDefinition> Repeated declarations and bound row snapshots. */
    public array $repeats;

    /** @var list<string> Active field and embed paths. */
    public array $paths;

    /** @var list<string> Explicitly omitted field and embed paths. */
    public array $removed;

    /**
     * Flatten child indexes and reject collisions across the whole tree.
     *
     * @param list<\FormCompose\Form\Field\FieldDefinition|\FormCompose\Blueprint\EmbeddedDefinition|\FormCompose\Blueprint\RepeatedDefinition> $entries Ordered children.
     * @param list<string> $removed Removal history local to this definition.
     */
    public function __construct(array $entries, array $removed)
    {
        $fields = [];
        $embeds = [];
        $paths = [];
        $repeats = [];
        $embedOptions = [];
        foreach ($entries as $entry) {
            $paths[] = $entry->path;
            if ($entry instanceof FieldDefinition) {
                $fields[] = $entry;
                continue;
            }

            if ($entry instanceof RepeatedDefinition) {
                $repeats[$entry->path] = $entry;
            }

            $childDefinition = $entry instanceof RepeatedDefinition ? $entry->rows : $entry->definition;
            if (!$childDefinition instanceof FormDefinition) {
                continue;
            }

            $embeds[$entry->path] = $childDefinition;
            $embedOptions[$entry->path] = $entry->options;
            $repeats += $this->qualify($childDefinition->index->repeats, $entry->path);
            $embedOptions += $this->qualify($childDefinition->index->embedOptions, $entry->path);
            $embeds += $this->qualify($childDefinition->index->embeds, $entry->path);
            array_push($paths, ...array_map(
                static fn(string $path): string => $entry->path . '.' . $path,
                $childDefinition->index->paths,
            ));
            array_push($fields, ...array_map(
                static fn(FieldDefinition $field): FieldDefinition => new FieldDefinition(
                    $entry->path . '.' . $field->path,
                    $field->options,
                ),
                $childDefinition->fields,
            ));
            array_push($removed, ...array_map(
                static fn(string $path): string => $entry->path . '.' . $path,
                $childDefinition->index->removed,
            ));
        }

        if (count(array_unique($paths)) !== count($paths)) {
            throw new InvalidArgumentException('Form definitions cannot contain duplicate field paths or embed paths.');
        }

        $this->fields = $fields;
        $this->embeds = $embeds;
        $this->embedOptions = $embedOptions;
        $this->repeats = $repeats;
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

    /**
     * Prefix a child map's keys while retaining its values and iteration order.
     *
     * @template T
     * @param array<array-key, T> $children Relative child metadata.
     * @return array<string, T>
     */
    private function qualify(array $children, string $prefix): array
    {
        $qualified = [];
        foreach ($children as $path => $child) {
            $qualified[$prefix . '.' . $path] = $child;
        }

        return $qualified;
    }
}
