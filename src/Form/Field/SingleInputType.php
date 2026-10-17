<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\RenderNode;

/**
 * Renders one editable scalar input through the shared PHP element pipeline.
 *
 * Extend this for textarea/editor-style widgets with one submitted name. Set
 * $template to an application element; local templates['widget'] overrides it.
 * Hidden values, files, checkboxes, and other special submission protocols use
 * native child fields or a dedicated FieldTypeInterface implementation instead.
 */
class SingleInputType implements FieldTypeInterface
{
    protected ?string $template = null;

    protected string $part = 'widget';

    /**
     * Render application markup with the normal theme, variant, and parent lookup.
     */
    public function render(BoundField $field, Attributes $attributes): string
    {
        $options = $field->state->options;
        if ($this->template !== null) {
            /** @var array<string, string> $templates */
            $templates = $options['templates'] ?? [];
            $options['templates'] = $templates + [$this->part => $this->template];
        }

        return $field->renderer->render($this->part, new RenderNode(
            'field',
            $field->state->type,
            ['field' => $field, 'attributes' => $attributes] + $this->variables($field),
            $options,
        ));
    }

    /**
     * Describe the single editable value, excluding disabled controls.
     *
     * @return list<\FormCompose\Form\Field\Submission>
     */
    public function submissions(BoundField $field, Attributes $attributes): array
    {
        return (
            $attributes->has('disabled')
                ? []
                : [
                    new Submission($field->state->name, ($field->state->options['secure'] ?? true) !== false),
                ]
        );
    }

    /**
     * Prepare extension variables; templates escape the scalar value as text.
     *
     * @return array<string, mixed>
     */
    protected function variables(BoundField $field): array
    {
        return ['value' => NativeValue::scalar($field->state->value)];
    }
}
