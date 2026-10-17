<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use Cake\View\View;
use Closure;
use RuntimeException;

/**
 * Renders form parts as CakePHP elements using a Theme's ordered layers.
 *
 * An explicit template override takes priority. Otherwise each layer is searched
 * for a variant, type-specific, then generic part, with bundled base elements last.
 * Elements receive RenderNode variables, including the templateData bag, and a
 * $parent() closure that renders the same part in the next layer, or resumes
 * theme lookup after an explicit override.
 * Optional themeDebug comments describe actual lookup attempts without exposing values.
 * Subclasses can specialize layered() for lookup or element() for execution.
 */
class PhpRenderer implements RendererInterface
{
    /**
     * Use the Cake view to render elements from the selected theme.
     */
    public function __construct(
        protected readonly View $view,
        public readonly Theme $theme = new Theme(),
        public readonly bool $themeDebug = false,
    ) {
    }

    /**
     * Render explicit templates or search the theme layers for the prepared node.
     */
    public function render(string $part, RenderNode $node): string
    {
        /** @var array<string, string> $templates */
        $templates = $node->options['templates'] ?? [];
        if (isset($templates[$part])) {
            $element = $templates[$part];
            $html = $this->element($element, $node, fn(): string => $this->renderParent($part, $node));

            return $this->debug($html, $part, $node, [
                'source' => 'explicit override',
                'element' => $element,
                'lookup' => [],
            ]);
        }

        return $this->layered($part, $node);
    }

    /**
     * Search each layer by variant, type, then generic scoped part.
     */
    protected function layered(string $part, RenderNode $node, int $start = 0): string
    {
        $names = TemplateNames::resolve($part, $node);
        $layers = [...$this->theme->layers, 'FormCompose.form/base'];
        $lookup = [];
        for ($index = $start, $count = count($layers); $index < $count; $index++) {
            foreach ($names as $name) {
                $element = $layers[$index] . '/' . $name;
                $exists = $this->view->elementExists($element);
                if ($this->themeDebug) {
                    $lookup[] = ($exists ? 'MATCH ' : 'MISS ') . $element;
                }

                if ($exists) {
                    $html = $this->element($element, $node, fn(): string => $this->renderParent(
                        $part,
                        $node,
                        $index + 1,
                    ));

                    return $this->debug($html, $part, $node, [
                        'source' => 'layer ' . $layers[$index],
                        'element' => $element,
                        'lookup' => $lookup,
                    ]);
                }
            }
        }

        throw new RuntimeException('No template for ' . $node->scope . ' part: ' . $part);
    }

    /**
     * Supply prepared variables and a parent-part callback to an ordinary Cake element.
     */
    protected function element(string $element, RenderNode $node, Closure $parent): string
    {
        return $this->view->element($element, array_replace($node->variables, ['parent' => $parent]));
    }

    /**
     * Resume ordinary lookup, marking delegation only when it produces output.
     */
    protected function renderParent(string $part, RenderNode $node, int $start = 0): string
    {
        $html = $this->layered($part, $node, $start);

        return $this->themeDebug && $html !== '' ? '<!-- FormCompose PARENT -->' . $html : $html;
    }

    /**
     * Annotate a successful element with presentation metadata only; preserve empty output.
     *
     * @param array{source: string, element: string, lookup: list<string>} $details Actual resolution results.
     */
    protected function debug(string $html, string $part, RenderNode $node, array $details): string
    {
        return $this->themeDebug
            ? new TemplateTrace($part, $details['element'], $details['source'], $details['lookup'])->wrap($html, $node)
            : $html;
    }
}
