<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\ActionConfig;
use FormCompose\Rendering\RenderableInterface;

/**
 * Configures one action link and renders it only on demand; PostButtonBuilder adds button state.
 * @extends ConfigurationBuilder<ActionConfig>
 */
class ActionBuilder extends ConfigurationBuilder implements RenderableInterface
{
    use ActionMethodsTrait;

    /** @param \Closure(ActionConfig): string $render Rendering boundary retaining its owning session. */
    public function __construct(
        protected readonly Closure $render,
        ActionConfig $config = new ActionConfig(),
    ) {
        parent::__construct($config);
    }

    /** Capture the action settings independently of future edits. */
    public function config(): ActionConfig
    {
        return $this->configuration;
    }

    /** Overlay explicitly supplied action settings. */
    public function apply(ActionConfig $config): static
    {
        $this->applyConfig($config);

        return $this;
    }

    /** Capture the next protected action settings. */
    protected function updateActionConfig(ActionConfig $config): void
    {
        $this->configuration = $config;
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
