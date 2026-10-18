<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\BoundField;

/**
 * Configures one session field and resolves independent state without rendering.
 * @extends ConfigurationBuilder<FieldConfig>
 */
class FieldBuilder extends ConfigurationBuilder
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

    /** @param \Closure(FieldConfig): BoundField $resolve Field resolution through the owning session. */
    public function __construct(
        protected readonly Closure $resolve,
        FieldConfig $config = new FieldConfig(),
    ) {
        parent::__construct($config);
    }

    /** Resolve state without registering input names, IDs, feedback, or security data. */
    public function resolve(): BoundField
    {
        return ($this->resolve)($this->config());
    }
}
