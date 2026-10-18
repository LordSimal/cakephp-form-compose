<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use FormCompose\Configuration\SummaryConfig;
use FormCompose\Form\FormSession;
use FormCompose\Form\InputRegistry;
use function Cake\I18n\__;

/**
 * Renders committed feedback through an application-owned summary element.
 *
 * It never resolves fields or changes submitted names. Inject a subclass through
 * FormSession's optional summary renderer to specialize presentation preparation.
 */
class ErrorSummaryRenderer
{
    /**
     * Use the same theme and renderer as fields, forms, and repeated layouts.
     */
    public function __construct(protected readonly RenderContext $rendering)
    {
    }

    /**
     * Read feedback and render one summary, reserving only its emitted presentation IDs.
     *
     */
    public function render(FormSession $session, InputRegistry $inputs, SummaryConfig $config): string
    {
        $entries = array_values($inputs->feedback->entries());
        if ($entries === []) {
            return '';
        }

        $title = $config->title ?? __('Please correct the following errors.');
        [$attributes, $titleAttributes] = $this->attributes($session->binding->id, $config);

        return $inputs->render(function () use (
            $inputs,
            $session,
            $config,
            $entries,
            $title,
            $attributes,
            $titleAttributes,
        ): string {
            $html = $this->rendering->renderer->render(
                'control',
                new RenderNode(
                    'error-summary',
                    'default',
                    [
                        'form' => $session,
                        'entries' => $entries,
                        'title' => $title,
                        'attributes' => $attributes,
                        'titleAttributes' => $titleAttributes,
                    ],
                    $config->presentation,
                ),
            );
            if ($titleAttributes->has('id')) {
                $inputs->registerIds([(string)$titleAttributes->values['id']]);
            }

            return $html;
        }, $attributes);
    }

    /**
     * Associate the heading with its summary, respecting explicitly omitted identities.
     *
     * @return array{\FormCompose\Rendering\Attributes, \FormCompose\Rendering\Attributes}
     */
    protected function attributes(string $bindingId, SummaryConfig $config): array
    {
        $configured = $config->presentation->attributes;
        $parts = $config->presentation->partAttributes;
        $attributes = $this->rendering
            ->theme
            ->attributes('error-summary.control', 'default')
            ->merge(['id' => $bindingId . '-error-summary'])
            ->merge($configured);
        $id = $attributes->has('id') ? (string)$attributes->values['id'] : $bindingId . '-error-summary';
        $title = $this->rendering
            ->theme
            ->attributes('error-summary.title', 'default')
            ->merge(['id' => $id . '-title'])
            ->merge($parts['title'] ?? []);
        if ($title->has('id')) {
            $attributes = $attributes->merge(['aria-labelledby' => (string)$title->values['id']]);
        }

        return [$attributes, $title];
    }
}
