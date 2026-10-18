<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\FormConfig;

/**
 * Mutable form authoring with independent immutable configuration snapshots.
 * @extends ConfigurationBuilder<FormConfig>
 */
class FormConfigBuilder extends ConfigurationBuilder
{
    use FormSubmissionMethodsTrait;
    use FormBindingMethodsTrait;
    use FormLayoutMethodsTrait;

    protected string $primaryPart = 'layout';

    /** Start from a reusable immutable snapshot. */
    public function __construct(FormConfig|self $config = new FormConfig())
    {
        parent::__construct(FormConfig::capture($config));
    }

    /** Capture the domain-specific immutable settings. */
    public function config(): FormConfig
    {
        return $this->configuration;
    }

    /** Store domain settings through the shared immutable snapshot boundary. */
    protected function updateFormConfig(FormConfig $config): void
    {
        $this->configuration = $config;
    }

    /** Overlay explicitly supplied settings from the same configuration domain. */
    public function apply(FormConfig|self $config): static
    {
        $this->applyConfig(FormConfig::capture($config));

        return $this;
    }
}
