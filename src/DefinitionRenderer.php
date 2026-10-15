<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RenderNode;
use FormCompose\Rendering\TemplateOptions;

/**
 * Renders compiled definitions through the same theme/element pipeline as controls.
 *
 * Inject a subclass into FormSession to override createView() for an application's
 * scoped layout API. Rendering and submission lifecycle remain owned by the session.
 */
class DefinitionRenderer
{
    /**
     * Retain the session's shared rendering dependencies.
     */
    public function __construct(protected readonly RenderContext $rendering)
    {
    }

    /**
     * Merge local presentation and render the selected or themed default layout.
     *
     * @param array<string, mixed> $options Per-call presentation overrides.
     */
    public function render(FormSession $session, FormDefinition $definition, string $path, array $options): string
    {
        $options = TemplateOptions::normalize(array_replace($definition->options, $options));
        /** @var array<string, mixed> $attributes */
        $attributes = $options['attributes'] ?? [];
        $node = new RenderNode(
            'definition',
            'default',
            [
                'form' => $this->createView($session, $definition, $path),
                'definition' => $definition,
                'attributes' => $this->rendering
                    ->theme
                    ->attributes('definition.control', 'default')
                    ->merge($attributes),
            ],
            $options,
        );

        return $this->rendering->renderer->render('control', $node);
    }

    /**
     * Supply the layout API, preserving the original session's field extensions.
     */
    protected function createView(FormSession $session, FormDefinition $definition, string $path): DefinitionView
    {
        return new DefinitionView($session, $definition, $path);
    }
}
