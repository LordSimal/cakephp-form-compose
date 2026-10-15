<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Form\FormLifecycle;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\FieldAttributes;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\RenderNode;
use FormCompose\Rendering\Theme;

/**
 * Immutable field state shared by automatic controls and application-owned markup.
 */
final readonly class BoundField
{
    /**
     * Compose immutable state, rendering, and a submission contract.
     */
    public function __construct(
        public FieldState $state,
        private FormLifecycle $lifecycle,
        private RendererInterface $renderer,
        private Theme $theme,
        private FieldTypeInterface $fieldType,
    ) {
    }

    /**
     * Resolve attributes without rendering or registering fields.
     *
     * @param array<string, mixed> $extra Additional attributes.
     */
    public function attributes(string $part = 'input', array $extra = []): Attributes
    {
        return new FieldAttributes($this->theme)->resolve($this->state, $part, $extra);
    }

    /**
     * Render the entire themed control, or just the input for hidden fields.
     */
    public function control(): string
    {
        return $this->state->type === 'hidden'
            ? $this->input()
            : $this->renderer->render('control', RenderNode::field($this, $this->attributes('container')));
    }

    /**
     * Render and register an input only after its template succeeds.
     *
     * @param array<string, mixed> $attributes Input attributes.
     */
    public function input(array $attributes = []): string
    {
        $this->lifecycle->assertInput($this);
        $bag = $this->attributes('input', $attributes);
        $html = $this->renderer->render('input', RenderNode::field($this, $bag));
        $this->lifecycle->register($this, $this->fieldType->submissions($this, $bag));

        return $html;
    }

    /**
     * Render a registered control type inside an input template without registering twice.
     */
    public function renderInput(Attributes $attributes): string
    {
        return $this->fieldType->render($this, $attributes);
    }

    /**
     * Register handwritten input markup using the same submission contract as input().
     *
     * @param array<string, mixed> $attributes Attributes used by the handwritten input.
     */
    public function register(array $attributes = []): void
    {
        $this->lifecycle->register($this, $this->fieldType->submissions($this, $this->attributes(
            'input',
            $attributes,
        )));
    }

    /**
     * Render a label or group legend unless suppressed by presentation options.
     *
     * @param array<string, mixed> $attributes Label attributes.
     */
    public function label(array $attributes = []): string
    {
        if (($this->state->options['label'] ?? null) === false) {
            return '';
        }

        return $this->renderer->render('label', RenderNode::field($this, $this->attributes('label', $attributes)));
    }

    /**
     * Render help content when enabled.
     *
     * @param array<string, mixed> $attributes Help attributes.
     */
    public function help(array $attributes = []): string
    {
        return $this->state->hasHelp()
            ? $this->renderer->render('help', RenderNode::field($this, $this->attributes('help', $attributes)))
            : '';
    }

    /**
     * Render validation messages when enabled.
     *
     * @param array<string, mixed> $attributes Error attributes.
     */
    public function errors(array $attributes = []): string
    {
        return $this->state->hasErrors()
            ? $this->renderer->render('errors', RenderNode::field($this, $this->attributes('errors', $attributes)))
            : '';
    }
}
