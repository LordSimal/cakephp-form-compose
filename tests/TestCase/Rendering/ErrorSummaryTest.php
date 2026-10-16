<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\View\Form\ArrayContext;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;
use FormCompose\Rendering\Html;
use FormCompose\RepeatedDefinition;
use FormCompose\Test\Fixture\Authoring\EmptyForm;
use FormCompose\Test\Fixture\Authoring\TitleForm;
use FormCompose\Test\Fixture\Summary\CustomHelper;
use FormCompose\Test\FormTestCase;

/**
 * Verifies the rendered-field scope, template API, and complete-form placement.
 */
class ErrorSummaryTest extends FormTestCase
{
    public function testOnlyRenderedEligibleFieldsAppearInRenderOrder(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'errors' => [
                'first' => ['invalid' => 'First error'],
                'second' => ['one' => 'Second one', 'two' => 'Second two'],
                'omitted' => ['invalid' => 'Omitted error'],
                'suppressed' => ['invalid' => 'Suppressed error'],
            ],
        ]));
        $form->field('omitted')->attributes();
        $form->open();
        static::assertSame('', $form->errorSummary());
        $body = $form->control('second', ['label' => 'Second label']);
        $body .= $form->control('first', ['id' => 'custom:first', 'label' => false]);
        $body .= $form->control('suppressed', ['errors' => false]);
        $summary = $form->errorSummary();
        static::assertStringContainsString('Second label: Second one', $summary);
        static::assertStringContainsString('Second label: Second two', $summary);
        static::assertStringContainsString('First: First error', $summary);
        static::assertStringContainsString('href="#custom%3Afirst"', $summary);
        static::assertStringNotContainsString('Omitted error', $summary);
        static::assertStringNotContainsString('Suppressed error', $summary);
        static::assertLessThan(strpos($summary, 'First error'), strpos($summary, 'Second one'));
        static::assertStringContainsString('id="custom:first"', $body);
        $form->close();
    }

    public function testLabelsTitlesAndMessagesEscapeUnlessExplicitlyTrusted(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'errors' => ['title' => ['invalid' => '<script>bad</script>'], 'trusted' => ['invalid' => 'Invalid']],
        ]));
        $form->open();
        $form->control('title', ['label' => '<Title>']);
        $form->control('trusted', ['label' => new Html('<em>Trusted</em>')]);

        $summary = $form->errorSummary(['title' => '<Errors>']);
        static::assertStringContainsString('&lt;Errors&gt;', $summary);
        static::assertStringContainsString('&lt;Title&gt;: &lt;script&gt;bad&lt;/script&gt;', $summary);
        static::assertStringContainsString('<em>Trusted</em>: Invalid', $summary);
        static::assertStringNotContainsString('&amp;lt;', $summary);
        $form->close();
    }

    public function testHiddenAndDisabledFieldsRemainVisibleAsUnlinkedMessages(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'errors' => ['id' => ['invalid' => 'Invalid identifier'], 'locked' => ['invalid' => 'Locked value']],
        ]));
        $form->open();
        $form->control('id', ['type' => 'hidden']);
        $form->control('locked', ['disabled' => true]);

        $summary = $form->errorSummary();
        static::assertStringContainsString('Invalid identifier', $summary);
        static::assertStringContainsString('Locked value', $summary);
        static::assertStringNotContainsString('<a ', $summary);
        $form->close();
    }

    public function testRepeatedRowsAndCollectionErrorsAreIncludedWithoutDuplicatingChildErrors(): void
    {
        $row = new FormDefinition([])->add(new FieldDefinition('body'));
        $definition = new FormDefinition([])->add(new RepeatedDefinition('comments', $row));
        $context = new ArrayContext([
            'defaults' => ['comments' => [2 => ['body' => 'Value']]],
            'errors' => [
                'comments' => ['count' => 'Add another', 2 => ['body' => ['invalid' => 'Fix row two']]],
            ],
        ]);
        $form = $this->bindCompiled($definition, helper: $this->helper(), data: $context);
        $form->open();
        $form->definition();

        $summary = $form->errorSummary();
        static::assertSame(1, substr_count($summary, 'Fix row two'));
        static::assertSame(1, substr_count($summary, 'Add another'));
        static::assertStringContainsString('href="#form-compose-1-comments-2-body"', $summary);
        static::assertStringContainsString('Comments: Add another', $summary);
        $form->close();
    }

    public function testEmptyCollectionsCanContributeTheirOwnError(): void
    {
        $form = $this->helper()->bind(new ArrayContext(['errors' => ['comments' => ['count' => 'At least one']]]));
        $form->open();
        $form->embedMany('comments', EmptyForm::class, options: [
            'label' => new Html('<strong>Comments</strong>'),
        ]);
        static::assertStringContainsString('<strong>Comments</strong>: At least one', $form->errorSummary());
        $form->close();
    }

    public function testCompleteFormSummaryIsOptInAndAppearsBeforeControls(): void
    {
        $context = new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]);
        $helper = $this->helper();
        $ordinary = $helper->render(TitleForm::class, $context);
        static::assertStringNotContainsString('<section', $ordinary);
        $html = $helper->render(TitleForm::class, $context, [
            'errorSummary' => true,
        ]);
        static::assertLessThan(strpos($html, 'name="title"'), strpos($html, '<section'));
        static::assertStringNotContainsString('errorSummary=', $html);
        static::assertStringContainsString('aria-labelledby="form-compose-2-error-summary-title"', $html);
    }

    public function testSummaryOptionsSelectCustomTemplatesAndAttributeDefaults(): void
    {
        $html = $this->helper()->render(
            TitleForm::class,
            new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]),
            [
                'errorSummary' => [
                    'template' => 'summary/custom',
                    'title' => new Html('<em>Errors</em>'),
                    'attributes' => ['id' => 'validation', 'class' => 'notice'],
                    'partAttributes' => ['title' => ['id' => 'validation-heading']],
                    'templateData' => ['intro' => 'Check <these>'],
                ],
            ],
        );
        static::assertStringContainsString(
            '<aside id="validation" class="notice" aria-labelledby="validation-heading">',
            $html,
        );
        static::assertStringContainsString('<em>Errors</em>', $html);
        static::assertStringContainsString('Check &lt;these&gt;', $html);
    }

    public function testAnInjectedSummaryRendererWorksThroughTheHelperShortcut(): void
    {
        $helper = new CustomHelper($this->helper()->getView());
        $html = $helper->render(
            TitleForm::class,
            new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]),
            ['errorSummary' => true],
        );
        static::assertStringContainsString('class="custom-summary"', $html);
    }

    public function testHandwrittenInputRegistrationAddsFeedbackWithoutRenderingAgain(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'errors' => ['title' => ['invalid' => 'Fix handwritten title']],
        ]));
        $field = $form->field('title');
        $form->open();
        $input = '<input' . (string)$field->attributes() . '>';
        $field->register();
        $summary = $form->errorSummary();
        static::assertStringContainsString('id="form-compose-1-title"', $input);
        static::assertSame(1, substr_count($summary, 'Fix handwritten title'));
        static::assertStringNotContainsString('<a ', $summary);
        $form->close();
    }

    public function testSuppressedCollectionErrorsNeverEnterTheSummary(): void
    {
        $form = $this->helper()->bind(new ArrayContext(['errors' => ['comments' => ['count' => 'Suppressed']]]));
        $form->open();
        $form->embedMany('comments', EmptyForm::class, options: [
            'errors' => false,
        ]);
        static::assertSame('', $form->errorSummary());
        $form->close();
    }
}
