<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use FormCompose\Field\BoundField;
use FormCompose\Field\FieldDefinition;
use FormCompose\Field\FieldState;
use FormCompose\Field\FieldTypeInterface;
use FormCompose\Field\NativeType;
use FormCompose\Form\FormLifecycle;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\TemplateOptions;
use InvalidArgumentException;

/**
 * Builds a BoundField from a context path and merged configuration.
 *
 * Combines compiled blueprint defaults and per-call overrides, normalizes the
 * template shorthand to templates['control'], evaluates deferred label/help text,
 * infers a control type,
 * applies association conventions, resolves its value, and derives its input
 * name, ID, constraints, and errors.
 * The resulting field uses a registered custom type or the standard NativeType.
 * Called by FormSession::field(); resolving a field does not render its input.
 * Subclasses can specialize inferType() and the field/type/value/convention factories.
 */
class FieldResolver
{
    /**
     * Use one form's binding and presentation dependencies.
     */
    public function __construct(protected readonly FormBinding $binding, protected readonly RenderContext $rendering)
    {
    }

    /**
     * Bind blueprint defaults and inline options to a field state snapshot.
     *
     * @param array<string, mixed> $options Explicit field overrides.
     */
    public function resolve(string $field, array $options, FormLifecycle $lifecycle): BoundField
    {
        $definition = new FieldDefinition($field);
        $path = $definition->path;
        $defaults = in_array($path, array_column($this->binding->definition->fields ?? [], 'path'), true)
            ? $this->binding->definition?->field($path)->options ?? []
            : [];
        $conventions = $this->createFieldConventions();
        $options = array_replace(
            $defaults,
            $options,
        );
        FieldOptions::validate($options);
        $options = FieldText::resolve(TemplateOptions::normalize($conventions->resolve($path, $options)));
        $type = (string)($options['type'] ?? $this->inferType($path, $options));
        if (!preg_match('/^[a-z][a-z0-9_-]*$/D', $type)) {
            throw new InvalidArgumentException('Invalid control type.');
        }

        $parts = explode('.', $path);
        $first = array_shift($parts);
        $name = (string)($options['name'] ?? $first . ($parts === [] ? '' : '[' . implode('][', $parts) . ']'));
        $id = (string)($options['id'] ?? $this->binding->id . '-' . str_replace('.', '-', $path));
        if ($id === '' || !preg_match('/^[a-zA-Z0-9_.:-]+(?:\[[a-zA-Z0-9_.:-]*\])*$/D', $name)) {
            throw new InvalidArgumentException('Use a nonempty ID and a valid bracket-notation input name.');
        }

        $state = new FieldState($path, $type, $this->createValueResolver()->resolve($path, $options), [
            'name' => $name,
            'id' => $id,
            'messages' => $conventions->messages($path),
            'constraints' => Constraints::resolve($this->binding->context, $path, $type, $options),
            'options' => $options,
        ]);

        return $this->createBoundField($state, $lifecycle);
    }

    /**
     * Map context metadata to useful native controls.
     *
     * @param array<string, mixed> $options Explicit field options.
     */
    protected function inferType(string $path, array $options): string
    {
        if (isset($options['options'])) {
            return 'select';
        }

        return match ($this->binding->context->type($path)) {
            'boolean' => 'checkbox',
            'text' => 'textarea',
            'integer', 'tinyinteger', 'smallinteger', 'biginteger', 'decimal', 'float' => 'number',
            'datetime',
            'timestamp',
            'datetimefractional',
            'timestampfractional',
            'timestamptimezone',
                => 'datetime-local',
            'date' => 'date',
            'time' => 'time',
            'binary' => 'file',
            default => 'text',
        };
    }

    /**
     * Create the field object exposed to application templates.
     */
    protected function createBoundField(FieldState $state, FormLifecycle $lifecycle): BoundField
    {
        return new BoundField(
            $state,
            $lifecycle,
            $this->rendering->renderer,
            $this->rendering->theme,
            $this->createFieldType($state->type),
        );
    }

    /**
     * Select a registered control type or create the default native implementation.
     */
    protected function createFieldType(string $type): FieldTypeInterface
    {
        return $this->rendering->types[$type] ?? new NativeType($this->rendering->renderer, $this->rendering->theme);
    }

    /**
     * Create the source resolver used to populate field values.
     */
    protected function createValueResolver(): ValueResolver
    {
        return new ValueResolver($this->binding);
    }

    /**
     * Create association defaults and choice discovery; override to specialize conventions.
     */
    protected function createFieldConventions(): FieldConventions
    {
        return new FieldConventions($this->binding, $this->rendering->viewVariable);
    }
}
