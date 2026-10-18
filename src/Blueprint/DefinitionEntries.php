<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

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
     * @param list<\FormCompose\Form\Field\FieldDefinition|\FormCompose\Blueprint\EmbeddedDefinition|\FormCompose\Blueprint\RepeatedDefinition> $entries Ordered children.
     * @param \Closure(\FormCompose\Form\Field\FieldDefinition|\FormCompose\Blueprint\EmbeddedDefinition|\FormCompose\Blueprint\RepeatedDefinition): (\FormCompose\Form\Field\FieldDefinition|\FormCompose\Blueprint\EmbeddedDefinition|\FormCompose\Blueprint\RepeatedDefinition|null) $change Direct entry operation.
     * @param \Closure(\FormCompose\Blueprint\FormDefinition, string): \FormCompose\Blueprint\FormDefinition $descend Child definition operation.
     * @return list<\FormCompose\Form\Field\FieldDefinition|\FormCompose\Blueprint\EmbeddedDefinition|\FormCompose\Blueprint\RepeatedDefinition>
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
                            $entry->blueprint,
                            $entry->keys,
                            $entry->config,
                            $child,
                        )
                        : new EmbeddedDefinition($entry->path, $child, $entry->presentation);
                }
            }

            if ($entry !== null) {
                $result[] = $entry;
            }
        }

        return $result;
    }
}
