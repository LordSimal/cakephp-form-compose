<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Binding\FieldResolver;
use FormCompose\Binding\FormBinding;
use FormCompose\Field\BoundField;
use FormCompose\Field\FieldDefinition;
use FormCompose\Form\FormLifecycle;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RenderNode;
use LogicException;
use Throwable;

/**
 * Public form facade over independent binding, lifecycle, and PHP rendering services.
 */
final class FormSession
{
    private readonly FormLifecycle $lifecycle;

    /**
     * Create isolated lifecycle state without constructing or inheriting Cake's FormHelper.
     */
    public function __construct(public readonly FormBinding $binding, private readonly RenderContext $rendering)
    {
        $this->lifecycle = new FormLifecycle($binding);
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
        } catch (Throwable $exception) {
            $this->lifecycle->cancelClose();
            throw $exception;
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
        return new FieldResolver($this->binding, $this->rendering)->resolve($field, $options, $this->lifecycle);
    }

    /**
     * Render a complete field using the same bound state as manual composition.
     *
     * @param array<string, mixed> $options Field options.
     */
    public function control(string|FieldDefinition $field, array $options = []): string
    {
        return $this->field($field, $options)->control();
    }

    /**
     * Render optional reusable field definitions in presentation order.
     */
    public function render(?FormDefinition $definition = null): string
    {
        $definition ??= $this->binding->definition;
        if ($definition === null) {
            throw new LogicException('Supply a form definition to render a field collection.');
        }

        return implode('', array_map($this->control(...), $definition->fields));
    }

    /**
     * Render a themed button and register its optional submission only after rendering succeeds.
     *
     * @param array<string, mixed> $attributes Button attributes and optional templates or variant.
     */
    public function submit(string|Html $label, array $attributes = []): string
    {
        $this->lifecycle->assertOpen();
        $options = array_intersect_key($attributes, array_flip(['templates', 'variant']));
        unset($attributes['templates'], $attributes['variant']);
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
}
