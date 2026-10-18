<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use Closure;
use FormCompose\Configuration\SummaryConfig;
use FormCompose\Form\FormSession;
use FormCompose\Form\FormState;
use function Cake\I18n\__;

/** Buffers a form body before summary/layout rendering while its session remains open. */
class CompleteFormRenderer
{
    /** Reuse the enclosing session's rendering pipeline. */
    public function __construct(protected readonly RenderContext $rendering)
    {
    }

    /** @param \Closure(): FormState $state Read the successfully opened form state. */
    public function render(FormSession $form, Closure $state): string
    {
        $config = $form->binding->config;
        $html = $form->open();
        $body = $form->blueprint()->render();
        $summaryConfig = $config->errorSummary;
        $summary = $summaryConfig === false
            ? ''
            : $form->errorSummary($summaryConfig instanceof SummaryConfig ? $summaryConfig : null)->render();
        $state = $state();
        $html .= $this->rendering->renderer->render(
            'layout',
            new RenderNode(
                'form',
                $state->method,
                [
                    'form' => $form,
                    'state' => $state,
                    'body' => $body,
                    'summary' => $summary,
                    'submit' => $config->submit ?? __('Submit'),
                    'submitConfig' => $config->submitConfig,
                ],
                $state->presentation,
            ),
        );

        return $html . $form->close();
    }
}
