<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use Closure;
use FormCompose\EmbeddedDefinition;
use FormCompose\FormContext;
use FormCompose\FormDefinition;
use FormCompose\RepeatedDefinition;

/**
 * Expands repeated declarations into independent row embeds before parent configuration.
 *
 * The owning factory supplies blueprint resolution; no container or discovery state
 * is retained. Subclasses can specialize keys() or repeated() through compiler expansion.
 */
class DefinitionExpander
{
    /**
     * Reuse the caller's blueprint resolver, including dependency injection and cycles.
     *
     * @param \Closure(string, \FormCompose\FormContext): \FormCompose\FormDefinition $resolve Blueprint construction.
     */
    public function __construct(protected readonly Closure $resolve)
    {
    }

    /**
     * Expand only unresolved declarations; compiled snapshots keep their hook results.
     */
    public function expand(FormDefinition $definition, FormContext $context): FormDefinition
    {
        $entries = [];
        foreach ($definition->entries as $entry) {
            $entries[] = match (true) {
                $entry instanceof RepeatedDefinition => $this->repeated($entry, $context),
                $entry instanceof EmbeddedDefinition => new EmbeddedDefinition(
                    $entry->path,
                    $this->expand($entry->definition, $context->at($entry->path)),
                    $entry->options,
                ),
                default => $entry,
            };
        }

        return $entries === $definition->entries
            ? $definition
            : new FormDefinition($entries, $definition->options, $definition->removed);
    }

    /**
     * Resolve each row at its own relative path and leave reusable prototypes unchanged.
     */
    public function repeated(RepeatedDefinition $entry, FormContext $context): RepeatedDefinition
    {
        if ($entry->rows instanceof FormDefinition) {
            return $entry;
        }

        $keys = $entry->keys ?? $this->keys($entry->path, $context);
        $rows = new FormDefinition([]);
        foreach ($keys as $key) {
            $rowContext = $context->at($entry->path . '.' . $key);
            $row = is_string($entry->definition)
                ? ($this->resolve)($entry->definition, $rowContext)
                : $this->expand($entry->definition, $rowContext);
            $rows = $rows->embed((string)$key, $row);
        }

        return new RepeatedDefinition($entry->path, $entry->definition, $keys, $entry->options, $rows);
    }

    /**
     * Infer original keys without falling back when a submitted collection is explicitly cleared.
     *
     * @return list<int|string>
     */
    protected function keys(string $path, FormContext $context): array
    {
        /** @var mixed $value */
        $value = $context->value($path);
        if (!is_iterable($value)) {
            return [];
        }

        /** @var iterable<array-key, mixed> $rows */
        $rows = $value;
        $keys = array_keys(is_array($rows) ? $rows : iterator_to_array($rows));
        // Reuse the declaration's key validation before running any row hooks.
        new RepeatedDefinition($path, new FormDefinition([]), $keys);

        return $keys;
    }
}
