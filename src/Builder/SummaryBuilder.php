<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\SummaryConfig;
use FormCompose\Rendering\RenderableInterface;

/** Configures one summary occurrence and renders it only on demand. */
class SummaryBuilder extends SummaryConfigBuilder implements RenderableInterface
{
    /** @param \Closure(SummaryConfig): string $render Rendering boundary retaining its owning session. */
    public function __construct(
        protected readonly Closure $render,
        SummaryConfig|SummaryConfigBuilder $config = new SummaryConfig(),
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
