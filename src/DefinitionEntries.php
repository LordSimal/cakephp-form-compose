<?php
declare(strict_types=1);

namespace FormCompose;

use Closure;

/**
 * Applies immutable edits to a definition's ordered children.
 *
 * Traversal follows actual child membership, allowing non-overlapping explicit
 * dot paths alongside embeds. Child operations preserve their own removal history.
 */
final class DefinitionEntries
{
    /**
     * Change or remove one entry without moving neighboring entries.
     *
     * @param list<\FormCompose\Field\FieldDefinition|\FormCompose\EmbeddedDefinition> $entries Ordered children.
     * @param \Closure(\FormCompose\Field\FieldDefinition|\FormCompose\EmbeddedDefinition): (\FormCompose\Field\FieldDefinition|\FormCompose\EmbeddedDefinition|null) $change Direct entry operation.
     * @param \Closure(\FormCompose\FormDefinition, string): \FormCompose\FormDefinition $descend Child definition operation.
     * @return list<\FormCompose\Field\FieldDefinition|\FormCompose\EmbeddedDefinition>
     */
    public static function change(array $entries, string $path, Closure $change, Closure $descend): array
    {
        $result = [];
        foreach ($entries as $entry) {
            if ($entry->path === $path) {
                $entry = $change($entry);
            } elseif ($entry instanceof EmbeddedDefinition && str_starts_with($path, $entry->path . '.')) {
                $relative = substr($path, strlen($entry->path) + 1);
                if ($entry->definition->has($relative)) {
                    $entry = new EmbeddedDefinition($entry->path, $descend($entry->definition, $relative));
                }
            }

            if ($entry !== null) {
                $result[] = $entry;
            }
        }

        return $result;
    }
}
