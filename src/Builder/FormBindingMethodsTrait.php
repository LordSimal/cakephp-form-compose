<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\FormConfig;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\Theme;

/** Binding settings for form authoring.
 */
trait FormBindingMethodsTrait
{
    /** @param list<string> $value Configured valueSources data. */
    public function valueSources(array $value): static
    {
        $this->updateFormConfig($this->config()->valueSources($value));

        return $this;
    }

    /** @param list<string> $value Configured passwordFields data. */
    public function passwordFields(array $value): static
    {
        $this->updateFormConfig($this->config()->passwordFields($value));

        return $this;
    }

    /** @param array<string, string> $value Configured typeMap data. */
    public function typeMap(array $value): static
    {
        $this->updateFormConfig($this->config()->typeMap($value));

        return $this;
    }

    /** @param array<string, \FormCompose\Form\Field\FieldTypeInterface> $value Configured types data. */
    public function types(array $value): static
    {
        $this->updateFormConfig($this->config()->types($value));

        return $this;
    }

    /** @param array<string, mixed> $value Configured context data. */

    public function context(array $value): static
    {
        $this->updateFormConfig($this->config()->context($value));

        return $this;
    }

    /** Append a blueprint in rendering order; null clears the selection before binding. */
    public function blueprint(?string $value): static
    {
        $this->updateFormConfig($this->config()->blueprint($value));

        return $this;
    }

    /** Set theme on this mutable builder. */
    public function theme(?Theme $value): static
    {
        $this->updateFormConfig($this->config()->theme($value));

        return $this;
    }

    /** Set renderer on this mutable builder. */
    public function renderer(?RendererInterface $value): static
    {
        $this->updateFormConfig($this->config()->renderer($value));

        return $this;
    }

    /** Capture domain-specific immutable configuration. */
    abstract public function config(): FormConfig;

    /** Store a typed configuration update on the attached builder. */
    abstract protected function updateFormConfig(FormConfig $config): void;
}
