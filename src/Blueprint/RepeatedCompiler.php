<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use Closure;
use FormCompose\FormContext;
use FormCompose\FormDefinition;
use FormCompose\RepeatedDefinition;

/**
 * Compiles one repeated blueprint occurrence into independent row embeds.
 *
 * The owning factory supplies blueprint resolution; no container or discovery state
 * is retained. Subclasses can specialize keys() through BlueprintCompiler::createRepeatedCompiler().
 */
class RepeatedCompiler
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
     * Resolve each row blueprint once at its own relative path.
     */
    public function compile(RepeatedDefinition $entry, FormContext $context): RepeatedDefinition
    {
        $keys = $entry->keys ?? $this->keys($entry->path, $context);
        RepeatedDefinition::validateKeys($keys);
        $rows = new FormDefinition([]);
        foreach ($keys as $key) {
            $rowContext = $context->at($entry->path . '.' . $key);
            $row = ($this->resolve)($entry->definition, $rowContext);
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

        return array_keys(is_array($rows) ? $rows : iterator_to_array($rows));
    }
}
