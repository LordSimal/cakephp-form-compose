<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\View\Form\ArrayContext;
use FormCompose\Test\FormTestCase;
use LogicException;

/**
 * Enforces generated feedback associations while preserving independent field parts.
 */
class CompositionAccessibilityTest extends FormTestCase
{
    public function testMissingFeedbackPreventsClosingAndCanBeRenderedBeforeRetrying(): void
    {
        $form = $this->helper(attributes: ['formTokenData' => []])
            ->form(new ArrayContext([
                'errors' => ['title' => ['invalid' => 'Fix the title']],
            ]))
            ->bind();
        $field = $form->field('title')->help('Helpful text')->resolve();
        $html = $form->open() . $field->input()->render();
        try {
            $form->close();
            static::fail('Expected missing generated feedback to prevent closing.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('form-compose-1-title-help', $logicException->getMessage());
            static::assertStringContainsString('form-compose-1-title-errors', $logicException->getMessage());
        }

        $html .= $field->help()->render() . $field->errors()->render() . $form->close();
        static::assertStringContainsString('_Token[fields]', $html);
        static::assertStringContainsString('id="form-compose-1-title-help"', $html);
        static::assertStringContainsString('id="form-compose-1-title-errors"', $html);
    }

    public function testFeedbackCanPrecedeInputAndInspectionCreatesNoExpectations(): void
    {
        $form = $this->helper()->form()->bind();
        $form->field('unused')->help('Unrendered')->resolve()->attributes();
        $field = $form->field('title')->help('Helpful text')->resolve();
        $form->open();
        $html = $field->help()->render() . $field->input()->render() . $form->close();
        static::assertStringContainsString('aria-describedby="form-compose-1-title-help"', $html);
        static::assertStringNotContainsString('unused', $html);
    }

    public function testSummaryOnlyErrorsCanExplicitlyOmitInlineDescriptions(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['title' => ['bad' => 'Fix title']]]))
            ->bind();
        $form->open();

        $html = $form->field('title')->resolve()->input()->attributes(['aria-describedby' => null])->render();
        $html .= $form->errorSummary()->render() . $form->close();
        static::assertStringContainsString('aria-invalid="true"', $html);
        static::assertStringNotContainsString('aria-describedby', $html);
        static::assertStringContainsString('Title: Fix title', $html);
    }

    public function testExternalDescriptionsAndRemovedFeedbackIdsAreNotGeneratedExpectations(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();

        $html = $form->field('title')
            ->help('Handwritten elsewhere')
            ->partAttributes('help', ['id' => false])
            ->resolve()
            ->input()
            ->attributes(['aria-describedby' => 'external-hint'])
            ->render();
        $html .= $form->close();
        static::assertStringContainsString('aria-describedby="external-hint"', $html);
    }

    public function testFailedControlsRollBackFeedbackExpectations(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $form->control('occupied')->id('collision')->render();
        try {
            $form->control('title')
                ->help('Discarded hint')
                ->partAttributes('container', ['id' => 'collision'])
                ->render();
            static::fail('Expected a failed enclosing control.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('already been rendered', $logicException->getMessage());
        }

        static::assertStringContainsString('name="title"', $form->field('title')->resolve()->input()->render());
        static::assertStringContainsString('</form>', $form->close());
    }

    public function testEmptyNativeChoiceGroupsCreateNoInputDescriptionObligations(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();

        $html = $form->field('status')
            ->type('radio')
            ->choices([])
            ->help('No choices')
            ->resolve()
            ->input()
            ->render();
        $html .= $form->close();
        static::assertStringNotContainsString('aria-describedby', $html);
    }

    public function testGroupedControlsWithChoicesStillRequireTheirFeedback(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $form->field('status')
            ->type('radio')
            ->choices(['a' => 'A'])
            ->help('Choose one')
            ->resolve()
            ->input()
            ->render();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('form-compose-1-status-help');
        $form->close();
    }

    public function testFeedbackExpectationsRemainIsolatedBetweenForms(): void
    {
        $helper = $this->helper();
        $first = $helper->form()->bind();
        $second = $helper->form()->bind();
        $first->open();
        $field = $first->field('title')->help('First hint')->resolve();
        $field->input()->render();

        $second->open();
        $second->control('title')->help('Second hint')->render();
        $second->close();
        try {
            $first->close();
            static::fail('Expected the first form to retain its own feedback obligation.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('form-compose-1-title-help', $logicException->getMessage());
        }

        $field->help()->render();
        static::assertStringContainsString('</form>', $first->close());
    }
}
