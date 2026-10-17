<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\Form\FormProtector;
use Cake\View\Form\ArrayContext;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\Authoring\CommentsForm;
use FormCompose\Test\Fixture\Authoring\TitleForm;
use FormCompose\Test\Fixture\Authoring\TitleIdForm;
use FormCompose\Test\Fixture\RecordingRenderer;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use RuntimeException;

/** Exercises complete-form layouts through public rendering and protection APIs. */
class FormLayoutTest extends FormTestCase
{
    public function testCustomLayoutReceivesTheFinishedSummaryBodyAndActionOptions(): void
    {
        $html = $this->helper()->render(
            TitleForm::class,
            new ArrayContext([
                'defaults' => ['title' => 'Keep <this>'],
                'errors' => ['title' => ['invalid' => 'Fix <title>']],
            ]),
            [
                'template' => 'forms/editor',
                'templateData' => ['caption' => '<Project editor>'],
                'errorSummary' => true,
            ],
            submit: 'Save <project>',
            submitOptions: ['name' => 'intent', 'value' => 'save'],
        );
        $xpath = $this->xpath($html);
        static::assertSame(1.0, $xpath->evaluate('count(//form)'));
        static::assertSame(1.0, $xpath->evaluate('count(//article[@data-form-layout])'));
        static::assertSame('<Project editor>', $xpath->evaluate('string(//h2)'));
        static::assertSame('Keep <this>', $xpath->evaluate('string(//input[@name="title"]/@value)'));
        static::assertSame('Save <project>', $xpath->evaluate('string(//button[@value="save"])'));
        static::assertStringContainsString('Fix &lt;title&gt;', $html);
        static::assertLessThan(strpos($html, 'data-form-body'), strpos($html, 'Please correct'));
        static::assertSame(1.0, $xpath->evaluate('count(//div[@data-form-body]//input[@name="title"])'));
        static::assertStringNotContainsString('template=', $html);
        static::assertStringNotContainsString('templateData=', $html);
    }

    public function testNestedFeedbackIsAlreadyAvailableWhenTheCompleteLayoutRuns(): void
    {
        $html = $this->helper()->render(
            CommentsForm::class,
            new ArrayContext([
                'defaults' => ['comments' => [2 => ['body' => 'A comment']]],
                'errors' => ['comments' => ['count' => 'Add a comment', 2 => ['body' => ['invalid' => 'Fix row two']]]],
            ]),
            ['template' => 'forms/editor', 'errorSummary' => true],
        );
        static::assertStringContainsString('href="#form-compose-1-comments.2.body"', $html);
        static::assertSame(1.0, $this->xpath($html)->evaluate(
            'count(//article/section//li[contains(., "Fix row two")])',
        ));
        static::assertLessThan(strpos($html, 'data-form-body'), strpos($html, 'Add a comment'));
    }

    public function testLayoutButtonsAndLockedFieldsRemainProtectedUntilAutomaticClosing(): void
    {
        $csrf = bin2hex(random_bytes(16));
        $helper = $this->helper(attributes: ['csrfToken' => $csrf, 'formTokenData' => []]);
        $html = $helper->render(
            TitleIdForm::class,
            new ArrayContext([
                'defaults' => ['id' => 42, 'title' => 'Example'],
            ]),
            ['method' => 'patch', 'template' => 'forms/editor'],
            submitOptions: [
                'name' => 'intent',
                'value' => 'save',
            ],
        );
        $xpath = $this->xpath($html);
        static::assertSame('PATCH', $xpath->evaluate('string(//input[@name="_method"]/@value)'));
        static::assertSame($csrf, $xpath->evaluate('string(//input[@name="_csrfToken"]/@value)'));
        $payload = [
            'id' => '42',
            'title' => 'Changed',
            '_Token' => [
                'fields' => $xpath->evaluate('string(//input[@name="_Token[fields]"]/@value)'),
                'unlocked' => $xpath->evaluate('string(//input[@name="_Token[unlocked]"]/@value)'),
                'debug' => $xpath->evaluate('string(//input[@name="_Token[debug]"]/@value)'),
            ],
        ];
        $sessionId = $helper->getView()->getRequest()->getSession()->id();
        foreach ([[], ['intent' => 'save'], ['intent' => 'check']] as $button) {
            static::assertTrue(new FormProtector()->validate($payload + $button, '/articles/save', $sessionId));
        }

        static::assertFalse(new FormProtector()->validate(
            array_replace($payload, ['id' => '99']),
            '/articles/save',
            $sessionId,
        ));
        static::assertLessThan(strpos($html, 'name="_Token[fields]"'), strpos($html, 'value="save"'));
    }

    public function testFormLayoutUsesScopedThemeLookupAndParentDelegation(): void
    {
        $helper = $this->helper();
        $helper->setConfig('themeDebug', true);

        $renderer = new RecordingRenderer(new PhpRenderer(
            $helper->getView(),
            new Theme(['themes/form-layout']),
            themeDebug: true,
        ));
        $html = $helper->bind(null, ['method' => 'patch'], renderer: $renderer, blueprint: TitleForm::class)->render();
        static::assertStringContainsString('<main data-method-layout>', $html);
        static::assertStringContainsString('element: themes/form-layout/form/patch/control', $html);
        static::assertStringContainsString('element: FormCompose.form/base/form/control', $html);
        static::assertStringContainsString('FormCompose PARENT', $html);
        $parts = array_map(
            static fn(array $call): string => $call['node']->scope . '.' . $call['part'],
            $renderer->calls,
        );
        static::assertSame([
            'form.open',
            'form.hidden',
            'blueprint.control',
            'form.control',
            'button.control',
            'form.close',
        ], array_values(
            array_filter($parts, static fn(string $part): bool => !str_starts_with($part, 'field.')),
        ));
    }

    public function testLocalLayoutSelectionOverridesTheBindingShorthand(): void
    {
        $form = $this->helper()->bind(null, ['template' => 'forms/failing-editor'], blueprint: TitleForm::class);
        $html = $form->render(options: ['templates' => ['control' => 'forms/editor']], submit: false);
        static::assertStringContainsString('data-form-layout', $html);
        static::assertSame(1.0, $this->xpath($html)->evaluate('count(//button)'));
        static::assertStringNotContainsString('Partial action', $html);
    }

    public function testInvalidLayoutSelectionFailsBeforeOpeningTheSession(): void
    {
        $form = $this->helper()->bind(blueprint: TitleForm::class);
        try {
            $form->render(options: ['template' => '']);
            static::fail('Expected invalid layout selection.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('nonempty element name', $invalidArgumentException->getMessage());
        }

        static::assertStringContainsString('<form', $form->render());
    }

    public function testLayoutExceptionsDoNotEchoPartialFormsAndFreshBindingsCanRender(): void
    {
        $helper = $this->helper();
        $this->expectOutputString('');
        try {
            $helper->render(TitleForm::class, options: ['template' => 'forms/failing-editor']);
            static::fail('Expected layout failure.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Form layout failed.', $runtimeException->getMessage());
        }

        $html = $helper->render(TitleForm::class, options: ['template' => 'forms/editor']);
        static::assertStringContainsString('data-form-layout', $html);
        static::assertStringNotContainsString('Partial action', $html);
    }

    public function testTheDefaultLayoutPreservesSuppressionAndTrustedButtonMarkup(): void
    {
        $helper = $this->helper();
        static::assertSame(
            0.0,
            $this->xpath($helper->render(TitleForm::class, submit: false))->evaluate('count(//button)'),
        );
        static::assertSame(
            1.0,
            $this->xpath($helper->render(
                TitleForm::class,
                submit: new Html('<strong>Save</strong>'),
            ))->evaluate('count(//button/strong[text()="Save"])'),
        );
    }
}
