<?php
declare(strict_types=1);

namespace FormCompose\Field;

use Closure;
use FormCompose\Rendering\Attributes;

/**
 * Constructs one discovered field type only when a form actually uses it.
 *
 * BlueprintFactory supplies a fresh proxy per form binding. A resolved instance
 * is reused by that form's fields, while explicit instances bypass this proxy.
 */
class LazyFieldType implements FieldTypeInterface, InputIdentityProviderInterface
{
    protected ?FieldTypeInterface $instance = null;

    /**
     * Retain construction without accessing the container during ordinary binding.
     *
     * @param \Closure(): \FormCompose\Field\FieldTypeInterface $factory Type construction.
     */
    public function __construct(protected readonly Closure $factory)
    {
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
     * @return list<\FormCompose\Field\Submission>
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
     * Resolve once per proxy; failed construction remains retryable.
     */
    protected function resolve(): FieldTypeInterface
    {
        return $this->instance ??= ($this->factory)();
    }
}
