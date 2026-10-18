<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\EmbedConfig;

/** Selects and presents a repeated occurrence before its row blueprints are compiled for rendering. */
class RepeatedEmbedBuilder extends EmbedBuilder
{
    use RowSelectionTrait;

    /** @param \Closure(EmbedConfig, list<int|string>|null): string $render Compile and render the selected rows. */
    public function __construct(Closure $render, EmbedConfig|EmbedConfigBuilder $config = new EmbedConfig())
    {
        parent::__construct(fn(EmbedConfig $settings): string => $render($settings, $this->selectedKeys), $config);
    }
}
