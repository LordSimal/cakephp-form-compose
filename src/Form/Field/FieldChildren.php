<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use Closure;
use FormCompose\Binding\PathValue;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Configuration\PresentationConfig;
use InvalidArgumentException;
use LogicException;

/**
 * Resolves a composite's relative declarations through its owning field resolver.
 *
 * @internal BoundField's per-field child cache; resolving children never registers inputs.
 */
class FieldChildren
{
    /** @var array<string, \FormCompose\Form\Field\BoundField>|null */
    protected ?array $fields = null;

    /**
     * Retain the owning binding and lifecycle through an explicit resolver callback.
     *
     * @param \Closure(string, \FormCompose\Configuration\FieldConfig, \FormCompose\Form\Field\BoundField): \FormCompose\Form\Field\BoundField $resolve Child resolver.
     */
    public function __construct(protected readonly Closure $resolve, public readonly ?BoundField $parent = null)
    {
    }

    /**
     * Resolve all declared children once, without rendering any of them.
     *
     * @return array<string, \FormCompose\Form\Field\BoundField>
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

        /** @var \FormCompose\Form\Field\CompositeType|\FormCompose\Form\Field\LazyFieldType $type */
        $fields = [];
        foreach (ControlList::normalize($type->inputs($field)) as $child) {
            if (!$child instanceof FieldSpec) {
                throw new InvalidArgumentException('Composite children must be relative FieldSpec declarations.');
            }

            $fields[$child->path] = ($this->resolve)(
                $field->state->path . '.' . $child->path,
                $this->configuration($field, $child),
                $field,
            );
        }

        return $this->fields = $fields;
    }

    /**
     * Derive child identities and values, preserving explicit options and missing keys.
     *
     */
    protected function configuration(BoundField $field, FieldSpec $child): FieldConfig
    {
        $state = $field->state;
        $config = FieldConfig::builder()
            ->name(
                InputNames::base($state->name) . '[' . str_replace('.', '][', $child->path) . ']',
            )
            ->id($state->id . '.' . str_replace('-', ':2d', $child->path))
            ->secure($state->config->secure)
            ->errors($state->config->errors)
            ->presentation(PresentationConfig::builder()->templateData($state->config->presentation->templateData));
        $value = PathValue::lookup(is_array($state->value) ? $state->value : [], $child->path);
        if ($value->found || $state->config->hasValue) {
            $config = $config->value($value->value);
        }

        return $config->merge($child->config);
    }
}
