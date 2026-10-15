<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

/**
 * Framework-independent presentation dependencies for one form.
 */
final readonly class RenderContext
{
    /**
     * Collect the renderer, theme, and optional custom field implementations.
     *
     * @param array<string, \FormCompose\Field\FieldTypeInterface> $types Registered custom types.
     */
    public function __construct(public RendererInterface $renderer, public Theme $theme, public array $types = [])
    {
    }
}
