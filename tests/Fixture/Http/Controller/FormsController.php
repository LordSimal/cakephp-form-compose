<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Http\Controller;

use Cake\Controller\Controller;
use Cake\Database\Type\EnumType;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Routing\Attribute\Get;
use Cake\Routing\Attribute\Post;
use Cake\Routing\Attribute\Scope;
use Cake\View\Form\ArrayContext;
use Cake\View\View;
use DateTimeImmutable;
use FormCompose\Configuration\ButtonConfig;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Form\Field\FieldSpec;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\Authoring\EmailForm;
use FormCompose\Test\Fixture\Authoring\HttpCommentForm;
use FormCompose\Test\Fixture\Authoring\LayoutForm;
use FormCompose\Test\Fixture\Enum\Priority;
use FormCompose\Test\Fixture\Enum\PublicationStatus;
use FormCompose\View\Helper\FormHelper;

/**
 * Exercises application-owned markup and real Cake form protection over HTTP.
 */
#[Scope('/forms')]
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
    #[Get('')]
    public function index(): Response
    {
        $helper = new FormHelper(new View($this->getRequest()), [
            'themeDebug' => $this->getRequest()->getQuery('themeDebug') === '1',
        ]);
        $form = $helper->bind(
            $this->createContext(),
            config: FormConfig::builder()
                ->theme($this->createTheme())
                ->url('/forms/save')
                ->multipart(),
        );
        $opening = $form->open();
        $html = $form->control('id')->type('hidden')->value(42)->render();
        $html .= $form->control('empty_select')
            ->type('select')
            ->choices([])
            ->placeholder(false)
            ->render();
        $html .= $form->control('author_id')
            ->choices([1 => 'Ada', 'Authors' => [2 => 'Grace', '' => 'Unassigned']])
            ->value(null)
            ->render();
        $html .= $form->control('topics')
            ->type('multicheckbox')
            ->choices(['a' => 'A', 'b' => 'B'])
            ->required(true)
            ->value(['a'])
            ->render();
        $html .= $form->control('active')->type('checkbox')->value(false)->render();
        $html .= $form->control('visibility')
            ->type('radio')
            ->choices(['public' => 'Public', 'private' => 'Private'])
            ->render();
        $html .= $form->control('tags._ids')
            ->choices([1 => 'PHP', 2 => 'CakePHP'])
            ->multiple(true)
            ->render();
        $html .= $form->control('email')
            ->type('email')
            ->value('reader@example.test')
            ->help('Work address')
            ->partAttributes('help', ['id' => 'email-hint'])
            ->partAttributes('errors', ['id' => 'email-feedback'])
            ->render();
        $html .= $form->control('author.name')->value('Ada')->render();
        $html .= $form->control('bio')->type('textarea')->value('Hello <world>')->render();
        $html .= $form->control('attachment')->type('file')->render();
        $html .= $form->control('disabled')->disabled(true)->render();
        $html .= $form->embedMany(path: 'comments', blueprint: HttpCommentForm::class)
            ->partTemplate('control', 'repeat/comments')
            ->render();
        $html = $opening . $form->errorSummary()->render() . $html . $form->submit('Save')->render() . $form->close();

        return $this->getResponse()
            ->withType('html')
            ->withStringBody(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Form fixture</title></head><body>'
                    . $html
                    . '</body></html>',
            );
    }

    /**
     * Render a complete form with an application-owned body/summary/action layout.
     */
    #[Get('/complete')]
    public function complete(): Response
    {
        $helper = new FormHelper(new View($this->getRequest()), ['themeDebug' => true]);
        $html = $helper->form(new ArrayContext([
            'defaults' => ['id' => 42, 'title' => 'A complete form', 'email' => 'editor@example.test'],
            'errors' => ['title' => ['invalid' => 'Review the title']],
        ]))
            ->blueprint(LayoutForm::class)
            ->blueprint(EmailForm::class)
            ->url('/forms/save')
            ->multipart()
            ->partTemplate('layout', 'forms/editor')
            ->templateData(['caption' => 'Complete form'])
            ->errorSummary(true)
            ->submit('Save complete form')
            ->submitConfig(
                ButtonConfig::builder()->name('intent')->value('save'),
            )
            ->render();

        return $this->getResponse()
            ->withType('html')
            ->withStringBody('<!DOCTYPE html><html><body>' . $html . '</body></html>');
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
    #[Get('/temporal')]
    public function temporal(): Response
    {
        $helper = new FormHelper(new View($this->getRequest()));
        $form = $helper->form(new ArrayContext([
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
        ]))
            ->url('/forms/save')
            ->bind();
        $html =
            $form->open()
            . $form->controls([
                'day',
                'clock',
                'instant',
                new FieldSpec(
                    'fractional_clock',
                    FieldConfig::builder()->attributes(['step' => '1.5', 'min' => '00:00:00']),
                ),
                new FieldSpec(
                    'fractional_instant',
                    FieldConfig::builder()->attributes(['step' => '2.5', 'min' => '2026-10-15T00:00:00']),
                ),
            ])->render();
        $html .= $form->control('week')
            ->type('week')
            ->value(new Date('2027-01-01'))
            ->render();
        $html .= $form->submit('Save temporal')->render() . $form->close();

        return $this->getResponse()
            ->withType('html')
            ->withStringBody('<!DOCTYPE html><html><body>' . $html . '</body></html>');
    }

    /**
     * Compose inputs with both rendered and handwritten accessible feedback.
     */
    #[Get('/composition')]
    public function composition(): Response
    {
        $helper = new FormHelper(new View($this->getRequest()));
        $form = $helper->form(new ArrayContext(['errors' => ['title' => ['invalid' => 'Review the title']]]))
            ->url('/forms/save')
            ->bind();
        $field = $form->field('title')->help('Use a descriptive title')->value('Example')->resolve();
        $html = $form->open() . $field->label()->render() . $field->input()->render();
        $html .= '<p' . (string)$field->attributes('help') . '>Use a descriptive title</p>';
        $form->registerFeedback($field, 'help');
        $html .= $field->errors()->render() . $form->submit('Save composition')->render() . $form->close();

        return $this->getResponse()
            ->withType('html')
            ->withStringBody('<!DOCTYPE html><html><body>' . $html . '</body></html>');
    }

    /**
     * Discover schema enum choices for native select and radio submissions.
     */
    #[Get('/enums')]
    public function enums(): Response
    {
        $helper = new FormHelper(new View($this->getRequest()));
        $form = $helper->form(new ArrayContext([
            'schema' => [
                'status' => ['type' => EnumType::from(PublicationStatus::class)],
                'priority' => ['type' => EnumType::from(Priority::class)],
            ],
            'data' => ['status' => PublicationStatus::Published, 'priority' => Priority::Normal],
        ]))
            ->url('/forms/save')
            ->bind();
        $html =
            $form->open()
            . $form->controls([
                'status',
                new FieldSpec('priority', FieldConfig::builder()->type('radio')),
            ])->render();
        $html .= $form->submit('Save enums')->render() . $form->close();

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
    #[Post('/save')]
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
