<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\EmbedConfig;
use FormCompose\Rendering\Html;

/**
 * Mutable embed authoring with independent immutable configuration snapshots.
 * @extends ConfigurationBuilder<EmbedConfig>
 */
class EmbedConfigBuilder extends ConfigurationBuilder
{
    /** @param list<int|string>|null $value Configured keys data. */
    public function keys(?array $value): static
    {
        $this->configuration = $this->config()->withKeys($value);
        $this->changed();

        return $this;
    }

    /** Set label on this mutable builder. */
    public function label(string|Html|null $value): static
    {
        $this->configuration = $this->config()->withLabel($value);
        $this->changed();

        return $this;
    }

    /** Set errors on this mutable builder. */
    public function errors(bool $value = true): static
    {
        $this->configuration = $this->config()->withErrors($value);
        $this->changed();

        return $this;
    }

    /** Start from a reusable immutable snapshot. */
    public function __construct(EmbedConfig|self $config = new EmbedConfig())
    {
        parent::__construct(EmbedConfig::capture($config));
    }

    /** Capture the domain-specific immutable settings. */
    public function config(): EmbedConfig
    {
        return $this->configuration;
    }

    /** Overlay explicitly supplied settings from the same configuration domain. */
    public function apply(EmbedConfig|self $config): static
    {
        $this->applyConfig(EmbedConfig::capture($config));

        return $this;
    }
}
