<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

/** Explicit replacement operations, kept separate from ordinary presentation merging. */
trait PresentationReplacementTrait
{
    /** @param array<string, mixed> $attributes Replace configured input/wrapper attributes. */
    public function replaceAttributes(array $attributes): self
    {
        return $this->merge($this->layer(['attributes' => $attributes], ['attributes' => true]));
    }

    /** @param array<string, mixed> $attributes Replace one configured part's attributes. */
    public function replacePartAttributes(string $part, array $attributes): self
    {
        return $this->merge($this->layer(['partAttributes' => [$part => $attributes]], ['part:' . $part => true]));
    }

    /** @param array<string, string> $templates Replace the configured part-to-element map. */
    public function replaceTemplates(array $templates): self
    {
        return $this->merge($this->layer(['templates' => $templates], ['templates' => true]));
    }

    /** @param array<string, mixed> $data Replace application-owned template variables. */
    public function replaceTemplateData(array $data): self
    {
        return $this->merge($this->layer(['templateData' => $data], ['templateData' => true]));
    }

    /**
     * @param array{attributes?: array<string, mixed>, partAttributes?: array<string, array<string, mixed>>, templates?: array<string, string>, templateData?: array<string, mixed>, variant?: ?string} $values Explicit presentation values.
     * @param array<string, true> $replace Replaced bag identities.
     */
    abstract private function layer(array $values, array $replace): self;

    /** Combine this layer with another immutable layer. */
    abstract public function merge(self $next): self;
}
