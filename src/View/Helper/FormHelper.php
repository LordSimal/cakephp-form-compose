<?php
declare(strict_types=1);

namespace FormCompose\View\Helper;

use Cake\View\Form\ContextFactory;
use Cake\View\Form\ContextInterface;
use Cake\View\Helper;
use FormCompose\Binding\FormBinding;
use FormCompose\FormSession;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\Theme;
use InvalidArgumentException;

/**
 * Creates bound form sessions from a CakePHP view.
 *
 * bind() selects a CakePHP form context for the supplied data and collects the
 * form's theme, renderer, and custom field types. Each returned FormSession has
 * its own field resolution, rendering, and submission lifecycle. Override
 * createSession() to select an application-specific FormSession subclass.
 *
 * ```php
 * $form = $this->Form->bind($article, ['url' => ['action' => 'edit', $article->id]]);
 * echo $form->open(), $form->control('title'), $form->submit('Save'), $form->close();
 * ```
 *
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class FormHelper extends Helper
{
    protected int $sequence = 0;

    /**
     * Create a form session for the given context and presentation.
     *
     * @param array<string, mixed> $options Form options, context, valueSources, and custom field types.
     */
    public function bind(
        mixed $data = null,
        array $options = [],
        Theme|string|null $theme = null,
        ?RendererInterface $renderer = null,
    ): FormSession {
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

        return $this->createSession(
            new FormBinding($context, $view->getRequest(), $id, $options),
            new RenderContext($renderer ?? new PhpRenderer($view, $theme), $theme, $types),
        );
    }

    /**
     * Create the session used by bind(); override to supply an application session subclass.
     */
    protected function createSession(FormBinding $binding, RenderContext $rendering): FormSession
    {
        return new FormSession($binding, $rendering);
    }
}
