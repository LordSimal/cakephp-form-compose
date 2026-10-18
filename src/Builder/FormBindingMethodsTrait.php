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
        $this->updateFormConfig($this->config()->withValueSources($value));

        return $this;
    }

    /** @param list<string> $value Configured passwordFields data. */
    public function passwordFields(array $value): static
    {
        $this->updateFormConfig($this->config()->withPasswordFields($value));

        return $this;
    }

    /** @param array<string, string> $value Configured typeMap data. */
    public function typeMap(array $value): static
    {
        $this->updateFormConfig($this->config()->withTypeMap($value));

        return $this;
    }

    /** @param array<string, \FormCompose\Form\Field\FieldTypeInterface> $value Configured types data. */
    public function types(array $value): static
    {
        $this->updateFormConfig($this->config()->withTypes($value));

        return $this;
    }

    /** @param array<string, mixed> $value Configured context data. */

    public function context(array $value): static
    {
        $this->updateFormConfig($this->config()->withContext($value));

        return $this;
    }

    /** Set blueprint on this mutable builder. */
    public function blueprint(?string $value): static
    {
        $this->updateFormConfig($this->config()->withBlueprint($value));

        return $this;
    }

    /** Set theme on this mutable builder. */
    public function theme(Theme|ThemeBuilder|null $value): static
    {
        $this->updateFormConfig($this->config()->withTheme($value));

        return $this;
    }

    /** Set renderer on this mutable builder. */
    public function renderer(?RendererInterface $value): static
    {
        $this->updateFormConfig($this->config()->withRenderer($value));

        return $this;
    }

    /** Capture domain-specific immutable configuration. */
    abstract public function config(): FormConfig;

    /** Store a typed configuration update and notify attached behavior. */
    abstract protected function updateFormConfig(FormConfig $config): void;
}
