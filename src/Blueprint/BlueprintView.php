<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use FormCompose\Builder\ControlBatch;
use FormCompose\Builder\ControlBuilder;
use FormCompose\Builder\EmbedBuilder;
use FormCompose\Builder\FieldBuilder;
use FormCompose\Builder\OmittedControlBuilder;
use FormCompose\Builder\PartBuilder;
use FormCompose\Configuration\EmbedConfig;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Form\Field\FieldSpec;
use FormCompose\Form\FormSession;
use InvalidArgumentException;

/**
 * Exposes a blueprint's compiled definition to its layout with relative field paths.
 *
 * The original session resolves and registers controls, preserving custom field
 * services and one form lifecycle. This view cannot open or close another form.
 * Override qualify() through BlueprintRenderer::createView() to specialize it.
 */
class BlueprintView
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
     * @throws \InvalidArgumentException For removed or unknown fields; guard optional parts with has().
     */
    public function field(string $field, ?FieldConfig $config = null): FieldBuilder
    {
        $configured = $this->definition->field($field);

        return $this->session->field($this->qualify($field), $configured->config->merge($config ?? new FieldConfig()));
    }

    /** Configure a scoped control, preserving deliberately removed fields as empty output. */
    public function control(string $field, ?FieldConfig $config = null): ControlBuilder
    {
        if ($this->definition->index->wasRemoved($field)) {
            return new OmittedControlBuilder($this->session, $config ?? new FieldConfig());
        }

        $configured = $this->definition->field($field);

        return $this->session->control(
            $this->qualify($field),
            $configured->config->merge($config ?? new FieldConfig()),
        );
    }

    /** @param iterable<array-key, mixed> $fields Ordered relative field declarations. */
    public function controls(iterable $fields): ControlBatch
    {
        return $this->session->controlBatch(
            $fields,
            fn(FieldSpec $field): string => $this->control($field->path, $field->config)->render(),
        );
    }

    /** Render an already compiled child without rerunning its hook. */
    public function embedded(string $path): PartBuilder
    {
        return new PartBuilder(function (PresentationConfig $presentation) use ($path): string {
            if ($this->definition->index->wasRemoved($path)) {
                return '';
            }

            $defaults = $this->definition->index->embedPresentation[$path] ?? new PresentationConfig();

            return $this->session->renderDefinition(
                $this->definition->embedded($path),
                $this->qualify($path),
                $defaults->merge($presentation),
            );
        });
    }

    /** Render this child's layout at its existing path without another form lifecycle. */
    public function render(?PresentationConfig $presentation = null): string
    {
        return $this->session->renderDefinition(
            $this->definition,
            $this->path === '' ? null : $this->path,
            $presentation ?? new PresentationConfig(),
        );
    }

    /** Configure an already compiled repeated occurrence without rerunning row hooks. */
    public function embeddedMany(string $path): EmbedBuilder
    {
        return new EmbedBuilder(function (EmbedConfig $config) use ($path): string {
            if ($this->definition->index->wasRemoved($path)) {
                return '';
            }

            $repeat =
                $this->definition->index->repeats[$path] ?? throw new InvalidArgumentException('Unknown repeated embed: '
                    . $path);

            return $this->session->renderRepeated($this->qualify($path), $repeat, $config);
        });
    }

    /**
     * Qualify a relative field or embed without changing explicit name/id overrides.
     */
    protected function qualify(string $path): string
    {
        return $this->path === '' ? $path : $this->path . '.' . $path;
    }
}
