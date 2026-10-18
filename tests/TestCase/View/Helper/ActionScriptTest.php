<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\View\Helper;

use Cake\Http\ServerRequest;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;

/**
 * Verifies page-level asset registration independently of rendered action markup.
 */
class ActionScriptTest extends FormTestCase
{
    public function testInitializationRegistersTheAssetBeforeAnyActionsAndDeduplicatesAcrossHelpers(): void
    {
        $helper = $this->helper();
        $view = $helper->getView();
        $initial = $view->fetch('script');
        static::assertStringContainsString('/form_compose/js/actions.js', $initial);
        static::assertStringContainsString('defer', $initial);
        $other = new FormHelper($view);
        static::assertStringNotContainsString('<script', $helper->postLink(label: 'Delete', url: '/delete')->render());
        static::assertStringNotContainsString(
            '<script',
            $other->postButton(label: 'Delete', url: '/delete')->confirm('Sure?')->render(),
        );
        static::assertSame($initial, $view->fetch('script'));
    }

    public function testAutomaticLoadingCanBeDisabledAndManuallyRegistered(): void
    {
        $view = new View(new ServerRequest());
        $helper = new FormHelper($view, ['autoActionScript' => false]);
        $html = $helper->postLink(label: 'Delete', url: '/delete')->confirm('Sure?')->render();
        static::assertStringContainsString('data-form-compose-confirm="Sure?"', $html);
        static::assertStringNotContainsString('<script', $html);
        static::assertSame('', $view->fetch('script'));
        $htmlHelper = $view->loadHelper('Html');
        assert($htmlHelper instanceof HtmlHelper, 'The fixture uses CakePHP HtmlHelper.');
        $htmlHelper->script('FormCompose.actions', ['block' => 'script', 'defer' => true]);
        static::assertStringContainsString('/form_compose/js/actions.js', $view->fetch('script'));
    }
}
