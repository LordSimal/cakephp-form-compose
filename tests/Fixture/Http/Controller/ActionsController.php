<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Http\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\View\View;
use FormCompose\View\Helper\FormHelper;

/**
 * Exercises standalone actions alongside a parent form through real HTTP security.
 */
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
    public function index(): Response
    {
        $request = $this->getRequest()->withAttribute('cspScriptNonce', 'fixture-actions');
        $view = new View($request);
        $helper = new FormHelper($view);
        $html = $helper->postButton('Native delete', '/actions/save', [
            'method' => 'delete',
            'data' => ['version' => 0, 'record' => ['id' => 42]],
            'form' => ['id' => 'native-action'],
        ]);
        $html .= $helper->postLink('Confirm link', '/actions/save', [
            'method' => 'patch',
            'data' => ['version' => 7, 'requestSubmit' => 'locked'],
            'confirm' => 'Proceed with <record>?',
            'form' => ['id' => 'link-action'],
        ]);
        $html .= $helper->postButton('Confirm button', '/actions/save', [
            'confirm' => 'Proceed with button?',
            'form' => ['id' => 'confirm-action'],
        ]);
        $parent = $helper->bind(null, ['url' => '/actions/save', 'id' => 'parent-form']);
        $html .= $parent->open() . $parent->control('title', ['required' => true]);
        $html .= $helper->postButton('External delete', '/actions/save', [
            'method' => 'delete',
            'block' => 'actions',
            'data' => ['version' => 5],
            'form' => ['id' => 'external-action'],
        ]);
        $html .= $helper->postLink('External link', '/actions/save', [
            'method' => 'put',
            'block' => 'actions',
            'data' => ['version' => 6],
            'form' => ['id' => 'external-link'],
        ]);
        $html .= $parent->submit('Save parent') . $parent->close() . $view->fetch('actions');

        return $this->getResponse()
            ->withType('html')
            ->withHeader(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'nonce-fixture-actions'; object-src 'none'",
            )
            ->withStringBody(
                '<!DOCTYPE html><html><head><title>Actions</title></head><body>' . $html . '</body></html>',
            );
    }

    /**
     * Return the actual effective method and payload after CSRF and tamper checks.
     */
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
