<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\ButtonConfig;

/**
 * Mutable button authoring with independent immutable configuration snapshots.
 * @extends ConfigurationBuilder<ButtonConfig>
 */
class ButtonConfigBuilder extends ConfigurationBuilder
{
    /** Set type on this mutable builder. */
    public function type(string $value): static
    {
        $this->configuration = $this->config()->withType($value);
        $this->changed();

        return $this;
    }

    /** Set name on this mutable builder. */
    public function name(?string $value): static
    {
        $this->configuration = $this->config()->withName($value);
        $this->changed();

        return $this;
    }

    /** Set value on this mutable builder. */
    public function value(mixed $value): static
    {
        $this->configuration = $this->config()->withValue($value);
        $this->changed();

        return $this;
    }

    /** Set disabled on this mutable builder. */
    public function disabled(bool $value = true): static
    {
        $this->configuration = $this->config()->withDisabled($value);
        $this->changed();

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
}
