<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Field\BoundField;
use FormCompose\Field\ControlList;
use FormCompose\Field\FieldDefinition;
use FormCompose\Rendering\TemplateOptions;
use InvalidArgumentException;

/**
 * Exposes one compiled definition to its layout with relative field paths.
 *
 * The original session resolves and registers controls, preserving custom field
 * services and one form lifecycle. This view cannot open or close another form.
 * Override qualify() through DefinitionRenderer::createView() to specialize it.
 */
class DefinitionView
{
    /**
     * Retain compiled local configuration and its absolute mount within the form.
     */
    public function __construct(
        protected readonly FormSession $session,
        public readonly FormDefinition $definition,
        public readonly string $path = '',
    ) {
    }

    /**
     * Inspect final field or embed availability without registration side effects.
     */
    public function has(string $path): bool
    {
        return $this->definition->has($path);
    }

    /**
     * Resolve an available field for independently renderable parts.
     *
     * @param array<string, mixed> $options Per-call field overrides.
     * @throws \InvalidArgumentException For removed or unknown fields; guard optional parts with has().
     */
    public function field(string|FieldDefinition $field, array $options = []): BoundField
    {
        return $this->session->field($this->configured($field), $options);
    }

    /**
     * Combine relative configuration with explicit definition overrides.
     */
    protected function configured(string|FieldDefinition $field): FieldDefinition
    {
        $path = $field instanceof FieldDefinition ? $field->path : $field;
        $configured = $this->definition->field($path);

        return new FieldDefinition(
            $this->qualify($path),
            array_replace(
                $configured->options,
                $field instanceof FieldDefinition ? $field->options : [],
            ),
        );
    }

    /**
     * Render a configured control, or omit a deliberately removed field.
     *
     * @param array<string, mixed> $options Per-call field overrides.
     */
    public function control(string|FieldDefinition $field, array $options = []): string
    {
        $path = $field instanceof FieldDefinition ? $field->path : $field;
        if ($this->definition->index->wasRemoved($path)) {
            return '';
        }

        return $this->session->control($this->configured($field), $options);
    }

    /**
     * Render ordered relative controls inside this definition's layout transaction.
     *
     * Like control(), removed fields produce nothing and unknown fields fail.
     * Calls this view's control() so subclasses retain their layout behavior.
     *
     * @param array<array-key, mixed> $fields Indexed paths/definitions or paths mapped to options.
     * @throws \InvalidArgumentException For invalid entries or unknown fields.
     */
    public function controls(array $fields): string
    {
        $definitions = ControlList::normalize($fields);
        $html = '';
        foreach ($definitions as $definition) {
            $html .= $this->control($definition);
        }

        return $html;
    }

    /**
     * Render an already compiled embed with its own layout and parent adjustments.
     *
     * @param array<string, mixed> $options Local definition presentation overrides.
     */
    public function embedded(string $path, array $options = []): string
    {
        if ($this->definition->index->wasRemoved($path)) {
            return '';
        }

        return $this->session->definition(
            $this->definition->embedded($path),
            at: $this->qualify($path),
            options: array_replace(
                TemplateOptions::normalize($this->definition->index->embedOptions[$path] ?? []),
                TemplateOptions::normalize($options),
            ),
        );
    }

    /**
     * Render the child layout independently of an occurrence wrapper template.
     *
     * @param array<string, mixed> $options Local definition presentation.
     */
    public function render(array $options = []): string
    {
        return $this->session->definition(
            $this->definition,
            at: $this->path === '' ? null : $this->path,
            options: $options,
        );
    }

    /**
     * Render compiled repeated rows without re-running their blueprint hooks.
     *
     * @param array<string, mixed> $options Occurrence presentation overrides.
     */
    public function embeddedMany(string $path, array $options = []): string
    {
        if ($this->definition->index->wasRemoved($path)) {
            return '';
        }

        $repeat =
            $this->definition->index->repeats[$path] ?? throw new InvalidArgumentException('Unknown repeated embed: '
                . $path);

        return $this->session->embedMany($this->qualify($path), $repeat, options: $options);
    }

    /**
     * Qualify a relative field or embed without changing explicit name/id overrides.
     */
    protected function qualify(string $path): string
    {
        return $this->path === '' ? $path : $this->path . '.' . $path;
    }
}
