<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\PresentationConfig;

/** Authors layout or field-part presentation without changing resolved field state. */
class PresentationBuilder
{
    use PresentationMethodsTrait;
    use ReplacementMethodsTrait;

    protected PresentationConfig $configuration;

    /** Start from an immutable presentation snapshot and its primary template part. */
    public function __construct(
        PresentationConfig|self|null $configuration = null,
        protected string $primaryPart = 'control',
    ) {
        $this->configuration = PresentationConfig::capture($configuration);
    }

    /** Capture immutable presentation independently of future edits. */
    public function config(): PresentationConfig
    {
        return $this->configuration;
    }

    /** Overlay another presentation layer. */
    public function apply(PresentationConfig|self $config): static
    {
        $this->configuration = $this->configuration->merge(PresentationConfig::capture($config));
        $this->changed();

        return $this;
    }

    /** Specialize attached authoring behavior without changing snapshot semantics. */
    protected function changed(): void
    {
    }

    /** Read the presentation currently being authored. */
    protected function currentPresentation(): PresentationConfig
    {
        return $this->configuration;
    }

    /** Store a new snapshot before notifying an attached layout declaration. */
    protected function updatePresentation(PresentationConfig $presentation): static
    {
        $this->configuration = $presentation;
        $this->changed();

        return $this;
    }

    /** Return the primary part for this authoring scope. */
    protected function primaryPart(): string
    {
        return $this->primaryPart;
    }
}
