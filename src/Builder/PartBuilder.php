<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Rendering\RenderableInterface;

/** Defers rendering of one field part or scoped layout until its presentation is complete. */
class PartBuilder extends PresentationBuilder implements RenderableInterface
{
    /** @param \Closure(PresentationConfig): string $render Rendering boundary. */
    public function __construct(protected readonly Closure $render, string $part = 'control')
    {
        parent::__construct(primaryPart: $part);
    }

    /** Render through the owner, preserving its lifecycle and extension points. */
    public function render(): string
    {
        return ($this->render)($this->config());
    }

    /** Rendering through echo has the same behavior as explicit render(). */
    public function __toString(): string
    {
        return $this->render();
    }
}
