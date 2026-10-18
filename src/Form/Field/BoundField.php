<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Builder\PartBuilder;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Form\FeedbackEntry;
use FormCompose\Form\FormLifecycle;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\FieldAttributes;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\RenderNode;

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
    use FieldPartsTrait;

    /** @var array<string, \FormCompose\Form\Field\BoundField> Child fields, resolved without registration. */
    public array $inputs {
        get => $this->children->resolve($this, $this->fieldType);
    }

    /** The successful emitted focus target, or null before rendering. */
    public ?string $focusTarget {
        get => $this->lifecycle->inputs->target($this->state->id);
    }

    protected ?Attributes $activeAttributes = null;

    protected ?PresentationConfig $activePresentation = null;

    /** Effective presentation while rendering this input and its native children. */
    public PresentationConfig $presentation {
        get => $this->activePresentation ?? $this->state->config->presentation;
    }

    /** @internal Effective attributes while rendering this input's children. */
    public Attributes $inputAttributes {
        get => $this->activeAttributes ?? $this->attributes();
    }

    /** The shared rendering pipeline used by custom field types. */
    public RendererInterface $renderer {
        get => $this->rendering->renderer;
    }

    /** The enclosing composite field, when this field is a declared child. */
    public ?BoundField $parent {
        get => $this->children->parent;
    }

    /**
     * Compose immutable state, rendering, and a submission contract.
     */
    public function __construct(
        public readonly FieldState $state,
        protected readonly FormLifecycle $lifecycle,
        protected readonly RenderContext $rendering,
        protected readonly FieldTypeInterface $fieldType,
        protected readonly FieldChildren $children,
    ) {
    }

    /**
     * Resolve attributes without rendering or registering fields.
     *
     * @param array<string, mixed> $extra Additional attributes.
     */
    public function attributes(string $part = 'input', array $extra = []): Attributes
    {
        $attributes = $this->createFieldAttributes()->resolve($this->state, $part, $extra);

        return $part === 'input' && $this->parent instanceof self
            ? InheritedAttributes::resolve($this->parent->inputAttributes, $attributes, $this->state)
            : $attributes;
    }

    /**
     * Render the entire themed control, or just the input for hidden fields.
     */
    public function control(): string
    {
        if ($this->state->type === 'hidden') {
            return $this->input()->render();
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
     */
    public function input(): PartBuilder
    {
        return new PartBuilder($this->inputMarkup(...), 'input');
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
            $this->lifecycle->inputs->references->record($this, $bag);
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
     */
    public function label(): PartBuilder
    {
        return new PartBuilder($this->labelMarkup(...), 'label');
    }

    /**
     * Render help content when enabled.
     *
     */
    public function help(): PartBuilder
    {
        return new PartBuilder($this->helpMarkup(...), 'help');
    }

    /**
     * Render validation messages when enabled.
     *
     */
    public function errors(): PartBuilder
    {
        return new PartBuilder($this->errorsMarkup(...), 'errors');
    }

    /**
     * Create the attribute resolver used by every part of this field.
     */
    protected function createFieldAttributes(): FieldAttributes
    {
        return new FieldAttributes($this->rendering->theme);
    }
}
