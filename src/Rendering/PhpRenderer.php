<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use Cake\View\View;
use Closure;
use RuntimeException;

/**
 * Resolves scoped Cake PHP elements with deterministic theme and parent-part lookup.
 */
final readonly class PhpRenderer implements RendererInterface
{
    /**
     * Bind a renderer to a Cake view and framework-independent theme.
     */
    public function __construct(private View $view, public Theme $theme = new Theme())
    {
    }

    /**
     * Render explicit templates or search the theme layers for the prepared node.
     */
    public function render(string $part, RenderNode $node): string
    {
        /** @var array<string, string> $templates */
        $templates = $node->options['templates'] ?? [];
        if (isset($templates[$part])) {
            return $this->element($templates[$part], $node, fn(): string => $this->layered($part, $node));
        }

        return $this->layered($part, $node);
    }

    /**
     * Search each layer by variant, type, then generic scoped part.
     */
    private function layered(string $part, RenderNode $node, int $start = 0): string
    {
        $names = TemplateNames::resolve($part, $node);
        $layers = [...$this->theme->layers, 'FormCompose.form/base'];
        for ($index = $start, $count = count($layers); $index < $count; $index++) {
            foreach ($names as $name) {
                $element = $layers[$index] . '/' . $name;
                if ($this->view->elementExists($element)) {
                    return $this->element($element, $node, fn(): string => $this->layered($part, $node, $index + 1));
                }
            }
        }
        throw new RuntimeException('No template for ' . $node->scope . ' part: ' . $part);
    }

    /**
     * Supply prepared variables and a parent-part callback to an ordinary Cake element.
     */
    private function element(string $element, RenderNode $node, Closure $parent): string
    {
        return $this->view->element($element, array_replace($node->variables, ['parent' => $parent]));
    }
}
