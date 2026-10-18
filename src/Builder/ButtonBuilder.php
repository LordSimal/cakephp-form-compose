<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\ButtonConfig;
use FormCompose\Rendering\RenderableInterface;

/**
 * Configures one button occurrence and renders it only on demand.
 * @extends ConfigurationBuilder<ButtonConfig>
 */
class ButtonBuilder extends ConfigurationBuilder implements RenderableInterface
{
    use ButtonStateMethodsTrait;

    /** Set type on this mutable builder. */
    public function type(string $value): static
    {
        $this->configuration = $this->config()->type($value);

        return $this;
    }

    /** Capture the domain-specific immutable settings. */
    public function config(): ButtonConfig
    {
        return $this->configuration;
    }

    /** Overlay explicitly supplied settings from the same configuration domain. */
    public function apply(ButtonConfig $config): static
    {
        $this->applyConfig($config);

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

    /** @param \Closure(ButtonConfig): string $render Rendering boundary retaining its owning session. */
    public function __construct(
        protected readonly Closure $render,
        ButtonConfig $config = new ButtonConfig(),
    ) {
        parent::__construct($config);
    }

    /** Produce markup using the final configuration. */
    public function render(): string
    {
        return ($this->render)($this->config());
    }

    /** Render at the point of string conversion; exceptions remain visible to callers. */
    public function __toString(): string
    {
        return $this->render();
    }
}
