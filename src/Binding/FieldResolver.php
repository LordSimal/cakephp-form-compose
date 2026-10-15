<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\Utility\Hash;
use FormCompose\Field\BoundField;
use FormCompose\Field\FieldDefinition;
use FormCompose\Field\FieldState;
use FormCompose\Field\NativeType;
use FormCompose\Form\FormLifecycle;
use FormCompose\Rendering\RenderContext;
use InvalidArgumentException;

/**
 * Resolves field metadata once for use by any renderer.
 */
final readonly class FieldResolver
{
    /**
     * Use one form's binding and presentation dependencies.
     */
    public function __construct(private FormBinding $binding, private RenderContext $rendering)
    {
    }

    /**
     * Bind reusable or inline configuration to a field state snapshot.
     *
     * @param array<string, mixed> $options Explicit field overrides.
     */
    public function resolve(string|FieldDefinition $field, array $options, FormLifecycle $lifecycle): BoundField
    {
        $definition = $field instanceof FieldDefinition ? $field : $this->find($field);
        $options = array_replace($definition->options, $options);
        $path = $definition->path;
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
        $messages = array_map(
            static fn(mixed $message): string => (string)$message,
            array_values(Hash::flatten($this->binding->context->error($path))),
        );
        $state = new FieldState($path, $type, new ValueResolver($this->binding)->resolve($path, $options), [
            'name' => $name,
            'id' => $id,
            'messages' => $messages,
            'constraints' => Constraints::resolve($this->binding->context, $path, $type, $options),
            'options' => $options,
        ]);

        return new BoundField(
            $state,
            $lifecycle,
            $this->rendering->renderer,
            $this->rendering->theme,
            $this->rendering->types[$type] ?? new NativeType($this->rendering->renderer, $this->rendering->theme),
        );
    }

    /**
     * Read a definition without requiring schema-based form generation.
     */
    private function find(string $path): FieldDefinition
    {
        foreach ($this->binding->definition->fields ?? [] as $field) {
            if ($field->path === $path) {
                return $field;
            }
        }

        return new FieldDefinition($path);
    }

    /**
     * Map context metadata to useful native controls.
     *
     * @param array<string, mixed> $options Explicit field options.
     */
    private function inferType(string $path, array $options): string
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
}
