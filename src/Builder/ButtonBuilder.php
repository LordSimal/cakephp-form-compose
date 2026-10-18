<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\ButtonConfig;
use FormCompose\Rendering\RenderableInterface;

/** Configures one button occurrence and renders it only on demand. */
class ButtonBuilder extends ButtonConfigBuilder implements RenderableInterface
{
    /** @param \Closure(ButtonConfig): string $render Rendering boundary retaining its owning session. */
    public function __construct(
        protected readonly Closure $render,
        ButtonConfig|ButtonConfigBuilder $config = new ButtonConfig(),
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
