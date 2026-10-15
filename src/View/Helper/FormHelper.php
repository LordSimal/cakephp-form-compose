<?php
declare(strict_types=1);

namespace FormCompose\View\Helper;

use Cake\View\Form\ContextFactory;
use Cake\View\Form\ContextInterface;
use Cake\View\Helper;
use FormCompose\Binding\FormBinding;
use FormCompose\FormDefinition;
use FormCompose\FormSession;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\Theme;
use InvalidArgumentException;

/**
 * Opt-in view entry point for independent, composable form sessions.
 *
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class FormHelper extends Helper
{
    private int $sequence = 0;

    /**
     * Bind context and presentation without emitting HTML or changing legacy FormHelper state.
     *
     * @param array<string, mixed> $options Form options, context, valueSources, and custom field types.
     */
    public function bind(
        mixed $data = null,
        array $options = [],
        Theme|string|null $theme = null,
        ?FormDefinition $definition = null,
        ?RendererInterface $renderer = null,
    ): FormSession {
        if (isset($options['widgets']) || isset($options['widgetTemplates'])) {
            throw new InvalidArgumentException(
                'Use explicit field types for widget compatibility and PHP elements for native markup.',
            );
        }
        $view = $this->getView();
        /** @var array<string, \FormCompose\Field\FieldTypeInterface> $types */
        $types = $options['types'] ?? [];
        /** @var ContextFactory|null $contextFactory */
        $contextFactory = $this->getConfig('contextFactory');
        $contextFactory = $contextFactory instanceof ContextFactory
            ? $contextFactory
            : ContextFactory::createWithDefaults();
        /** @var array<string, mixed> $contextOptions */
        $contextOptions = $options['context'] ?? [];
        $context = $data instanceof ContextInterface
            ? $data
            : $contextFactory
                ->get($view->getRequest(), ['entity' => $data] + $contextOptions);
        $theme = match ($theme) {
            null, 'Base' => Theme::base(),
            default => $theme,
        };
        if (!$theme instanceof Theme) {
            throw new InvalidArgumentException('Use Base or a Theme instance.');
        }
        $id = (string)($options['id'] ?? 'form-compose-' . ++$this->sequence);
        unset($options['context'], $options['types']);

        return new FormSession(
            new FormBinding($context, $view->getRequest(), $id, $options, $definition),
            new RenderContext($renderer ?? new PhpRenderer($view, $theme), $theme, $types),
        );
    }
}
