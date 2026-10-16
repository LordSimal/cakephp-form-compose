<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Form\FeedbackEntry;
use FormCompose\Form\InputRegistry;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RenderNode;
use FormCompose\Rendering\TemplateOptions;
use LogicException;

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
        $options = array_replace(
            TemplateOptions::normalize($definition->options),
            TemplateOptions::normalize($options),
        );
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
     * Render a repeated section through an optional PHP wrapper with scoped row views.
     *
     * @param array<string, mixed> $options Occurrence overrides.
     */
    public function renderMany(
        FormSession $session,
        RepeatedDefinition $repeat,
        array $options,
        InputRegistry $inputs,
    ): string {
        $rowDefinition = $repeat->rows;
        if (!$rowDefinition instanceof FormDefinition) {
            throw new LogicException('Repeated embeds must be compiled before rendering.');
        }

        $options = array_replace(TemplateOptions::normalize($repeat->options), TemplateOptions::normalize($options));
        $repeat = new RepeatedDefinition($repeat->path, $repeat->definition, $repeat->keys, $options, $repeat->rows);
        $rows = [];
        foreach ($rowDefinition->entries as $entry) {
            if (!$entry instanceof EmbeddedDefinition) {
                continue;
            }

            $rows[$entry->path] = $this->createView(
                $session,
                $entry->definition,
                $repeat->path . '.' . $entry->path,
            );
        }

        $embed = $this->createRepeatedView($repeat, $rows, $session, $inputs);
        /** @var array<string, mixed> $configured */
        $configured = $options['attributes'] ?? [];
        $attributes = $this->rendering->theme->attributes('embed-many.control', 'default')->merge($configured);

        return $inputs->render(function () use ($embed, $rows, $attributes, $options, $inputs, $repeat): string {
            $html = $this->rendering->renderer->render('control', new RenderNode(
                'embed-many',
                'default',
                ['embed' => $embed, 'rows' => $rows, 'attributes' => $attributes],
                $options,
            ));
            $inputs->feedback->record(
                'embed-many:' . $repeat->path,
                $embed->hasErrors()
                    ? new FeedbackEntry($embed->label(), $embed->messages)
                    : null,
            );

            return $html;
        }, $attributes);
    }

    /**
     * Supply repeated feedback behavior through the same renderer extension boundary.
     *
     * @param array<int|string, \FormCompose\DefinitionView> $rows Scoped row views.
     */
    protected function createRepeatedView(
        RepeatedDefinition $repeat,
        array $rows,
        FormSession $session,
        InputRegistry $inputs,
    ): RepeatedView {
        return new RepeatedView($repeat, $rows, $session, $this->rendering, $inputs);
    }

    /**
     * Supply the layout API, preserving the original session's field extensions.
     */
    protected function createView(FormSession $session, FormDefinition $definition, string $path): DefinitionView
    {
        return new DefinitionView($session, $definition, $path);
    }
}
