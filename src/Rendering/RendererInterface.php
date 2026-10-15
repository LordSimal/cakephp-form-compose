<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

/**
 * Unified template boundary for fields, form lifecycle markup, and buttons.
 */
interface RendererInterface
{
    /**
     * Render a named part from its prepared data without performing binding or security work.
     */
    public function render(string $part, RenderNode $node): string;
}
