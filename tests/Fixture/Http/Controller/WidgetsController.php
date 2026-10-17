<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Http\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\Routing\Attribute\Get;
use Cake\Routing\Attribute\Post;
use Cake\Routing\Attribute\Scope;
use Cake\View\Form\ArrayContext;
use Cake\View\View;
use FormCompose\Test\Fixture\Widget\DateRangeType;
use FormCompose\Test\Fixture\Widget\MarkdownType;
use FormCompose\View\Helper\FormHelper;

/**
 * Serves custom PHP widgets through Cake's genuine CSRF and form protection stack.
 */
#[Scope('/widgets')]
class WidgetsController extends Controller
{
    /**
     * Enable Cake token validation for actual browser submissions.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('FormProtection');
        $this->disableAutoRender();
    }

    /**
     * Demonstrate reordered composite parts and an editor with no required JavaScript.
     */
    #[Get('')]
    public function index(): Response
    {
        $form = new FormHelper(new View($this->getRequest()))->bind(
            new ArrayContext([
                'errors' => ['period' => ['invalid' => 'Check dates']],
            ]),
            ['types' => ['range' => new DateRangeType(), 'markdown' => new MarkdownType()]],
        );
        $html = $form->open(['url' => '/widgets/save']);
        $controls = $form->control('period', [
            'type' => 'range',
            'help' => 'Choose your stay',
            'value' => ['start' => '2026-10-01', 'end' => '2026-10-17'],
            'templates' => ['composite' => 'widgets/inline'],
        ]);
        $controls .= $form->control('body', ['type' => 'markdown', 'value' => 'Hello <world>']);
        $html .= $form->errorSummary() . $controls . $form->submit('Save widgets') . $form->close();

        return $this->getResponse()
            ->withType('html')
            ->withStringBody(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Widgets</title></head><body>'
                    . $html
                    . '</body></html>',
            );
    }

    /**
     * Return only successfully protected application data.
     */
    #[Post('/save')]
    public function save(): Response
    {
        /** @var array<string, mixed> $data */
        $data = $this->getRequest()->getData();
        unset($data['_Token'], $data['_csrfToken']);

        return $this->getResponse()->withType('json')->withStringBody(json_encode($data, JSON_THROW_ON_ERROR));
    }
}
