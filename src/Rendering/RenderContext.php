<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use Cake\View\View;
use Closure;

/**
 * Groups the presentation dependencies shared by a form and its fields.
 *
 * FormHelper creates it when binding a form. FormSession and FieldResolver use its
 * renderer and theme; the types map supplies named FieldTypeInterface instances
 * for custom controls. The optional viewVariable callback reads live view variables
 * for conventional choice discovery. Request data and entity metadata live in FormBinding.
 */
final readonly class RenderContext
{
    /**
     * Collect the renderer, theme, and optional custom field implementations.
     *
     * @param array<string, \FormCompose\Field\FieldTypeInterface> $types Registered custom types.
     * @param \Closure(string): mixed|null $viewVariable Live view-variable lookup for choice lists.
     */
    public function __construct(
        public RendererInterface $renderer,
        public Theme $theme,
        public array $types = [],
        public ?Closure $viewVariable = null,
    ) {
    }

    /**
     * Assemble view-backed rendering for bound forms and standalone actions.
     *
     * themeDebug configures only the default PHP renderer. Explicit renderers keep
     * their own diagnostic behavior and are retained without wrapping or mutation.
     *
     * @param array<string, \FormCompose\Field\FieldTypeInterface> $types Registered custom types.
     */
    public static function forView(
        View $view,
        Theme $theme,
        ?RendererInterface $renderer = null,
        array $types = [],
        bool $themeDebug = false,
    ): self {
        return new self($renderer ?? new PhpRenderer($view, $theme, $themeDebug), $theme, $types, $view->get(...));
    }
}
