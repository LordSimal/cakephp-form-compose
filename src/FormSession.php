<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Binding\FieldResolver;
use FormCompose\Binding\FormBinding;
use FormCompose\Field\BoundField;
use FormCompose\Field\FieldDefinition;
use FormCompose\Form\FormLifecycle;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RenderNode;
use Throwable;

/**
 * Coordinates the public API for building and rendering one bound form.
 *
 * Created by FormHelper::bind(). Uses the binding to resolve fields, the renderer
 * to produce themed markup, and the lifecycle to track submitted inputs and tokens.
 * A session can open once; render its inputs before closing it. Override
 * createFieldResolver() or createLifecycle() to supply specialized collaborators.
 *
 * ```php
 * $form = $this->Form->bind($article);
 * echo $form->open(), $form->control('title');
 * echo $form->submit('Save'), $form->close();
 * ```
 */
class FormSession
{
    protected readonly FormLifecycle $lifecycle;

    /**
     * Create a form session with its binding and rendering configuration.
     */
    public function __construct(public readonly FormBinding $binding, protected readonly RenderContext $rendering)
    {
        $this->lifecycle = $this->createLifecycle();
    }

    /**
     * Prepare and render the opening through the same theme system as fields.
     *
     * @param array<string, mixed> $attributes Form options and HTML attributes.
     */
    public function open(array $attributes = []): string
    {
        $state = $this->lifecycle->prepare($this->rendering->theme, $attributes);
        $html = $this->rendering->renderer->render(
            'open',
            new RenderNode(
                'form',
                $state->method,
                [
                    'form' => $this,
                    'state' => $state,
                    'attributes' => $state->attributes,
                    'hidden' => $state->hidden,
                ],
                $state->presentation,
            ),
        );
        $this->lifecycle->opened($state);

        return $html;
    }

    /**
     * Finalize security data and render closing markup before committing the closed state.
     */
    public function close(): string
    {
        $state = $this->lifecycle->assertOpen();
        try {
            $html = $this->rendering->renderer->render(
                'close',
                new RenderNode(
                    'form',
                    $state->method,
                    [
                        'form' => $this,
                        'state' => $state,
                        'hidden' => $this->lifecycle->tokens(),
                        'attributes' => $this->rendering->theme->attributes('form.close', $state->method),
                    ],
                    $state->presentation,
                ),
            );
        } catch (Throwable $throwable) {
            $this->lifecycle->cancelClose();
            throw $throwable;
        }

        $this->lifecycle->close();

        return $html;
    }

    /**
     * Resolve a field snapshot without emitting markup or registering submissions.
     *
     * @param array<string, mixed> $options Per-field overrides.
     */
    public function field(string|FieldDefinition $field, array $options = []): BoundField
    {
        return $this->createFieldResolver()->resolve($field, $options, $this->lifecycle);
    }

    /**
     * Render a complete field using the same bound state as manual composition.
     *
     * Set template to a CakePHP element name for this control's layout, or use
     * templates to override individual parts. Conflicting control overrides fail.
     * templateData supplies a separate data bag to the control and all its parts.
     *
     * @param array<string, mixed> $options Field options.
     */
    public function control(string|FieldDefinition $field, array $options = []): string
    {
        return $this->field($field, $options)->control();
    }

    /**
     * Render a reusable field collection here, through the normal control templates.
     *
     * With no argument, render the definition selected at bind(). An explicitly
     * supplied collection stays local to this call and does not replace defaults.
     * The form must be open; a session without a definition renders an empty string.
     */
    public function definition(?FormDefinition $definition = null): string
    {
        $this->lifecycle->assertOpen();
        $definition ??= $this->binding->definition ?? FormDefinition::make();

        return implode('', array_map($this->control(...), $definition->fields));
    }

    /**
     * Render a themed button and register its optional submission only after rendering succeeds.
     *
     * @param array<string, mixed> $attributes Button attributes and optional templates, variant, or templateData.
     */
    public function submit(string|Html $label, array $attributes = []): string
    {
        $this->lifecycle->assertOpen();
        $options = array_intersect_key($attributes, array_flip(['templates', 'variant', 'templateData']));
        unset($attributes['templates'], $attributes['variant'], $attributes['templateData']);
        $type = (string)($attributes['type'] ?? 'submit');
        $bag = $this->rendering->theme->attributes('button', $type)->merge(['type' => 'submit'])->merge($attributes);
        $html = $this->rendering->renderer->render(
            'control',
            new RenderNode(
                'button',
                $type,
                [
                    'form' => $this,
                    'label' => $label,
                    'attributes' => $bag,
                ],
                $options,
            ),
        );
        $this->lifecycle->button($bag);

        return $html;
    }

    /**
     * Render prepared infrastructure fields through themed hidden-input elements.
     *
     * @param list<\FormCompose\Rendering\Attributes> $fields Method, CSRF, or final protection fields.
     * @param array<string, mixed> $presentation Form template and variant options.
     */
    public function hiddenFields(array $fields, array $presentation = []): string
    {
        $html = '';
        foreach ($fields as $field) {
            $attributes = $this->rendering->theme->attributes('form.hidden', 'hidden')->merge($field->values);
            $html .= $this->rendering->renderer->render(
                'hidden',
                new RenderNode(
                    'form',
                    'hidden',
                    [
                        'attributes' => $attributes,
                    ],
                    $presentation,
                ),
            );
        }

        return $html;
    }

    /**
     * Create lifecycle coordination for this session before opening the form.
     */
    protected function createLifecycle(): FormLifecycle
    {
        return new FormLifecycle($this->binding);
    }

    /**
     * Create the resolver used by field() and control().
     */
    protected function createFieldResolver(): FieldResolver
    {
        return new FieldResolver($this->binding, $this->rendering);
    }
}
