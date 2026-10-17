<?php
declare(strict_types=1);

namespace FormCompose\View\Helper;

use Cake\View\Form\ContextFactory;
use Cake\View\Form\ContextInterface;
use Cake\View\Helper;
use Cake\View\Helper\HtmlHelper;
use FormCompose\Binding\FormBinding;
use FormCompose\Binding\TypeMap;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Blueprint\FormContext;
use FormCompose\Form\FormIds;
use FormCompose\Form\FormSession;
use FormCompose\Rendering\Html;
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
     * Default presentation, type inference, and page-level action enhancement.
     *
     * @var array<string, mixed>
     */
    protected array $defaultConfig = [
        'theme' => 'Base',
        'themeDebug' => false,
        'passwordFields' => FormBinding::DEFAULT_PASSWORD_FIELDS,
        'typeMap' => [],
        'autoActionScript' => true,
    ];

    protected ?FormIds $formIds = null;

    protected ?PostActionRenderer $postActions = null;

    /**
     * Register delegated action behavior in the layout's script block on helper initialization.
     *
     * Configure autoActionScript before loading the helper. Disable it when the
     * application bundles or replaces actions.js. Cake's shared HTML helper
     * handles asset deduplication, routing, and the request's CSP script nonce.
     *
     * @param array<string, mixed> $config Helper configuration.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        if ($this->getConfig('autoActionScript') === true) {
            $html = $this->getView()->loadHelper('Html');
            assert($html instanceof HtmlHelper, 'The Html helper must extend CakePHP HtmlHelper.');
            $html->script('FormCompose.actions', ['block' => 'script', 'defer' => true]);
        }
    }

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
            RenderContext::forView(
                $this->getView(),
                $this->resolveTheme($theme),
                $renderer,
                themeDebug: $this->getConfig('themeDebug') === true,
            ),
        );
    }

    /**
     * Render a protected action link using the page's delegated JavaScript enhancement.
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
            RenderContext::forView(
                $this->getView(),
                $this->resolveTheme($theme),
                $renderer,
                themeDebug: $this->getConfig('themeDebug') === true,
            ),
        );
    }

    /**
     * Bind and render a complete form using a blueprint class or alias.
     *
     * Includes a translated Submit button unless a label or false is supplied.
     * A form/layout template arranges the prepared body, summary, and actions;
     * options['template'] selects an application element for that complete layout.
     * Uses bind() and the returned session's render(), preserving application overrides.
     * For a custom theme or renderer, use bind(...)->render() instead.
     *
     * @param array<string, mixed> $options Binding options and form attributes.
     * @param array<string, mixed> $submitOptions Button attributes and presentation options.
     */
    public function render(
        string $blueprint,
        mixed $data = null,
        array $options = [],
        string|Html|false|null $submit = null,
        array $submitOptions = [],
    ): string {
        return $this->bind($data, $options, blueprint: $blueprint)
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
        ?string $blueprint = null,
    ): FormSession {
        $view = $this->getView();
        $options += ['passwordFields' => $this->getConfig('passwordFields', FormBinding::DEFAULT_PASSWORD_FIELDS)];
        $options['typeMap'] = array_replace(
            TypeMap::normalize($this->getConfig('typeMap', [])),
            TypeMap::normalize(array_key_exists('typeMap', $options) ? $options['typeMap'] : []),
        );
        /** @var array<string, \FormCompose\Form\Field\FieldTypeInterface> $types */
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

        $id = ($this->formIds ??= $this->createFormIds())->allocate(
            isset($options['id']) ? (string)$options['id'] : null,
        );
        unset($options['context'], $options['types']);
        $factory = $this->createBlueprintFactory();
        /** @var list<string> $valueSources */
        $valueSources = $options['valueSources'] ?? ['data', 'context'];
        $formContext = new FormContext($context, $view->getRequest(), valueSources: $valueSources);
        $compiled = $blueprint === null ? null : $factory->compile($blueprint, $formContext);

        $types = $factory->lazyTypes($types);

        return $this->createSession(
            new FormBinding($context, $view->getRequest(), $id, $options, $compiled),
            RenderContext::forView(
                $view,
                $theme,
                $renderer,
                $types,
                themeDebug: $this->getConfig('themeDebug') === true,
            ),
            $factory,
        );
    }

    /**
     * Construct the internal identity service with this helper's view dependency.
     */
    protected function createFormIds(): FormIds
    {
        return new FormIds($this->getView());
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
     * Replace action placement or trigger rendering through the helper's normal extension point.
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
