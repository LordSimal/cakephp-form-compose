<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

/** Fluent single-template selectors on immutable presentation settings. */
trait PresentationTemplatesTrait
{
    /** Select the primary control template. */
    public function template(string $element): self
    {
        return $this->partTemplate('control', $element);
    }

    /** Select an explicit template for one part. */
    public function partTemplate(string $part, string $element): self
    {
        return $this->templates([$part => $element]);
    }

    /** @param array<string, string> $templates Merge explicit elements keyed by part. */
    abstract public function templates(array $templates): self;
}
