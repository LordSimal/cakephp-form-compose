<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\View\Form\ContextInterface;

/**
 * Delegates field data and feedback through an overridable path translation.
 *
 * ScopedContext supplies association metadata and the prefix operation. Value
 * options and Cake error arrays pass through unchanged to the original context.
 */
abstract class ContextAdapter implements ContextInterface
{
    /**
     * Retain the existing Cake context rather than copying its bound data.
     */
    public function __construct(protected readonly ContextInterface $context)
    {
    }

    /**
     * Resolve values without changing null, false, zero, or default semantics.
     *
     * @param array<string, mixed> $options Cake value resolution options.
     */
    public function val(string $field, array $options = []): mixed
    {
        return $this->context->val($this->path($field), $options);
    }

    /**
     * Read the abstract type at the mounted path.
     */
    public function type(string $field): ?string
    {
        return $this->context->type($this->path($field));
    }

    /**
     * Check errors at the mounted path.
     */
    public function hasError(string $field): bool
    {
        return $this->context->hasError($this->path($field));
    }

    /**
     * Read errors at the mounted path.
     */
    public function error(string $field): array
    {
        return $this->context->error($this->path($field));
    }

    /**
     * Translate a local field into the underlying context's path.
     */
    abstract protected function path(string $field): string;
}
