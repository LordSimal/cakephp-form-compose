<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;

/**
 * Declares reusable child inputs while PHP elements own their layout.
 *
 * Override inputs() with relative paths and ordinary field options. The bundled
 * composite element renders each child's control(); custom elements receive
 * $inputs and can arrange individual parts or omit children. Only rendered child
 * inputs register submissions, identities, and summary targets.
 */
abstract class CompositeType extends SingleInputType implements ErrorTargetProviderInterface
{
    protected string $part = 'composite';

    /**
     * Declare children relative to the parent context path and submitted name.
     *
     * @return array<string, array<string, mixed>> Relative paths mapped to field options.
     */
    abstract public function inputs(BoundField $field): array;

    /**
     * Children describe their own submissions when their inputs actually render.
     *
     * @return list<\FormCompose\Field\Submission>
     */
    public function submissions(BoundField $field, Attributes $attributes): array
    {
        return [];
    }

    /**
     * Focus the first declared child that emitted an eligible control.
     */
    public function errorTarget(BoundField $field, Attributes $attributes): ?string
    {
        foreach ($field->inputs as $input) {
            if ($input->focusTarget !== null) {
                return $input->focusTarget;
            }
        }

        return null;
    }

    /**
     * Expose independently renderable children to the composite element.
     *
     * @return array<string, mixed>
     */
    protected function variables(BoundField $field): array
    {
        return ['inputs' => $field->inputs];
    }

    /**
     * Identify composite declarations without constructing a discovered service.
     */
    public static function matches(?FieldTypeInterface $type): bool
    {
        return $type instanceof self || ($type instanceof LazyFieldType && $type->composite);
    }
}
