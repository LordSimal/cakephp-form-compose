<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Http\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\Routing\Attribute\Get;
use Cake\Routing\Attribute\Route;
use Cake\Routing\Attribute\Scope;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;
use FormCompose\Configuration\FormConfig;
use FormCompose\View\Helper\FormHelper;

/**
 * Exercises standalone actions alongside a parent form through real HTTP security.
 */
#[Scope('/actions')]
class ActionsController extends Controller
{
    /**
     * Enforce Cake's real action-form protection checks before saving.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('FormProtection');
        $this->disableAutoRender();
    }

    /**
     * Render native, enhanced, and externally associated actions under a strict CSP.
     */
    #[Get('')]
    public function index(): Response
    {
        $request = $this->getRequest()->withAttribute('cspScriptNonce', 'fixture-actions');
        $view = new View($request);
        $helper = new FormHelper($view);
        $html = $helper->postButton(label: 'Native delete', url: '/actions/save')
            ->method('delete')
            ->data(['version' => 0, 'record' => ['id' => 42]])
            ->form(FormConfig::builder()->id('native-action')->config())
            ->render();
        $html .= $helper->postLink(label: 'Confirm link', url: '/actions/save')
            ->method('patch')
            ->data([
                'version' => 7,
                'requestSubmit' => 'locked',
                'dispatchEvent' => 'locked',
                'isConnected' => 'locked',
            ])
            ->confirm('Proceed with <record>?')
            ->form(FormConfig::builder()->id('link-action')->config())
            ->render();
        $html .= $helper->postButton(label: 'Confirm button', url: '/actions/save')
            ->confirm('Proceed with button?')
            ->form(FormConfig::builder()->id('confirm-action')->config())
            ->name('intent')
            ->value('delete')
            ->render();
        $parent = $helper->form()->url('/actions/save')->id('parent-form')->bind();
        $html .=
            $parent->open()
            . $parent->control('title')->required(true)->render();
        $html .= $helper->postButton(label: 'External delete', url: '/actions/save')
            ->method('delete')
            ->block('actions')
            ->data(['version' => 5])
            ->form(FormConfig::builder()->id('external-action')->config())
            ->render();
        $html .= $helper->postLink(label: 'External link', url: '/actions/save')
            ->method('put')
            ->block('actions')
            ->data(['version' => 6])
            ->form(FormConfig::builder()->id('external-link')->config())
            ->render();
        $html .= $parent->submit('Save parent')->render() . $parent->close() . $view->fetch('actions');

        return $this->page($view, $html);
    }

    /**
     * Load the enhancement on a host page with no initial action markup.
     */
    #[Get('/dynamic')]
    public function dynamic(): Response
    {
        $view = new View($this->getRequest()->withAttribute('cspScriptNonce', 'fixture-actions'));
        $manual = $this->getRequest()->getQuery('manual') === '1';
        new FormHelper($view, ['autoActionScript' => !$manual]);
        if ($manual) {
            $htmlHelper = $view->loadHelper('Html');
            assert($htmlHelper instanceof HtmlHelper, 'The fixture uses CakePHP HtmlHelper.');
            $htmlHelper->script('FormCompose.actions', ['block' => 'script', 'defer' => true]);
        }

        return $this->page($view, '<div id="fragments"></div>');
    }

    /**
     * Return protected markup for innerHTML insertion, with explicit per-fragment IDs.
     */
    #[Get('/fragment')]
    public function fragment(): Response
    {
        $helper = new FormHelper(new View($this->getRequest()));
        $id = (int)$this->getRequest()->getQuery('id', '1');
        $html = $helper->postLink(label: 'Dynamic link ' . $id, url: '/actions/save')
            ->method('delete')
            ->data(['version' => $id])
            ->confirm('Delete dynamic record?')
            ->form(FormConfig::builder()->id('dynamic-link-' . $id)->config())
            ->render();
        $html .= $helper->postButton(label: 'Dynamic button ' . $id, url: '/actions/save')
            ->confirm('Delete dynamic record?')
            ->data(['version' => $id])
            ->form(FormConfig::builder()->id('dynamic-button-' . $id)->config())
            ->render();

        return $this->getResponse()->withType('html')->withStringBody($html);
    }

    /**
     * Render a layout with Cake's script block under a nonce-only script policy.
     */
    protected function page(View $view, string $html): Response
    {
        return $this->getResponse()
            ->withType('html')
            ->withHeader(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'nonce-fixture-actions'; object-src 'none'",
            )
            ->withStringBody(
                '<!DOCTYPE html><html><head><title>Actions</title>'
                    . $view->fetch('script')
                    . '</head><body>'
                    . $html
                    . '</body></html>',
            );
    }

    /**
     * Return the actual effective method and payload after CSRF and tamper checks.
     */
    #[Route('/save', methods: ['POST', 'PUT', 'PATCH', 'DELETE'])]
    public function save(): Response
    {
        $this->getRequest()->allowMethod(['post', 'put', 'patch', 'delete']);

        return $this->getResponse()
            ->withType('json')
            ->withStringBody(json_encode([
                'method' => $this->getRequest()->getMethod(),
                'data' => $this->getRequest()->getData(),
            ], JSON_THROW_ON_ERROR));
    }
}
