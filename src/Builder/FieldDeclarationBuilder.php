<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldDefinition;

/**
 * Authors one blueprint field declaration; build() captures its path and immutable settings.
 * @extends ConfigurationBuilder<FieldConfig>
 */
class FieldDeclarationBuilder extends ConfigurationBuilder
{
    use FieldIdentityMethodsTrait;
    use FieldTextMethodsTrait;
    use FieldValueMethodsTrait;
    use FieldStateMethodsTrait;
    use FieldChoicesMethodsTrait;

    /** @param array<string, mixed> $value Configured extensionData data. */
    public function extensionData(array $value): static
    {
        $this->updateFieldConfig($this->config()->extensionData($value));

        return $this;
    }

    /** Start from a reusable immutable snapshot. */
    public function __construct(protected readonly string $path, FieldConfig $config = new FieldConfig())
    {
        parent::__construct($config);
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
    }

    /** Overlay explicitly supplied settings from the same configuration domain. */
    public function apply(FieldConfig $config): static
    {
        $this->applyConfig($config);

        return $this;
    }

    /** Compile this attached declaration without binding or rendering it. */
    public function build(): FieldDefinition
    {
        return new FieldDefinition($this->path, $this->config());
    }
}
