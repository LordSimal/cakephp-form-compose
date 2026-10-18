<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use FormCompose\Configuration\EmbedConfig;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Form\FeedbackEntry;
use FormCompose\Form\FormSession;
use FormCompose\Form\InputRegistry;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RenderNode;
use InvalidArgumentException;
use LogicException;

/**
 * Renders blueprint layouts from compiled definitions through the shared template pipeline.
 *
 * Inject a subclass into FormSession to override createView() for an application's
 * scoped layout API. Rendering and submission lifecycle remain owned by the session.
 */
class BlueprintRenderer
{
    /**
     * Retain the session's shared rendering dependencies.
     */
    public function __construct(protected readonly RenderContext $rendering)
    {
    }

    /**
     * Render an ordered composition while preserving each blueprint's layout and session extensions.
     *
     * An empty selection still passes an empty definition through the configured theme.
     * The caller owns the enclosing registration transaction so a failed layout rolls back all sections.
     *
     * @param list<\FormCompose\Blueprint\FormDefinition> $definitions Compiled selections.
     * @throws \InvalidArgumentException When a shared occurrence ID would be applied to several blueprints.
     */
    public function renderAll(
        FormSession $session,
        array $definitions,
        ?string $path,
        PresentationConfig $presentation,
    ): string {
        if (count($definitions) > 1 && new Attributes($presentation->attributes)->has('id')) {
            throw new InvalidArgumentException(
                'An occurrence ID cannot be applied to multiple blueprints; assign an ID to each blueprint separately.',
            );
        }

        $html = '';
        foreach ($definitions ?: [new FormDefinition([])] as $definition) {
            $html .= $session->renderDefinition($definition, $path, $presentation);
        }

        return $html;
    }

    /**
     * Merge local presentation and render the selected or themed default layout.
     *
     */
    public function render(
        FormSession $session,
        FormDefinition $definition,
        string $path,
        PresentationConfig $presentation,
    ): string {
        $presentation = $definition->presentation->merge($presentation);
        $attributes = $presentation->attributes;
        $node = new RenderNode(
            'blueprint',
            'default',
            [
                'form' => $this->createView($session, $definition, $path),
                'definition' => $definition,
                'attributes' => $this->rendering
                    ->theme
                    ->attributes('blueprint.control', 'default')
                    ->merge($attributes),
            ],
            $presentation,
        );

        return $this->rendering->renderer->render('control', $node);
    }

    /**
     * Render a repeated section through an optional PHP wrapper with scoped row views.
     *
     */
    public function renderMany(
        FormSession $session,
        RepeatedDefinition $repeat,
        EmbedConfig $config,
        InputRegistry $inputs,
    ): string {
        $rowDefinition = $repeat->rows;
        if (!$rowDefinition instanceof FormDefinition) {
            throw new LogicException('Repeated embeds must be compiled before rendering.');
        }

        $config = $repeat->config->merge($config);
        $presentation = $config->presentation;
        $repeat = new RepeatedDefinition($repeat->path, $repeat->blueprint, $repeat->keys, $config, $repeat->rows);
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
        $configured = $presentation->attributes;
        $attributes = $this->rendering->theme->attributes('embed-many.control', 'default')->merge($configured);

        return $inputs->render(function () use ($embed, $rows, $attributes, $presentation, $inputs, $repeat): string {
            $html = $this->rendering->renderer->render('control', new RenderNode(
                'embed-many',
                'default',
                ['embed' => $embed, 'rows' => $rows, 'attributes' => $attributes],
                $presentation,
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
     * @param array<int|string, \FormCompose\Blueprint\BlueprintView> $rows Scoped row views.
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
    protected function createView(FormSession $session, FormDefinition $definition, string $path): BlueprintView
    {
        return new BlueprintView($session, $definition, $path);
    }
}
