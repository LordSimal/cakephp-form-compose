<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\Datasource\EntityInterface;
use Cake\View\Form\ContextInterface;

/**
 * Adapts Cake's context to relative lookups during an embedded blueprint hook.
 *
 * Field metadata delegates to the original context with a prefix, including
 * unloaded association metadata. Form-wide identity and create state remain
 * those of the original binding; no data or validation lifecycle is introduced.
 * Subclasses can specialize path() when supplying a custom FormContext.
 */
class ScopedContext implements ContextInterface
{
    /**
     * Retain the original context and one validated mount path.
     */
    public function __construct(
        protected readonly ContextInterface $context,
        protected readonly string $prefix,
    ) {
    }

    /**
     * Preserve the form-wide primary key metadata.
     *
     * @return array<string>
     */
    public function getPrimaryKey(): array
    {
        return $this->context->getPrimaryKey();
    }

    /**
     * Check primary-key membership at the mounted path.
     */
    public function isPrimaryKey(string $field): bool
    {
        return $this->context->isPrimaryKey($this->path($field));
    }

    /**
     * Preserve the original form's create state.
     */
    public function isCreate(): bool
    {
        return $this->context->isCreate();
    }

    /**
     * Read associated validation requirements.
     */
    public function isRequired(string $field): ?bool
    {
        return $this->context->isRequired($this->path($field));
    }

    /**
     * Read the associated required-field message.
     */
    public function getRequiredMessage(string $field): ?string
    {
        return $this->context->getRequiredMessage($this->path($field));
    }

    /**
     * Read associated maximum-length validation metadata.
     */
    public function getMaxLength(string $field): ?int
    {
        return $this->context->getMaxLength($this->path($field));
    }

    /**
     * List loaded child fields without substituting root field names.
     *
     * @return array<string>
     */
    public function fieldNames(): array
    {
        /** @var mixed $value */
        $value = $this->context->val($this->prefix);
        if ($value instanceof EntityInterface) {
            return $value->getVisible();
        }

        return is_array($value) ? array_map(strval(...), array_keys($value)) : [];
    }

    /**
     * Read associated schema attributes.
     */
    public function attributes(string $field): array
    {
        return $this->context->attributes($this->path($field));
    }

    /**
     * Qualify a child field relative to this adapter.
     */
    protected function path(string $field): string
    {
        return $this->prefix . '.' . $field;
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
}
