<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use Stringable;

/** An explicitly renderable view object; string conversion renders at the same boundary. */
interface RenderableInterface extends Stringable
{
    /** Produce markup, committing registrations only after successful rendering. */
    public function render(): string;
}
