<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\ButtonConfig;

/**
 * Mutable action authoring with independent immutable configuration snapshots.
 * @extends ConfigurationBuilder<ActionConfig>
 */
class ActionConfigBuilder extends ConfigurationBuilder
{
    use ActionMethodsTrait;
    use ButtonStateMethodsTrait;

    /** Start from a reusable immutable snapshot. */
    public function __construct(ActionConfig|self $config = new ActionConfig())
    {
        parent::__construct(ActionConfig::capture($config));
    }

    /** Capture the domain-specific immutable settings. */
    public function config(): ActionConfig
    {
        return $this->configuration;
    }

    /** Overlay explicitly supplied settings from the same configuration domain. */
    public function apply(ActionConfig|self $config): static
    {
        $this->applyConfig(ActionConfig::capture($config));

        return $this;
    }

    /** Capture the next protected action settings. */
    protected function updateActionConfig(ActionConfig $config): void
    {
        $this->configuration = $config;
    }

    /** Read native button state from its configuration domain. */
    protected function buttonConfig(): ActionConfig
    {
        return $this->config();
    }

    /** Store native button state in its configuration domain. */
    protected function updateButtonConfig(ButtonConfig|ActionConfig $config): void
    {
        assert($config instanceof ActionConfig, 'Button updates must preserve the configuration domain.');
        $this->configuration = $config;
    }
}
