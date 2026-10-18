<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Configuration\PresentationConfig;
use FormCompose\Form\FeedbackEntry;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\RenderNode;

/**
 * Renders independently configurable parts against one stable field snapshot.
 * @require-extends \FormCompose\Form\Field\BoundField
 */
trait FieldPartsTrait
{
    /** Render the input part using local presentation and the stable field snapshot. */
    protected function inputMarkup(PresentationConfig $presentation): string
    {
        $attributes = $presentation->attributes;
        $this->lifecycle->assertInput($this);
        $bag = $this->attributes('input', $attributes);

        return $this->lifecycle->inputs->render(function () use ($bag, $presentation): string {
            $previous = $this->activeAttributes;
            $previousPresentation = $this->activePresentation;
            $this->activePresentation = $this->presentation->merge($presentation);
            $this->activeAttributes = $bag;
            try {
                $html = $this->renderer->render('input', $this->partNode($bag, $presentation));
            } finally {
                $this->activeAttributes = $previous;
                $this->activePresentation = $previousPresentation;
            }

            $ids = $this->fieldType instanceof InputIdentityProviderInterface
                ? $this->fieldType->inputIds($this, $bag)
                : [];
            $this->lifecycle->register($this, $this->fieldType->submissions($this, $bag), $ids);
            $this->lifecycle->inputs->references->record($this, $bag, $this->fieldType, $ids);
            $this->lifecycle->inputs->recordTarget($this, $this->fieldType, $bag);
            $this->lifecycle->inputs->feedback->record(
                $this->state->id,
                FeedbackEntry::field($this, $this->fieldType, $bag),
            );

            return $html;
        });
    }

    /** Render the label part using local presentation and the stable field snapshot. */
    protected function labelMarkup(PresentationConfig $presentation): string
    {
        $attributes = $presentation->attributes;
        if (($this->state->config->label ?? null) === false) {
            return '';
        }

        $bag = $this->attributes('label', $attributes);

        return $this->lifecycle->inputs->render(
            fn(): string => $this->renderer->render('label', $this->partNode($bag, $presentation)),
            $bag,
        );
    }

    /** Render the help part using local presentation and the stable field snapshot. */
    protected function helpMarkup(PresentationConfig $presentation): string
    {
        $attributes = $presentation->attributes;
        if (!$this->state->hasHelp()) {
            return '';
        }

        $bag = $this->attributes('help', $attributes);

        return $this->lifecycle->inputs->render(
            fn(): string => $this->renderer->render('help', $this->partNode($bag, $presentation)),
            $bag,
        );
    }

    /** Render the errors part using local presentation and the stable field snapshot. */
    protected function errorsMarkup(PresentationConfig $presentation): string
    {
        $attributes = $presentation->attributes;
        if (!$this->state->hasErrors()) {
            return '';
        }

        $bag = $this->attributes('errors', $attributes);

        return $this->lifecycle->inputs->render(
            fn(): string => $this->renderer->render('errors', $this->partNode($bag, $presentation)),
            $bag,
        );
    }

    /** Preserve field presentation while applying one part's local template overrides. */
    protected function partNode(Attributes $attributes, PresentationConfig $presentation): RenderNode
    {
        return new RenderNode(
            'field',
            $this->state->type,
            ['field' => $this, 'attributes' => $attributes],
            $this->presentation->merge($presentation),
        );
    }
}
