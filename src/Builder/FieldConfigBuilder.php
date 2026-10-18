<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\FieldConfig;

/**
 * Mutable field authoring with independent immutable configuration snapshots.
 * @extends ConfigurationBuilder<FieldConfig>
 */
class FieldConfigBuilder extends ConfigurationBuilder
{
    use FieldIdentityMethodsTrait;
    use FieldTextMethodsTrait;
    use FieldValueMethodsTrait;
    use FieldStateMethodsTrait;
    use FieldChoicesMethodsTrait;

    /** @param array<string, mixed> $value Configured extensionData data. */
    public function extensionData(array $value): static
    {
        $this->updateFieldConfig($this->config()->withExtensionData($value));

        return $this;
    }

    /** Start from a reusable immutable snapshot. */
    public function __construct(FieldConfig|self $config = new FieldConfig())
    {
        parent::__construct(FieldConfig::capture($config));
    }

    /** Capture the domain-specific immutable settings. */
    public function config(): FieldConfig
    {
        return $this->configuration;
    }

    /** Store domain settings through the shared immutable snapshot boundary. */
    protected function updateFieldConfig(FieldConfig $config): void
    {
        $this->configuration = $config;
        $this->changed();
    }

    /** Overlay explicitly supplied settings from the same configuration domain. */
    public function apply(FieldConfig|self $config): static
    {
        $this->applyConfig(FieldConfig::capture($config));

        return $this;
    }
}
