<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\View\Form\ArrayContext;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Test\Fixture\Authoring\TitleForm;
use FormCompose\Test\Fixture\FailOnceRenderer;
use FormCompose\Test\Fixture\Repeat\CommentForm;
use FormCompose\Test\FormTestCase;
use LogicException;
use RuntimeException;
use TypeError;

/**
 * Checks rollback of feedback and summary identities through public rendering operations.
 */
class ErrorSummaryFailureTest extends FormTestCase
{
    public function testFailedControlsDoNotLeaveFeedbackBehind(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext([
                'errors' => [
                    'existing' => ['invalid' => 'Existing error'],
                    'rejected' => ['invalid' => 'Rejected error'],
                ],
            ]))
            ->bind();
        $form->open();
        $form->control('existing')->id('hint')->render();
        try {
            $form->control('rejected')
                ->help('Hint')
                ->partAttributes('help', ['id' => 'hint'])
                ->render();
            static::fail('Expected a late feedback collision.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('already been rendered', $logicException->getMessage());
        }

        $summary = $form->errorSummary()->render();
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
        $form = $this->helper()->form($context)->bind();
        $form->open();
        $form->control('existing')->render();
        try {
            $form->embedMany(path: 'comments', blueprint: CommentForm::class)
                ->partTemplate('control', 'repeat/failure')
                ->render();
            static::fail('Expected repeated wrapper failure.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Repeated wrapper failed.', $runtimeException->getMessage());
        }

        $summary = $form->errorSummary()->render();
        static::assertStringContainsString('Existing error', $summary);
        static::assertStringNotContainsString('Row error', $summary);
        static::assertStringNotContainsString('Collection error', $summary);
        $form->close();
    }

    public function testSummaryTemplateFailureCanBeRetried(): void
    {
        $helper = $this->helper();
        $renderer = new FailOnceRenderer(new PhpRenderer($helper->getView()), 'error-summary', 'control');
        $form = $helper->form(new ArrayContext([
            'errors' => ['title' => ['invalid' => 'Fix title']],
        ]))
            ->renderer($renderer)
            ->bind();
        $form->open();
        $form->control('title')->render();
        try {
            $form->errorSummary()->render();
            static::fail('Expected summary template failure.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Template failed', $runtimeException->getMessage());
        }

        static::assertStringContainsString('Fix title', $form->errorSummary()->render());
        $form->close();
    }

    public function testLateSummaryCollisionRestoresItsHeadingId(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]))
            ->bind();
        $form->open();
        $form->control('title')->id('validation')->render();
        try {
            $form->errorSummary()
                ->attributes(['id' => 'validation'])
                ->render();
            static::fail('Expected summary wrapper collision.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('already been rendered', $logicException->getMessage());
        }

        static::assertStringContainsString('Fix title', $form->errorSummary()->render());
        static::assertStringContainsString(
            'id="validation-title"',
            $form->control('other')->id('validation-title')->render(),
        );
        $form->close();
    }

    public function testInvalidCompleteFormOptionsFailBeforeOpening(): void
    {
        $builder = $this->helper()
            ->form(new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]))
            ->blueprint(TitleForm::class);
        $this->expectException(TypeError::class);
        try {
            $this->invokeBuilder($builder, 'errorSummary', 'enabled');
        } finally {
            static::assertStringContainsString('<section', $builder->errorSummary()->render());
        }
    }

    public function testInvalidSummaryTitleLeavesTheSummaryRetryable(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]))
            ->bind();
        $form->open();
        $form->control('title')->render();
        $this->expectException(TypeError::class);
        try {
            $this->invokeBuilder($form->errorSummary(), 'title', false);
        } finally {
            static::assertStringContainsString('Fix title', $form->errorSummary()->render());
            $form->close();
        }
    }

    public function testLocalFalseOverridesBindingOptInWithoutLeakingFormAttributes(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]))
            ->errorSummary(true)
            ->blueprint(TitleForm::class)
            ->errorSummary(false)
            ->bind();
        $html = $form->render();
        static::assertStringNotContainsString('<section', $html);
        static::assertStringNotContainsString('errorSummary', $html);
    }

    public function testRemovedHeadingIdentityCreatesNoDanglingAriaReference(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['title' => ['invalid' => 'Fix title']]]))
            ->bind();
        $form->open();
        $form->control('title')->render();

        $summary = $form->errorSummary()
            ->attributes(['id' => null])
            ->partAttributes('title', ['id' => null])
            ->render();
        static::assertStringNotContainsString('aria-labelledby', $summary);
        static::assertStringNotContainsString('id=', $summary);
        $form->close();
    }

    public function testSummaryRequiresAnOpenForm(): void
    {
        $this->expectException(LogicException::class);
        $this->helper()->form()->bind()->errorSummary()->render();
    }
}
