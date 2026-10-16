<?php
declare(strict_types=1);

namespace FormCompose\View\Helper;

use Cake\View\Form\ContextFactory;
use Cake\View\Form\ContextInterface;
use Cake\View\Helper;
use FormCompose\Binding\FormBinding;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\FormContext;
use FormCompose\FormDefinition;
use FormCompose\FormSession;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\PostActionRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\Theme;
use InvalidArgumentException;

/**
 * Creates bound form sessions and renders complete forms from a CakePHP view.
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
    /**
     * Default presentation for bindings and standalone action helpers.
     *
     * @var array<string, mixed>
     */
    protected array $defaultConfig = ['theme' => 'Base'];

    protected int $sequence = 0;

    protected ?PostActionRenderer $postActions = null;

    /**
     * Render a standalone protected action using a native submit button.
     *
     * @param array|string $url Cake route or URL.
     * @param array<string, mixed> $options Method, locked data, form, block, confirm, and button presentation.
     */
    public function postButton(
        string|Html $label,
        array|string $url,
        array $options = [],
        Theme|string|null $theme = null,
        ?RendererInterface $renderer = null,
    ): string {
        return ($this->postActions ??= $this->createPostActionRenderer())->render(
            $label,
            $url,
            $options,
            'button',
            $this->actionRendering($theme, $renderer),
        );
    }

    /**
     * Render a protected action link, loading the external JavaScript enhancement once.
     *
     * @param array|string|null $url Cake route or URL; null uses the current request target.
     * @param array<string, mixed> $options Method, locked data, form, block, confirm, and link presentation.
     */
    public function postLink(
        string|Html $label,
        array|string|null $url = null,
        array $options = [],
        Theme|string|null $theme = null,
        ?RendererInterface $renderer = null,
    ): string {
        return ($this->postActions ??= $this->createPostActionRenderer())->render(
            $label,
            $url,
            $options,
            'link',
            $this->actionRendering($theme, $renderer),
        );
    }

    /**
     * Bind and render a complete form using a blueprint or ordinary definition.
     *
     * Includes a translated Submit button unless a label or false is supplied.
     * Uses bind() and the returned session's render(), preserving application overrides.
     * For a custom theme or renderer, use bind(...)->render() instead.
     *
     * @param array<string, mixed> $options Binding options and form attributes.
     * @param array<string, mixed> $submitOptions Button attributes and presentation options.
     */
    public function render(
        FormDefinition|string $definition,
        mixed $data = null,
        array $options = [],
        string|Html|false|null $submit = null,
        array $submitOptions = [],
    ): string {
        return $this->bind($data, $options, definition: $definition)
            ->render(submit: $submit, submitOptions: $submitOptions);
    }

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
        FormDefinition|string|null $definition = null,
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
        $theme = $this->resolveTheme($theme);

        $id = (string)($options['id'] ?? 'form-compose-' . ++$this->sequence);
        unset($options['context'], $options['types']);
        $factory = $this->createBlueprintFactory();
        /** @var list<string> $valueSources */
        $valueSources = $options['valueSources'] ?? ['data', 'context'];
        $formContext = new FormContext($context, $view->getRequest(), valueSources: $valueSources);
        if (is_string($definition)) {
            $definition = $factory->definition($definition, $formContext);
        }

        if ($definition instanceof FormDefinition) {
            $definition = $factory->expand($definition, $formContext);
        }

        $types = $factory->lazyTypes($types);

        return $this->createSession(
            new FormBinding($context, $view->getRequest(), $id, $options, $definition),
            new RenderContext($renderer ?? new PhpRenderer($view, $theme), $theme, $types, $view->get(...)),
            $factory,
        );
    }

    /**
     * Use the injected application factory or support dependency-free blueprints.
     *
     * Set helper option blueprintFactory to the service registered by the plugin
     * when application constructor dependencies are needed. Subclasses may override
     * this factory without changing bind() or session construction.
     */
    protected function createBlueprintFactory(): BlueprintFactory
    {
        /** @var mixed $factory */
        $factory = $this->getConfig('blueprintFactory');
        if ($factory !== null && !$factory instanceof BlueprintFactory) {
            throw new InvalidArgumentException('blueprintFactory must be a BlueprintFactory instance.');
        }

        return $factory ?? new BlueprintFactory();
    }

    /**
     * Create the session used by bind(); override to supply an application session subclass.
     * Forward the factory so render-time definitions use the same discovery and DI services.
     */
    protected function createSession(
        FormBinding $binding,
        RenderContext $rendering,
        BlueprintFactory $blueprints,
    ): FormSession {
        return new FormSession($binding, $rendering, $blueprints);
    }

    /**
     * Select a per-call override, helper default, or the bundled base theme.
     *
     * Null inherits helper configuration; Base explicitly selects the base theme.
     * Each binding retains the selected immutable theme independently of later config changes.
     */
    protected function resolveTheme(Theme|string|null $theme): Theme
    {
        /** @var mixed $selected */
        $selected = $theme ?? $this->getConfig('theme');

        return match ($selected) {
            null, 'Base' => Theme::base(),
            default => $selected instanceof Theme
                ? $selected
                : throw new InvalidArgumentException('Use Base or a Theme instance.'),
        };
    }

    /**
     * Supply action presentation without constructing a second helper or form lifecycle.
     */
    protected function actionRendering(Theme|string|null $theme, ?RendererInterface $renderer): RenderContext
    {
        $theme = $this->resolveTheme($theme);

        return new RenderContext($renderer ?? new PhpRenderer($this->getView(), $theme), $theme);
    }

    /**
     * Replace action placement or script behavior through the helper's normal extension point.
     */
    protected function createPostActionRenderer(): PostActionRenderer
    {
        return new PostActionRenderer(
            $this->getView(),
            fn(array $options, RenderContext $rendering): FormSession => $this->bind(
                null,
                $options,
                $rendering->theme,
                $rendering->renderer,
            ),
        );
    }
}
