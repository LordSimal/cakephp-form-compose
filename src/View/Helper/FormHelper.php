<?php
declare(strict_types=1);

namespace FormCompose\View\Helper;

use Cake\View\Form\ContextFactory;
use Cake\View\Form\ContextInterface;
use Cake\View\Helper;
use FormCompose\Binding\FormBinding;
use FormCompose\Binding\TypeMap;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Blueprint\FormContext;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Builder\FormBuilder;
use FormCompose\Configuration\FormConfig;
use FormCompose\Form\FormIds;
use FormCompose\Form\FormSession;
use FormCompose\Rendering\PostActionRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\Theme;
use FormCompose\View\PostActionsTrait;
use InvalidArgumentException;

/**
 * Creates bound form sessions and renders complete forms from a CakePHP view.
 *
 * form()->bind() selects a CakePHP form context for the supplied data and collects the
 * form's theme, renderer, and custom field types. Each returned FormSession has
 * its own field resolution, rendering, and submission lifecycle. Override
 * createSession() to select an application-specific FormSession subclass.
 *
 * ```php
 * $form = $this->Form->form($article)->url(['action' => 'edit', $article->id])->bind();
 * echo $form->open(), $form->control('title'), $form->submit('Save'), $form->close();
 * ```
 *
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class FormHelper extends Helper
{
    use PostActionsTrait;

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

    /** Configure a form before binding data or rendering markup. */
    public function form(mixed $data = null, ?FormConfig $config = null): FormBuilder
    {
        return $this->createFormBuilder($data, $config ?? new FormConfig());
    }

    /** Supply the fluent entry point through an overridable factory. */
    protected function createFormBuilder(mixed $data, FormConfig $config): FormBuilder
    {
        return new FormBuilder(fn(FormConfig $settings): FormSession => $this->bindForm($data, $settings), $config);
    }

    /**
     * Create a form session for the given context and presentation.
     *
     */
    protected function bindForm(mixed $data, FormConfig $config): FormSession
    {
        $view = $this->getView();
        /** @var list<string> $passwordFields */
        $passwordFields = $this->getConfig('passwordFields', FormBinding::DEFAULT_PASSWORD_FIELDS);
        $config = FormConfig::builder()->passwordFields($passwordFields)->merge($config);
        $config = $config->typeMap(array_replace(
            TypeMap::normalize($this->getConfig('typeMap', [])),
            $config->typeMap,
        ));
        $types = $config->types;
        /** @var ContextFactory|null $contextFactory */
        $contextFactory = $this->getConfig('contextFactory');
        $contextFactory = $contextFactory instanceof ContextFactory
            ? $contextFactory
            : ContextFactory::createWithDefaults();
        $contextOptions = $config->context;
        $context = $data instanceof ContextInterface
            ? $data
            : $contextFactory
                ->get($view->getRequest(), ['entity' => $data] + $contextOptions);
        $theme = $this->resolveTheme($config->theme);

        $id = ($this->formIds ??= $this->createFormIds())->allocate(
            $config->id,
        );
        $factory = $this->createBlueprintFactory();
        $valueSources = $config->valueSources;
        $formContext = new FormContext($context, $view->getRequest(), valueSources: $valueSources);
        $compiled = array_map(
            static fn(string $blueprint): FormDefinition => $factory->compile($blueprint, $formContext),
            $config->blueprints,
        );

        $types = $factory->lazyTypes($types);

        return $this->createSession(
            new FormBinding($context, $view->getRequest(), $id, $config, $compiled),
            RenderContext::forView(
                $view,
                $theme,
                $config->renderer,
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
     * this factory without changing form() or session construction.
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

        /** @var mixed $selected */
        return match ($selected) {
            null, 'Base' => Theme::base(),
            default => $selected instanceof Theme
                ? $selected
                : throw new InvalidArgumentException('Use Base or a Theme instance.'),
        };
    }
}
