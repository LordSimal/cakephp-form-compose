<?php
declare(strict_types=1);

namespace FormCompose\Field;

use Closure;
use FormCompose\Binding\PathValue;
use LogicException;

/**
 * Resolves a composite's relative declarations through its owning field resolver.
 *
 * @internal BoundField's per-field child cache; resolving children never registers inputs.
 */
class FieldChildren
{
    /** @var array<string, \FormCompose\Field\BoundField>|null */
    protected ?array $fields = null;

    /**
     * Retain the owning binding and lifecycle through an explicit resolver callback.
     *
     * @param \Closure(string, array<string, mixed>, \FormCompose\Field\BoundField): \FormCompose\Field\BoundField $resolve Child resolver.
     */
    public function __construct(protected readonly Closure $resolve, public readonly ?BoundField $parent = null)
    {
    }

    /**
     * Resolve all declared children once, without rendering any of them.
     *
     * @return array<string, \FormCompose\Field\BoundField>
     */
    public function resolve(BoundField $field, FieldTypeInterface $type): array
    {
        if (!CompositeType::matches($type)) {
            return [];
        }

        if ($this->fields !== null) {
            return $this->fields;
        }

        $parent = $field->parent;
        while ($parent instanceof BoundField) {
            if ($parent->state->type === $field->state->type) {
                throw new LogicException('Recursive composite field type: ' . $field->state->type);
            }

            $parent = $parent->parent;
        }

        /** @var \FormCompose\Field\CompositeType|\FormCompose\Field\LazyFieldType $type */
        $fields = [];
        foreach (ControlList::normalize($type->inputs($field)) as $child) {
            $fields[$child->path] = ($this->resolve)(
                $field->state->path . '.' . $child->path,
                $this->options($field, $child),
                $field,
            );
        }

        return $this->fields = $fields;
    }

    /**
     * Derive child identities and values, preserving explicit options and missing keys.
     *
     * @return array<string, mixed>
     */
    protected function options(BoundField $field, FieldDefinition $child): array
    {
        $state = $field->state;
        $options =
            $child->options
            + [
                'name' => InputNames::base($state->name) . '[' . str_replace('.', '][', $child->path) . ']',
                'id' => $state->id . '.' . str_replace('-', ':2d', $child->path),
            ]
            + array_intersect_key($state->options, array_flip(['secure', 'errors', 'templateData']));
        $value = PathValue::lookup(is_array($state->value) ? $state->value : [], $child->path);
        if ($value->found || array_key_exists('value', $state->options)) {
            $options += ['value' => $value->value];
        }

        return $options;
    }
}
