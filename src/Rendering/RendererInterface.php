<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

/**
 * Defines the rendering boundary shared by fields, forms, buttons, and hidden inputs.
 *
 * Implement this to choose how a named part and its RenderNode become HTML.
 * PhpRenderer supplies CakePHP element lookup; alternative renderers can use
 * another template engine. The node provides resolved data and presentation
 * options; binding and submission registration remain with the calling session.
 */
interface RendererInterface
{
    /**
     * Render a named part from its prepared data without performing binding or security work.
     */
    public function render(string $part, RenderNode $node): string;
}
