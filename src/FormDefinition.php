<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Builds an ordered, reusable collection of field configuration.
 *
 * A definition holds no bound data, markup, or submission state. add() and with()
 * return new collections, so the original can be reused across screens and forms.
 * Render it at any position with FormSession::definition(), or pass a field()
 * result to the session's field() or control() for an application-owned layout.
 * Each field path must be unique within the collection.
 *
 * ```php
 * $contact = FormDefinition::make()
 *     ->add(FieldDefinition::make('name')->label('Full name'))
 *     ->add(FieldDefinition::make('email', 'email'));
 * echo $form->definition($contact->with('email', ['label' => 'Work email']));
 * ```
 */
final readonly class FormDefinition
{
    /**
     * Collect uniquely named definitions in presentation order.
     *
     * @param list<\FormCompose\Field\FieldDefinition> $fields Fields to render.
     */
    public function __construct(public array $fields)
    {
        $paths = array_map(static fn(FieldDefinition $field): string => $field->path, $fields);
        if (count(array_unique($paths)) !== count($paths)) {
            throw new InvalidArgumentException('Form definitions cannot contain duplicate field paths.');
        }
    }

    /**
     * Start an empty collection for fluent construction.
     */
    public static function make(): self
    {
        return new self([]);
    }

    /**
     * Append a field without changing the original collection.
     *
     * @throws \InvalidArgumentException When the path is already present.
     */
    public function add(FieldDefinition $field): self
    {
        return new self([...$this->fields, $field]);
    }

    /**
     * Retrieve configuration for manual composition, without binding or rendering.
     *
     * @throws \InvalidArgumentException When the path is not in this collection.
     */
    public function field(string $path): FieldDefinition
    {
        foreach ($this->fields as $field) {
            if ($field->path === $path) {
                return $field;
            }
        }

        throw new InvalidArgumentException(sprintf('Field "%s" is not in this definition.', $path));
    }

    /**
     * Check collection membership without binding or rendering a field.
     */
    public function has(string $path): bool
    {
        return in_array($path, array_column($this->fields, 'path'), true);
    }

    /**
     * Remove an existing field while preserving the remaining order and original collection.
     *
     * @throws \InvalidArgumentException When the path is not in this collection.
     */
    public function remove(string $path): self
    {
        $this->field($path);

        return new self(array_values(array_filter(
            $this->fields,
            static fn(FieldDefinition $field): bool => $field->path !== $path,
        )));
    }

    /**
     * Override an existing field's options while preserving collection order.
     *
     * Options replace matching top-level keys, as with FieldDefinition::with().
     *
     * @param array<string, mixed> $options Field behavior and presentation overrides.
     * @throws \InvalidArgumentException When the path is not in this collection.
     */
    public function with(string $path, array $options): self
    {
        $replacement = $this->field($path)->with($options);

        return new self(array_map(
            static fn(FieldDefinition $field): FieldDefinition => $field->path === $path ? $replacement : $field,
            $this->fields,
        ));
    }
}
