<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Form\FeedbackEntry;
use FormCompose\Form\FormLifecycle;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\FieldAttributes;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\RenderNode;
use FormCompose\Rendering\Theme;

/**
 * Provides the rendering API for one resolved field in a form session.
 *
 * Exposes FieldState for inspection and renders either a complete control or its
 * input, label, help, and error parts. input() registers submitted names after
 * successful rendering; register() serves handwritten inputs using the same type.
 * Subclasses can customize field parts and supply their own FieldAttributes resolver.
 *
 * ```php
 * $email = $form->field('email', ['type' => 'email']);
 * echo $email->label(), $email->input(['class' => 'contact-input']);
 * echo $email->errors();
 * ```
 */
class BoundField
{
    /**
     * Compose immutable state, rendering, and a submission contract.
     */
    public function __construct(
        public readonly FieldState $state,
        protected readonly FormLifecycle $lifecycle,
        protected readonly RendererInterface $renderer,
        protected readonly Theme $theme,
        protected readonly FieldTypeInterface $fieldType,
    ) {
    }

    /**
     * Resolve attributes without rendering or registering fields.
     *
     * @param array<string, mixed> $extra Additional attributes.
     */
    public function attributes(string $part = 'input', array $extra = []): Attributes
    {
        return $this->createFieldAttributes()->resolve($this->state, $part, $extra);
    }

    /**
     * Render the entire themed control, or just the input for hidden fields.
     */
    public function control(): string
    {
        if ($this->state->type === 'hidden') {
            return $this->input();
        }

        $attributes = $this->attributes('container');

        return $this->lifecycle->inputs->render(
            fn(): string => $this->renderer->render('control', RenderNode::field($this, $attributes)),
            $attributes,
        );
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

        return $this->lifecycle->inputs->render(function () use ($bag): string {
            $html = $this->renderer->render('input', RenderNode::field($this, $bag));
            $ids = $this->fieldType instanceof InputIdentityProviderInterface
                ? $this->fieldType->inputIds($this, $bag)
                : [];
            $this->lifecycle->register($this, $this->fieldType->submissions($this, $bag), $ids);
            $this->lifecycle->inputs->feedback->record(
                $this->state->id,
                FeedbackEntry::field($this, $this->fieldType, $bag),
            );

            return $html;
        });
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
        $bag = $this->attributes('input', $attributes);
        $this->lifecycle->inputs->render(function () use ($bag): string {
            $this->lifecycle->register($this, $this->fieldType->submissions($this, $bag));
            $this->lifecycle->inputs->feedback->record(
                $this->state->id,
                FeedbackEntry::field($this, null, $bag),
            );

            return '';
        });
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

        $bag = $this->attributes('label', $attributes);

        return $this->lifecycle->inputs->render(
            fn(): string => $this->renderer->render('label', RenderNode::field($this, $bag)),
            $bag,
        );
    }

    /**
     * Render help content when enabled.
     *
     * @param array<string, mixed> $attributes Help attributes.
     */
    public function help(array $attributes = []): string
    {
        if (!$this->state->hasHelp()) {
            return '';
        }

        $bag = $this->attributes('help', $attributes);

        return $this->lifecycle->inputs->render(
            fn(): string => $this->renderer->render('help', RenderNode::field($this, $bag)),
            $bag,
        );
    }

    /**
     * Render validation messages when enabled.
     *
     * @param array<string, mixed> $attributes Error attributes.
     */
    public function errors(array $attributes = []): string
    {
        if (!$this->state->hasErrors()) {
            return '';
        }

        $bag = $this->attributes('errors', $attributes);

        return $this->lifecycle->inputs->render(
            fn(): string => $this->renderer->render('errors', RenderNode::field($this, $bag)),
            $bag,
        );
    }

    /**
     * Create the attribute resolver used by every part of this field.
     */
    protected function createFieldAttributes(): FieldAttributes
    {
        return new FieldAttributes($this->theme);
    }
}
