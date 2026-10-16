<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\View\Form\ArrayContext;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Test\Fixture\FailOnceRenderer;
use FormCompose\Test\Fixture\Repeat\CommentForm;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

/**
 * Checks rollback of feedback and summary identities through public rendering operations.
 */
class ErrorSummaryFailureTest extends FormTestCase
{
    public function testFailedControlsDoNotLeaveFeedbackBehind(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'errors' => [
                'existing' => ['invalid' => 'Existing error'],
                'rejected' => ['invalid' => 'Rejected error'],
            ],
        ]));
        $form->open();
        $form->control('existing', ['id' => 'hint']);
        try {
            $form->control('rejected', ['help' => 'Hint', 'partAttributes' => ['help' => ['id' => 'hint']]]);
            static::fail('Expected a late feedback collision.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('already been rendered', $logicException->getMessage());
        }

        $summary = $form->errorSummary();
        static::assertStringContainsString('Existing error', $summary);
        static::assertStringNotContainsString('Rejected error', $summary);
        $form->close();
    }

    public function testFailedRepeatedWrappersRestoreEarlierFeedback(): void
    {
        $context = new ArrayContext([
            'defaults' => ['comments' => [0 => ['id' => 1, 'body' => 'Value']]],
            'errors' => [
                'existing' => ['invalid' => 'Existing error'],
                'comments' => ['count' => 'Collection error', 0 => ['body' => ['invalid' => 'Row error']]],
            ],
        ]);
        $form = $this->helper()->bind($context);
        $form->open();
        $form->control('existing');
        try {
            $form->embedMany('comments', CommentForm::class, options: ['template' => 'repeat/failure']);
            static::fail('Expected repeated wrapper failure.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Repeated wrapper failed.', $runtimeException->getMessage());
        }

        $summary = $form->errorSummary();
        static::assertStringContainsString('Existing error', $summary);
        static::assertStringNotContainsString('Row error', $summary);
        static::assertStringNotContainsString('Collection error', $summary);
        $form->close();
    }

    public function testSummaryTemplateFailureCanBeRetried(): void
    {
        $helper = $this->helper();
        $renderer = new FailOnceRenderer(new PhpRenderer($helper->getView()), 'error-summary', 'control');
        $form = $helper->bind(new ArrayContext([
            'errors' => ['title' => ['invalid' => 'Fix title']],
        ]), renderer: $renderer);
        $form->open();
        $form->control('title');
        try {
            $form->errorSummary();
            static::fail('Expected summary template failure.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Template failed', $runtimeException->getMessage());
        }

        static::assertStringContainsString('Fix title', $form->errorSummary());
        $form->close();
    }

    public function testLateSummaryCollisionRestoresItsHeadingId(): void
    {
        $form = $this->helper()->bind(new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]));
        $form->open();
        $form->control('title', ['id' => 'validation']);
        try {
            $form->errorSummary(['attributes' => ['id' => 'validation']]);
            static::fail('Expected summary wrapper collision.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('already been rendered', $logicException->getMessage());
        }

        static::assertStringContainsString('Fix title', $form->errorSummary());
        static::assertStringContainsString('id="validation-title"', $form->control('other', [
            'id' => 'validation-title',
        ]));
        $form->close();
    }

    public function testInvalidCompleteFormOptionsFailBeforeOpening(): void
    {
        $form = $this->bindCompiled(
            new FormDefinition([])->add(new FieldDefinition('title')),
            helper: $this->helper(),
            data: new ArrayContext([
                'errors' => ['title' => ['invalid' => 'Fix title']],
            ]),
        );
        try {
            $form->render(options: ['errorSummary' => 'enabled']);
            static::fail('Expected invalid summary configuration.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('boolean or an options array', $invalidArgumentException->getMessage());
        }

        static::assertStringContainsString('<section', $form->render(options: ['errorSummary' => true]));
    }

    public function testInvalidSummaryTitleLeavesTheSummaryRetryable(): void
    {
        $form = $this->helper()->bind(new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]));
        $form->open();
        $form->control('title');
        try {
            $form->errorSummary(['title' => false]);
            static::fail('Expected invalid title configuration.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('text or trusted Html', $invalidArgumentException->getMessage());
        }

        static::assertStringContainsString('Fix title', $form->errorSummary());
        $form->close();
    }

    public function testLocalFalseOverridesBindingOptInWithoutLeakingFormAttributes(): void
    {
        $form = $this->bindCompiled(
            new FormDefinition([])->add(new FieldDefinition('title')),
            helper: $this->helper(),
            data: new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]),
            options: ['errorSummary' => true],
        );
        $html = $form->render(options: ['errorSummary' => false]);
        static::assertStringNotContainsString('<section', $html);
        static::assertStringNotContainsString('errorSummary', $html);
    }

    public function testRemovedHeadingIdentityCreatesNoDanglingAriaReference(): void
    {
        $form = $this->helper()->bind(new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]));
        $form->open();
        $form->control('title');

        $summary = $form->errorSummary([
            'attributes' => ['id' => null],
            'partAttributes' => ['title' => ['id' => null]],
        ]);
        static::assertStringNotContainsString('aria-labelledby', $summary);
        static::assertStringNotContainsString('id=', $summary);
        $form->close();
    }

    public function testSummaryRequiresAnOpenForm(): void
    {
        $this->expectException(LogicException::class);
        $this->helper()->bind()->errorSummary();
    }
}
