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
     * @param list<\FormCompose\Field\FieldDefinition|\FormCompose\EmbeddedDefinition|\FormCompose\RepeatedDefinition> $entries Ordered children.
     * @param \Closure(\FormCompose\Field\FieldDefinition|\FormCompose\EmbeddedDefinition|\FormCompose\RepeatedDefinition): (\FormCompose\Field\FieldDefinition|\FormCompose\EmbeddedDefinition|\FormCompose\RepeatedDefinition|null) $change Direct entry operation.
     * @param \Closure(\FormCompose\FormDefinition, string): \FormCompose\FormDefinition $descend Child definition operation.
     * @return list<\FormCompose\Field\FieldDefinition|\FormCompose\EmbeddedDefinition|\FormCompose\RepeatedDefinition>
     */
    public static function change(array $entries, string $path, Closure $change, Closure $descend): array
    {
        $result = [];
        foreach ($entries as $entry) {
            if ($entry->path === $path) {
                $entry = $change($entry);
            } elseif (
                str_starts_with($path, $entry->path . '.')
                && (
                    $entry instanceof EmbeddedDefinition
                    || $entry instanceof RepeatedDefinition
                )
            ) {
                $relative = substr($path, strlen($entry->path) + 1);
                $child = $entry instanceof RepeatedDefinition ? $entry->rows : $entry->definition;
                if ($child?->has($relative)) {
                    $child = $descend($child, $relative);
                    $entry = $entry instanceof RepeatedDefinition
                        ? new RepeatedDefinition(
                            $entry->path,
                            $entry->definition,
                            $entry->keys,
                            $entry->options,
                            $child,
                        )
                        : new EmbeddedDefinition($entry->path, $child, $entry->options);
                }
            }

            if ($entry !== null) {
                $result[] = $entry;
            }
        }

        return $result;
    }
}
