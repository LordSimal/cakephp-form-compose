<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

/** Explicit replacement counterparts to the normal merging presentation setters. */
trait ReplacementMethodsTrait
{
    /** @param array<string, mixed> $attributes Replacement input or wrapper attributes. */
    public function replaceAttributes(array $attributes): static
    {
        return $this->updatePresentation($this->currentPresentation()->replaceAttributes($attributes));
    }

    /** @param array<string, mixed> $attributes Replacement attributes for one part. */
    public function replacePartAttributes(string $part, array $attributes): static
    {
        return $this->updatePresentation($this->currentPresentation()->replacePartAttributes($part, $attributes));
    }

    /** @param array<string, string> $templates Replacement elements keyed by part. */
    public function replaceTemplates(array $templates): static
    {
        return $this->updatePresentation($this->currentPresentation()->replaceTemplates($templates));
    }

    /** @param array<string, mixed> $data Replacement application-owned template variables. */
    public function replaceTemplateData(array $data): static
    {
        return $this->updatePresentation($this->currentPresentation()->replaceTemplateData($data));
    }

    /** Read the current immutable presentation. */
    abstract protected function currentPresentation(): PresentationConfig;

    /** Capture a presentation update in the owning configuration domain. */
    abstract protected function updatePresentation(PresentationConfig $presentation): static;
}
