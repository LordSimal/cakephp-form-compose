<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\RenderNode;
use FormCompose\Rendering\Theme;
use WeakMap;

/**
 * Implements standard HTML controls, including inputs, textareas, and choice lists.
 *
 * This is the default FieldTypeInterface implementation selected by FieldResolver.
 * It prepares browser values, checked/selected attributes, choice groups, and hidden
 * fallbacks, then asks the renderer for native and choice templates. submissions()
 * describes the control's submitted name and locks hidden values for form protection.
 * Override choice() or createChoices() to specialize choice preparation and rendering.
 *
 * For example, control('active', ['type' => 'checkbox']) uses this class to prepare
 * the checkbox and its unchecked fallback; the theme determines their markup.
 */
class NativeType implements FieldTypeInterface, InputIdentityProviderInterface
{
    /** @var \WeakMap<\FormCompose\Field\BoundField, \FormCompose\Field\NativeInput> Successful render metadata. */
    protected WeakMap $inputs;

    /**
     * Use the shared renderer for native inputs, choices, and fallback fields.
     */
    public function __construct(protected readonly RendererInterface $renderer, protected readonly Theme $theme)
    {
        $this->inputs = new WeakMap();
    }

    /**
     * Prepare native control data and render its fallback, input, and choice templates.
     */
    public function render(BoundField $field, Attributes $attributes): string
    {
        $fallback = ControlFallback::resolve($field->state, $attributes);
        $prefix = $fallback instanceof Attributes
            ? $this->renderer->render('fallback', RenderNode::field($field, $fallback))
            : '';
        $native = NativeAttributes::resolve($field->state, $attributes);
        $choices = $this->prepareChoices($field, $native);
        $value = $field->state->type === 'textarea' ? NativeValue::scalar($field->state->value) : '';

        $html = $prefix . $this->renderer->render('native', RenderNode::field($field, $native, [
            'value' => $value,
            'choices' => $choices,
            'renderChoice' => fn(Choice $choice): string => $this->choice($field, $choice),
        ]));
        $this->inputs[$field] = NativeInput::from($field->state, $choices, $fallback);

        return $html;
    }

    /**
     * Render each choice and its children through the same themed field-part lookup.
     */
    protected function choice(BoundField $field, Choice $choice): string
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

        $input = $this->inputs[$field] ?? NativeInput::from(
            $field->state,
            $this->prepareChoices($field, NativeAttributes::resolve($field->state, $attributes)),
            ControlFallback::resolve($field->state, $attributes),
        );
        if (!$input->submittable) {
            return [];
        }

        return [new Submission(
            $field->state->name,
            ($field->state->options['secure'] ?? true) !== false,
            $field->state->type === 'hidden' ? NativeValue::scalar($field->state->value) : null,
        )];
    }

    /**
     * Report the IDs prepared by native rendering, without consuming choices again.
     *
     * @return list<string>
     */
    public function inputIds(BoundField $field, Attributes $attributes): array
    {
        return $this->inputs->offsetExists($field) ? $this->inputs->offsetGet($field)->ids : [];
    }

    /**
     * Prepare choices and the single-select empty option through the normal extension point.
     *
     * @return list<\FormCompose\Field\Choice>
     */
    protected function prepareChoices(BoundField $field, Attributes $attributes): array
    {
        /** @var iterable<array-key, mixed> $options */
        $options = $field->state->options['options'] ?? [];

        return SelectPlaceholder::apply(
            $field->state,
            $attributes,
            $this->createChoices()->resolve($field->state, $attributes, $options),
        );
    }

    /**
     * Create the choice builder used by selects, radios, and checkbox groups.
     */
    protected function createChoices(): Choices
    {
        return new Choices($this->theme);
    }
}
