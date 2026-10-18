<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\ActionConfig;
use FormCompose\Rendering\RenderableInterface;

/** Configures one action occurrence and renders it only on demand. */
class ActionBuilder extends ActionConfigBuilder implements RenderableInterface
{
    /** @param \Closure(ActionConfig): string $render Rendering boundary retaining its owning session. */
    public function __construct(
        protected readonly Closure $render,
        ActionConfig|ActionConfigBuilder $config = new ActionConfig(),
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
