<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\Configuration;
use FormCompose\Configuration\PresentationConfig;

/**
 * Captures immutable configuration while mutable authoring methods return this builder.
 * @template T of Configuration
 */
abstract class ConfigurationBuilder
{
    use PresentationMethodsTrait;
    use ReplacementMethodsTrait;

    protected string $primaryPart = 'control';

    /** @var T Current immutable configuration snapshot. */
    protected Configuration $configuration;

    /** @param T $configuration Initial immutable settings. */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /** @param T $config Overlay only explicitly supplied settings. */
    protected function applyConfig(Configuration $config): void
    {
        /** @var T $updated merge() preserves the concrete configuration domain. */
        $updated = $this->configuration->merge($config);
        $this->configuration = $updated;
        $this->changed();
    }

    /** @return T An immutable snapshot independent of future authoring edits. */
    abstract public function config(): Configuration;

    /** Specialize attached authoring behavior without changing snapshot semantics. */
    protected function changed(): void
    {
    }

    /** Read current presentation through the typed configuration boundary. */
    protected function currentPresentation(): PresentationConfig
    {
        return $this->configuration->presentation;
    }

    /** Capture one presentation update before notifying attached authoring behavior. */
    protected function updatePresentation(PresentationConfig $presentation): static
    {
        /** @var T $updated Presentation changes preserve the configuration domain. */
        $updated = $this->configuration->withPresentation($presentation);
        $this->configuration = $updated;
        $this->changed();

        return $this;
    }

    /** Return the primary part for this authoring scope. */
    protected function primaryPart(): string
    {
        return $this->primaryPart;
    }
}
