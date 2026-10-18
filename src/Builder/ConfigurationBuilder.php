<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\Configuration;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Configuration\PresentationMethodsTrait;
use FormCompose\Configuration\ReplacementMethodsTrait;

/**
 * Captures immutable configuration while mutable authoring methods return this builder.
 * @template T of Configuration|PresentationConfig
 */
abstract class ConfigurationBuilder
{
    use PresentationMethodsTrait;
    use ReplacementMethodsTrait;

    protected string $primaryPart = 'control';

    /** @var T Current immutable configuration snapshot. */
    protected Configuration|PresentationConfig $configuration;

    /** @param T $configuration Initial immutable settings. */
    public function __construct(Configuration|PresentationConfig $configuration)
    {
        $this->configuration = $configuration;
    }

    /** @param T $config Overlay only explicitly supplied settings. */
    protected function applyConfig(Configuration|PresentationConfig $config): void
    {
        $current = $this->configuration;
        if ($current instanceof PresentationConfig) {
            assert($config instanceof PresentationConfig, 'Presentation updates retain their domain.');
            /** @var T $updated merge() preserves the concrete configuration domain. */
            $updated = $current->merge($config);
        } else {
            assert(
                $current instanceof Configuration && $config instanceof Configuration,
                'Configuration domains must match.',
            );
            /** @var T $updated merge() preserves the concrete configuration domain. */
            $updated = $current->merge($config);
        }

        $this->configuration = $updated;
    }

    /** @return T An immutable snapshot independent of future authoring edits. */
    abstract public function config(): Configuration|PresentationConfig;

    /** Read current presentation through the typed configuration boundary. */
    protected function currentPresentation(): PresentationConfig
    {
        $current = $this->configuration;
        if ($current instanceof PresentationConfig) {
            return $current;
        }

        assert($current instanceof Configuration, 'Domain settings carry presentation.');

        return $current->presentation;
    }

    /** Capture one presentation update. */
    protected function updatePresentation(PresentationConfig $presentation): static
    {
        $current = $this->configuration;
        if ($current instanceof PresentationConfig) {
            /** @var T $updated Presentation changes preserve the configuration domain. */
            $updated = $presentation;
        } else {
            assert($current instanceof Configuration, 'Domain settings carry presentation.');
            /** @var T $updated Presentation changes preserve the configuration domain. */
            $updated = $current->presentation($presentation);
        }

        $this->configuration = $updated;

        return $this;
    }

    /** Return the primary part for this authoring scope. */
    protected function primaryPart(): string
    {
        return $this->primaryPart;
    }
}
