<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Http\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\View\Form\ArrayContext;
use Cake\View\View;
use FormCompose\Rendering\Theme;
use FormCompose\View\Helper\FormHelper;

/**
 * Exercises application-owned markup and real Cake form protection over HTTP.
 */
class FormsController extends Controller
{
    /**
     * Run the real component's token checks before either action.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('FormProtection');
        $this->disableAutoRender();
    }

    /**
     * Render representative native controls using the documented starter theme.
     */
    public function index(): Response
    {
        $helper = new FormHelper(new View($this->getRequest()));
        $form = $helper->bind(
            new ArrayContext(['errors' => ['email' => ['invalid' => 'Check your email']]]),
            theme: new Theme(
                layers: ['forms/starter'],
                defaults: [
                    'input' => ['class' => 'field-input'],
                    'choice' => ['class' => 'choice-input'],
                    'label' => ['class' => 'field-label'],
                    'help' => ['class' => 'field-help'],
                    'errors' => ['class' => 'field-errors'],
                ],
            ),
        );
        $html = $form->open(['url' => '/forms/save', 'type' => 'file']);
        $html .= $form->control('id', ['type' => 'hidden', 'value' => 42]);
        $html .= $form->control('empty_select', ['type' => 'select', 'options' => [], 'empty' => false]);
        $html .= $form->control('author_id', [
            'options' => [1 => 'Ada', 'Authors' => [2 => 'Grace', '' => 'Unassigned']],
            'value' => null,
        ]);
        $html .= $form->control('topics', [
            'type' => 'multicheckbox',
            'options' => ['a' => 'A', 'b' => 'B'],
            'required' => true,
            'value' => ['a'],
        ]);
        $html .= $form->control('active', ['type' => 'checkbox', 'value' => false]);
        $html .= $form->control('visibility', [
            'type' => 'radio',
            'options' => ['public' => 'Public', 'private' => 'Private'],
        ]);
        $html .= $form->control('tags._ids', ['options' => [1 => 'PHP', 2 => 'CakePHP'], 'multiple' => true]);
        $html .= $form->control('email', [
            'type' => 'email',
            'value' => 'reader@example.test',
            'help' => 'Work address',
            'partAttributes' => ['help' => ['id' => 'email-hint'], 'errors' => ['id' => 'email-feedback']],
        ]);
        $html .= $form->control('author.name', ['value' => 'Ada']);
        $html .= $form->control('bio', ['type' => 'textarea', 'value' => 'Hello <world>']);
        $html .= $form->control('attachment', ['type' => 'file']);
        $html .= $form->control('disabled', ['disabled' => true]);
        $html .= $form->submit('Save') . $form->close();

        return $this->getResponse()
            ->withType('html')
            ->withStringBody(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Form fixture</title></head><body>'
                    . $html
                    . '</body></html>',
            );
    }

    /**
     * Return only submissions accepted by CSRF and FormProtectionComponent.
     */
    public function save(): Response
    {
        $this->getRequest()->allowMethod(['post']);

        return $this->getResponse()
            ->withType('json')
            ->withStringBody(
                json_encode($this->getRequest()->getData(), JSON_THROW_ON_ERROR),
            );
    }
}
