<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\ButtonConfig;

/**
 * Mutable button authoring with independent immutable configuration snapshots.
 * @extends ConfigurationBuilder<ButtonConfig>
 */
class ButtonConfigBuilder extends ConfigurationBuilder
{
    use ButtonStateMethodsTrait;

    /** Set type on this mutable builder. */
    public function type(string $value): static
    {
        $this->configuration = $this->config()->withType($value);

        return $this;
    }

    /** Start from a reusable immutable snapshot. */
    public function __construct(ButtonConfig|self $config = new ButtonConfig())
    {
        parent::__construct(ButtonConfig::capture($config));
    }

    /** Capture the domain-specific immutable settings. */
    public function config(): ButtonConfig
    {
        return $this->configuration;
    }

    /** Overlay explicitly supplied settings from the same configuration domain. */
    public function apply(ButtonConfig|self $config): static
    {
        $this->applyConfig(ButtonConfig::capture($config));

        return $this;
    }

    /** Read native button state from its configuration domain. */
    protected function buttonConfig(): ButtonConfig
    {
        return $this->config();
    }

    /** Store native button state in its configuration domain. */
    protected function updateButtonConfig(ButtonConfig|ActionConfig $config): void
    {
        assert($config instanceof ButtonConfig, 'Button updates must preserve the configuration domain.');
        $this->configuration = $config;
    }
}
