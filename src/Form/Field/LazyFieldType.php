<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use Closure;
use FormCompose\Rendering\Attributes;
use LogicException;

/**
 * Constructs one discovered field type only when a form actually uses it.
 *
 * BlueprintFactory supplies a fresh proxy per form binding. A resolved instance
 * is reused by that form's fields, while explicit instances bypass this proxy.
 */
class LazyFieldType implements FieldTypeInterface, InputIdentityProviderInterface, ErrorTargetProviderInterface
{
    protected ?FieldTypeInterface $instance = null;

    /**
     * Retain construction without accessing the container during ordinary binding.
     *
     * @param \Closure(): \FormCompose\Form\Field\FieldTypeInterface $factory Type construction.
     */
    public function __construct(protected readonly Closure $factory, public readonly bool $composite = false)
    {
    }

    /**
     * Resolve child declarations only when a composite's children are requested.
     *
     * @return list<\FormCompose\Form\Field\FieldSpec>
     */
    public function inputs(BoundField $field): array
    {
        $type = $this->resolve();

        return $type instanceof CompositeType ? $type->inputs($field) : [];
    }

    /**
     * Render through the requested implementation and retain its instance.
     */
    public function render(BoundField $field, Attributes $attributes): string
    {
        return $this->resolve()->render($field, $attributes);
    }

    /**
     * Use the same implementation for successful submission metadata.
     *
     * @return list<\FormCompose\Form\Field\Submission>
     */
    public function submissions(BoundField $field, Attributes $attributes): array
    {
        return $this->resolve()->submissions($field, $attributes);
    }

    /**
     * Forward optional composite identities without constructing an unrelated type.
     *
     * @return list<string>
     */
    public function inputIds(BoundField $field, Attributes $attributes): array
    {
        $type = $this->resolve();

        return $type instanceof InputIdentityProviderInterface ? $type->inputIds($field, $attributes) : [];
    }

    /**
     * Forward an optional custom summary target through the same resolved instance.
     */
    public function errorTarget(BoundField $field, Attributes $attributes): ?string
    {
        $type = $this->resolve();

        return $type instanceof ErrorTargetProviderInterface
            ? $type->errorTarget($field, $attributes)
            : $field->state->id;
    }

    /**
     * Resolve once per proxy; failed construction remains retryable.
     */
    protected function resolve(): FieldTypeInterface
    {
        $type = $this->instance ?? ($this->factory)();
        if ($this->composite !== $type instanceof CompositeType) {
            throw new LogicException('A discovered field type must preserve its declared composite contract.');
        }

        return $this->instance = $type;
    }
}
