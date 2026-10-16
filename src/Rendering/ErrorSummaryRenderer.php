<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use FormCompose\Binding\FieldOptions;
use FormCompose\Form\InputRegistry;
use FormCompose\FormSession;
use InvalidArgumentException;
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
     * @param array<string, mixed> $options Title and standard template/attribute presentation.
     */
    public function render(FormSession $session, InputRegistry $inputs, array $options): string
    {
        $entries = array_values($inputs->feedback->entries());
        if ($entries === []) {
            return '';
        }

        FieldOptions::validate($options);
        $options = TemplateOptions::normalize($options);
        /** @var mixed $title */
        $title = $options['title'] ?? __('Please correct the following errors.');
        if (!is_string($title) && !$title instanceof Html) {
            throw new InvalidArgumentException('An error summary title must be text or trusted Html.');
        }

        [$attributes, $titleAttributes] = $this->attributes($session->binding->id, $options);

        return $inputs->render(function () use (
            $inputs,
            $session,
            $options,
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
                    $options,
                ),
            );
            if ($titleAttributes->has('id')) {
                $inputs->registerIds([(string)$titleAttributes->values['id']]);
            }

            return $html;
        }, $attributes);
    }

    /**
     * Extract opt-in summary presentation for the complete-form shortcut.
     *
     * @param array<string, mixed> $options Local opening options.
     * @param array<string, mixed> $defaults Binding options.
     * @return array<string, mixed>|null Null disables the summary; [] uses defaults.
     */
    public function options(array $options, array $defaults): ?array
    {
        /** @var mixed $summary */
        $summary = array_key_exists('errorSummary', $options)
            ? $options['errorSummary']
            : $defaults['errorSummary'] ?? false;
        if ($summary === false) {
            return null;
        }

        if ($summary === true) {
            return [];
        }

        if (!is_array($summary)) {
            throw new InvalidArgumentException('The errorSummary option must be a boolean or an options array.');
        }

        /** @var array<string, mixed> $summary */
        return $summary;
    }

    /**
     * Associate the heading with its summary, respecting explicitly omitted identities.
     *
     * @param array<string, mixed> $options Summary presentation.
     * @return array{\FormCompose\Rendering\Attributes, \FormCompose\Rendering\Attributes}
     */
    protected function attributes(string $bindingId, array $options): array
    {
        /** @var array<string, mixed> $configured */
        $configured = $options['attributes'] ?? [];
        /** @var array<string, array<string, mixed>> $parts */
        $parts = $options['partAttributes'] ?? [];
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
