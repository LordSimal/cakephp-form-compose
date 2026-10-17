<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use Cake\View\View;
use Closure;
use FormCompose\Form\FormSession;
use FormCompose\Form\PostAction;
use FormCompose\Form\PostActionSubmission;

/**
 * Builds independent protected action forms through the helper's binding factory.
 *
 * Form sessions own hidden inputs, tokens, and button submissions. This service
 * places complete forms inline or in view blocks and renders link parts.
 * Override trigger() via FormHelper::createPostActionRenderer().
 */
class PostActionRenderer
{
    /**
     * Retain the view and ordinary binding entry point, preserving session overrides.
     *
     * @param \Closure(array<string, mixed>, \FormCompose\Rendering\RenderContext): \FormCompose\Form\FormSession $bind Session factory.
     */
    public function __construct(protected readonly View $view, protected readonly Closure $bind)
    {
    }

    /**
     * Return a complete action without changing an existing form session.
     *
     * @param array|string|null $url Cake route or URL.
     * @param array<string, mixed> $options Action metadata and trigger presentation.
     */
    public function render(
        string|Html $label,
        array|string|null $url,
        array $options,
        string $kind,
        RenderContext $rendering,
    ): string {
        $action = new PostAction($kind, $url, $options);
        $form = ($this->bind)($action->form, $rendering);
        $html = $form->open();
        /** @var mixed $value */
        foreach ($action->data as $path => $value) {
            $html .= $form->control($path, ['type' => 'hidden', 'value' => $value, 'secure' => true]);
        }

        $trigger = $this->trigger($label, $action, $form, $rendering);
        $inside = $kind === 'button' && $action->block === null;
        $html .= $inside ? $trigger : '';
        $html .= $form->close();
        if ($action->block !== null) {
            $this->view->append($action->block, $html);
            $html = '';
        }

        return $html . ($inside ? '' : $trigger);
    }

    /**
     * Reuse normal button rendering or render a link associated with the action form.
     */
    protected function trigger(
        string|Html $label,
        PostAction $action,
        FormSession $form,
        RenderContext $rendering,
    ): string {
        $options = $action->options;
        if ($action->kind === 'button') {
            PostActionSubmission::button($action->data, $options['name'] ?? null);

            return $form->submit($label, ['form' => $form->binding->id] + $options);
        }

        $presentation = array_intersect_key($options, array_flip(['templates', 'variant', 'templateData']));
        unset($options['templates'], $options['variant'], $options['templateData'], $options['type']);
        $attributes = $rendering
            ->theme
            ->attributes('action.control', 'link')
            ->merge($options)
            ->replace([
                'href' => '#' . rawurlencode($form->binding->id),
                'data-form-compose-action' => $form->binding->id,
            ]);

        return $rendering->renderer->render('control', new RenderNode(
            'action',
            'link',
            ['form' => $form, 'label' => $label, 'attributes' => $attributes],
            $presentation,
        ));
    }
}
