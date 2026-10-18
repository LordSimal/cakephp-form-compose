<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\SummaryConfig;
use FormCompose\Rendering\Html;

/**
 * Mutable summary authoring with independent immutable configuration snapshots.
 * @extends ConfigurationBuilder<SummaryConfig>
 */
class SummaryConfigBuilder extends ConfigurationBuilder
{
    /** Set title on this mutable builder. */
    public function title(string|Html|null $value): static
    {
        $this->configuration = $this->config()->withTitle($value);

        return $this;
    }

    /** Start from a reusable immutable snapshot. */
    public function __construct(SummaryConfig|self $config = new SummaryConfig())
    {
        parent::__construct(SummaryConfig::capture($config));
    }

    /** Capture the domain-specific immutable settings. */
    public function config(): SummaryConfig
    {
        return $this->configuration;
    }

    /** Overlay explicitly supplied settings from the same configuration domain. */
    public function apply(SummaryConfig|self $config): static
    {
        $this->applyConfig(SummaryConfig::capture($config));

        return $this;
    }
}
