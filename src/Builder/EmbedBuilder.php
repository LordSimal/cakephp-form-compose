<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\EmbedConfig;
use FormCompose\Rendering\RenderableInterface;

/** Configures one embed occurrence and renders it only on demand. */
class EmbedBuilder extends EmbedConfigBuilder implements RenderableInterface
{
    /** @param \Closure(EmbedConfig): string $render Rendering boundary retaining its owning session. */
    public function __construct(
        protected readonly Closure $render,
        EmbedConfig|EmbedConfigBuilder $config = new EmbedConfig(),
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
