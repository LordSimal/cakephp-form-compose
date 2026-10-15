<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

/**
 * Groups the presentation dependencies shared by a form and its fields.
 *
 * FormHelper creates it when binding a form. FormSession and FieldResolver use its
 * renderer and theme; the types map supplies named FieldTypeInterface instances
 * for custom controls. Request data and entity metadata live in FormBinding.
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
