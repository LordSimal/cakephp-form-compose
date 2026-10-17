<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Http\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\View\Form\ArrayContext;
use Cake\View\View;
use DateTimeImmutable;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\Authoring\HttpCommentForm;
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
            $this->createContext(),
            theme: $this->createTheme(),
        );
        $opening = $form->open(['url' => '/forms/save', 'type' => 'file']);
        $html = $form->control('id', ['type' => 'hidden', 'value' => 42]);
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
        $html .= $form->embedMany('comments', HttpCommentForm::class, options: [
            'template' => 'repeat/comments',
        ]);
        $html = $opening . $form->errorSummary() . $html . $form->submit('Save') . $form->close();

        return $this->getResponse()
            ->withType('html')
            ->withStringBody(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Form fixture</title></head><body>'
                    . $html
                    . '</body></html>',
            );
    }

    /**
     * Supply realistic values and feedback to the HTTP rendering fixture.
     */
    protected function createContext(): ArrayContext
    {
        return new ArrayContext([
            'errors' => [
                'email' => ['invalid' => 'Check your email'],
                'topics' => ['invalid' => 'Choose topics'],
                'visibility' => ['invalid' => 'Choose visibility'],
                'comments' => [
                    'count' => 'Add another comment',
                    2 => ['body' => ['invalid' => 'Fix the third comment']],
                ],
            ],
            'defaults' => [
                'comments' => [0 => ['id' => 10, 'body' => 'First'], 2 => ['id' => 12, 'body' => 'Third']],
            ],
        ]);
    }

    /**
     * Render temporal objects for browser normalization and protected round trips.
     */
    public function temporal(): Response
    {
        $helper = new FormHelper(new View($this->getRequest()));
        $form = $helper->bind(new ArrayContext([
            'schema' => [
                'day' => ['type' => 'date'],
                'clock' => ['type' => 'time'],
                'instant' => ['type' => 'datetimefractional'],
                'fractional_clock' => ['type' => 'time'],
                'fractional_instant' => ['type' => 'datetime'],
            ],
            'data' => [
                'day' => new Date('2026-10-15'),
                'clock' => new Time('14:23:45'),
                'instant' => new DateTimeImmutable('2026-10-15 14:23:45.123'),
                'fractional_clock' => new Time('00:00:01.500000'),
                'fractional_instant' => new DateTimeImmutable('2026-10-15 00:00:02.500'),
            ],
        ]));
        $html =
            $form->open(['url' => '/forms/save'])
            . $form->controls([
                'day',
                'clock',
                'instant',
                'fractional_clock' => ['attributes' => ['step' => '1.5', 'min' => '00:00:00']],
                'fractional_instant' => ['attributes' => ['step' => '2.5', 'min' => '2026-10-15T00:00:00']],
            ]);
        $html .= $form->control('week', ['type' => 'week', 'value' => new Date('2027-01-01')]);
        $html .= $form->submit('Save temporal') . $form->close();

        return $this->getResponse()
            ->withType('html')
            ->withStringBody('<!DOCTYPE html><html><body>' . $html . '</body></html>');
    }

    /**
     * Compose inputs with both rendered and handwritten accessible feedback.
     */
    public function composition(): Response
    {
        $helper = new FormHelper(new View($this->getRequest()));
        $form = $helper->bind(new ArrayContext(['errors' => ['title' => ['invalid' => 'Review the title']]]));
        $field = $form->field('title', ['help' => 'Use a descriptive title', 'value' => 'Example']);
        $html = $form->open(['url' => '/forms/save']) . $field->label() . $field->input();
        $html .= '<p' . (string)$field->attributes('help') . '>Use a descriptive title</p>';
        $form->registerFeedback($field, 'help');
        $html .= $field->errors() . $form->submit('Save composition') . $form->close();

        return $this->getResponse()
            ->withType('html')
            ->withStringBody('<!DOCTYPE html><html><body>' . $html . '</body></html>');
    }

    /**
     * Share the application-owned starter theme across HTTP fixture actions.
     */
    protected function createTheme(): Theme
    {
        return new Theme(
            layers: ['forms/starter'],
            defaults: [
                'input' => ['class' => 'field-input'],
                'choice' => ['class' => 'choice-input'],
                'label' => ['class' => 'field-label'],
                'help' => ['class' => 'field-help'],
                'errors' => ['class' => 'field-errors'],
            ],
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
