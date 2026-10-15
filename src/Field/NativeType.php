<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\RenderNode;
use FormCompose\Rendering\Theme;

/**
 * Native control semantics rendered exclusively through FormCompose PHP templates.
 */
final readonly class NativeType implements FieldTypeInterface
{
    /**
     * Use the shared renderer for native inputs, choices, and fallback fields.
     */
    public function __construct(private RendererInterface $renderer, private Theme $theme)
    {
    }

    /**
     * Compose prepared native values and choices without producing HTML in PHP classes.
     */
    public function render(BoundField $field, Attributes $attributes): string
    {
        $fallback = ControlFallback::resolve($field->state, $attributes);
        $prefix = $fallback === null ? '' : $this->renderer->render('fallback', RenderNode::field($field, $fallback));
        $native = NativeAttributes::resolve($field->state, $attributes);
        /** @var iterable<array-key, mixed> $options */
        $options = $field->state->options['options'] ?? [];
        $choices = new Choices($this->theme)->resolve($field->state, $native, $options);
        $value = $field->state->type === 'textarea' ? NativeValue::scalar($field->state->value) : '';

        return $prefix . $this->renderer->render('native', RenderNode::field($field, $native, [
            'value' => $value,
            'choices' => $choices,
            'renderChoice' => fn(Choice $choice): string => $this->choice($field, $choice),
        ]));
    }

    /**
     * Render each choice and its children through the same themed field-part lookup.
     */
    private function choice(BoundField $field, Choice $choice): string
    {
        $part = $field->state->type === 'select' ? 'option' : 'choice';
        $part = $choice->kind === 'group' ? $part . '-group' : $part;

        return $this->renderer->render($part, RenderNode::field($field, $choice->attributes, [
            'choice' => $choice,
            'renderChoice' => fn(Choice $child): string => $this->choice($field, $child),
        ]));
    }

    /**
     * Describe successful native submissions and locked hidden values.
     *
     * @return list<\FormCompose\Field\Submission>
     */
    public function submissions(BoundField $field, Attributes $attributes): array
    {
        if ($attributes->has('disabled')) {
            return [];
        }

        return [new Submission(
            $field->state->name,
            ($field->state->options['secure'] ?? true) !== false,
            $field->state->type === 'hidden' ? NativeValue::scalar($field->state->value) : null,
        )];
    }
}
