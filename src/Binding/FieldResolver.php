<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\CompositeType;
use FormCompose\Form\Field\FieldChildren;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Form\Field\FieldIdentity;
use FormCompose\Form\Field\FieldState;
use FormCompose\Form\Field\FieldTypeInterface;
use FormCompose\Form\Field\NativeType;
use FormCompose\Form\FormLifecycle;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\TemplateOptions;

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
    public function resolve(
        string $field,
        array $options,
        FormLifecycle $lifecycle,
        ?BoundField $parent = null,
    ): BoundField {
        $definition = new FieldDefinition($field);
        $path = $definition->path;
        $defaults = array_column($this->binding->definition->fields ?? [], 'options', 'path')[$path] ?? [];
        $conventions = $this->createFieldConventions();
        $options = array_replace(
            $defaults,
            $options,
        );
        FieldOptions::validate($options);
        $options = FieldText::resolve(TemplateOptions::normalize($conventions->resolve($path, $options)));
        $identity = new FieldIdentity(
            $path,
            (string)($options['type'] ?? $this->inferType($path, $options)),
            $this->binding->id,
            $options,
        );
        $type = $identity->type;

        $state = new FieldState($path, $type, $this->createValueResolver()->resolve($path, $options), [
            'name' => $identity->name,
            'id' => $identity->id,
            'messages' => $this->composite($type) ? $conventions->directMessages($path) : $conventions->messages($path),
            'composite' => $this->composite($type),
            'requiredMessage' => $conventions->requiredMessage($path),
            'constraints' => Constraints::resolve($this->binding->context, $path, $type, $options),
            'options' => $options,
        ]);

        return $this->createBoundField($state, $lifecycle, $parent);
    }

    /**
     * Map context metadata to useful native controls.
     *
     * @param array<string, mixed> $options Explicit field options.
     */
    protected function inferType(string $path, array $options): string
    {
        if (in_array(array_last(explode('.', $path)), $this->binding->passwordFields, true)) {
            return 'password';
        }

        if (isset($options['options'])) {
            return 'select';
        }

        $name = array_last(explode('.', $path));

        return (
            TypeMap::FIELD_NAMES[$name]
                ?? $this->binding->typeMap[(string)$this->binding->context->type($path)]
                ?? 'text'
        );
    }

    /**
     * Create the field object exposed to application templates.
     */
    protected function createBoundField(
        FieldState $state,
        FormLifecycle $lifecycle,
        ?BoundField $parent = null,
    ): BoundField {
        return new BoundField(
            $state,
            $lifecycle,
            $this->rendering,
            $this->createFieldType($state->type),
            $this->createFieldChildren($lifecycle, $parent),
        );
    }

    /**
     * Create the child resolver using this same binding and lifecycle.
     */
    protected function createFieldChildren(FormLifecycle $lifecycle, ?BoundField $parent = null): FieldChildren
    {
        return new FieldChildren(
            fn(string $path, array $options, BoundField $parent): BoundField => $this->resolve(
                $path,
                $options,
                $lifecycle,
                $parent,
            ),
            $parent,
        );
    }

    /**
     * Inspect discovered metadata without instantiating a custom type.
     */
    protected function composite(string $type): bool
    {
        return CompositeType::matches($this->rendering->types[$type] ?? null);
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
