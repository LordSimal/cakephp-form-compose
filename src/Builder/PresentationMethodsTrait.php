<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\PresentationConfig;

/** Shared mutable presentation setters; each setter captures immutable configuration. */
trait PresentationMethodsTrait
{
    /** @param array<string, mixed> $attributes Input or wrapper attributes. */
    public function attributes(array $attributes): static
    {
        return $this->updatePresentation($this->currentPresentation()->withAttributes($attributes));
    }

    /** @param array<string, mixed> $attributes Attributes for one named part. */
    public function partAttributes(string $part, array $attributes): static
    {
        return $this->updatePresentation($this->currentPresentation()->withPartAttributes($part, $attributes));
    }

    /** Select the primary template of this builder's scope. */
    public function template(string $element): static
    {
        return $this->partTemplate($this->primaryPart(), $element);
    }

    /** Select an explicit element for one part. */
    public function partTemplate(string $part, string $element): static
    {
        return $this->updatePresentation($this->currentPresentation()->withTemplates([$part => $element]));
    }

    /** Select or clear a named theme variant. */
    public function variant(?string $variant): static
    {
        return $this->updatePresentation($this->currentPresentation()->withVariant($variant));
    }

    /** @param array<string, mixed> $data Application-owned template variables. */
    public function templateData(array $data): static
    {
        return $this->updatePresentation($this->currentPresentation()->withTemplateData($data));
    }

    /** Read the current immutable presentation. */
    abstract protected function currentPresentation(): PresentationConfig;

    /** Capture a presentation update in the owning configuration domain. */
    abstract protected function updatePresentation(PresentationConfig $presentation): static;

    /** Identify the part selected by template() for this builder. */
    abstract protected function primaryPart(): string;
}
