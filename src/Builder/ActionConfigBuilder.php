<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\FormConfig;

/**
 * Mutable action authoring with independent immutable configuration snapshots.
 * @extends ConfigurationBuilder<ActionConfig>
 */
class ActionConfigBuilder extends ConfigurationBuilder
{
    /** Set method on this mutable builder. */
    public function method(string $value): static
    {
        $this->configuration = $this->config()->withMethod($value);
        $this->changed();

        return $this;
    }

    /** @param array<string, mixed> $value Nested locked action data. */
    public function data(array $value): static
    {
        $this->configuration = $this->config()->withData($value);
        $this->changed();

        return $this;
    }

    /** Set confirm on this mutable builder. */
    public function confirm(?string $value): static
    {
        $this->configuration = $this->config()->withConfirm($value);
        $this->changed();

        return $this;
    }

    /** Set block on this mutable builder. */
    public function block(?string $value): static
    {
        $this->configuration = $this->config()->withBlock($value);
        $this->changed();

        return $this;
    }

    /** Set form on this mutable builder. */
    public function form(FormConfig|FormConfigBuilder $value): static
    {
        $this->configuration = $this->config()->withForm($value);
        $this->changed();

        return $this;
    }

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
}
