<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\RenderNode;

/**
 * Renders one editable scalar input through the shared PHP element pipeline.
 *
 * Extend this for textarea/editor-style widgets with one submitted name. Set
 * $template to an application element; a local partTemplate('widget', ...) overrides it.
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
        $presentation = $field->state->config->presentation;
        if ($this->template !== null) {
            $templates = $presentation->templates;
            $presentation = $presentation->templates($templates + [$this->part => $this->template]);
        }

        return $field->renderer->render($this->part, new RenderNode(
            'field',
            $field->state->type,
            ['field' => $field, 'attributes' => $attributes] + $this->variables($field),
            $presentation,
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
                    new Submission($field->state->name, $field->state->config->secure),
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
