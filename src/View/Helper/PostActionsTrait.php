<?php
declare(strict_types=1);

namespace FormCompose\View\Helper;

use Cake\View\Helper\HtmlHelper;
use FormCompose\Builder\ActionBuilder;
use FormCompose\Builder\PostButtonBuilder;
use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Form\FormSession;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\PostActionRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\Theme;

/**
 * Authors standalone protected actions and initializes their optional enhancement.
 * @require-extends \FormCompose\View\Helper\FormHelper
 */
trait PostActionsTrait
{
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
     */
    public function postButton(
        string|Html $label,
        array|string $url,
        ?ActionConfig $config = null,
        Theme|string|null $theme = null,
        ?RendererInterface $renderer = null,
    ): PostButtonBuilder {
        return new PostButtonBuilder(fn(ActionConfig $settings): string => ($this->postActions ??=
            $this->createPostActionRenderer())->render(
            $label,
            $url,
            $settings,
            'button',
            RenderContext::forView(
                $this->getView(),
                $this->resolveTheme($theme ?? $settings->form->theme),
                $renderer ?? $settings->form->renderer,
                themeDebug: $this->getConfig('themeDebug') === true,
            ),
        ), $config ?? new ActionConfig());
    }

    /**
     * Render a protected action link using the page's delegated JavaScript enhancement.
     *
     */
    public function postLink(
        string|Html $label,
        array|string|null $url = null,
        ?ActionConfig $config = null,
        Theme|string|null $theme = null,
        ?RendererInterface $renderer = null,
    ): ActionBuilder {
        return new ActionBuilder(fn(ActionConfig $settings): string => ($this->postActions ??=
            $this->createPostActionRenderer())->render(
            $label,
            $url,
            $settings,
            'link',
            RenderContext::forView(
                $this->getView(),
                $this->resolveTheme($theme ?? $settings->form->theme),
                $renderer ?? $settings->form->renderer,
                themeDebug: $this->getConfig('themeDebug') === true,
            ),
        ), $config ?? new ActionConfig());
    }

    /**
     * Replace action placement or trigger rendering through the helper's normal extension point.
     */
    protected function createPostActionRenderer(): PostActionRenderer
    {
        return new PostActionRenderer(
            $this->getView(),
            fn(FormConfig $config, RenderContext $rendering): FormSession => $this->bindForm(
                null,
                $config->theme($rendering->theme)->renderer($rendering->renderer),
            ),
        );
    }
}
