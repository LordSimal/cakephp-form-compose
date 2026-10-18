<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use Cake\View\Form\ArrayContext;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldSpec;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\Widget\ConfiguredType;
use FormCompose\Test\Fixture\Widget\DateRangeType;
use FormCompose\Test\FormTestCase;
use LogicException;

/**
 * Checks that composite feedback describes actual controls and preserves accessible parts.
 */
class CompositeFeedbackTest extends FormTestCase
{
    public function testGroupAndChildMessagesRemainDistinctAndReachTheCorrectInputs(): void
    {
        $context = new ArrayContext([
            'errors' => [
                'period' => [
                    'range' => 'End must follow start',
                    'start' => ['invalid' => 'Fix start'],
                ],
            ],
        ]);
        $form = $this->helper()
            ->form($context)
            ->types(['range' => new DateRangeType()])
            ->bind();
        $field = $form->field('period')->type('range')->help('Pick dates')->resolve();
        static::assertSame(['End must follow start'], $field->state->messages);
        static::assertSame(['Fix start'], $field->inputs['start']->state->messages);
        $html = $form->open() . $field->control() . $form->errorSummary()->render() . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame(
            $field->state->id
                . '-help '
                . $field->state->id
                . '-errors '
                . $field->inputs['start']->state->id
                . '-errors',
            $xpath->evaluate('string(//input[@name="period[start]"]/@aria-describedby)'),
        );
        static::assertSame('true', $xpath->evaluate('string(//input[@name="period[end]"]/@aria-invalid)'));
        static::assertSame(2, substr_count($html, 'Fix start'));
        static::assertSame(2, substr_count($html, 'End must follow start'));
        static::assertStringContainsString('href="#' . $field->inputs['start']->state->id . '"', $html);
        static::assertSame(0.0, $xpath->evaluate('count(//fieldset/label[@for])'));
    }

    public function testLocalDisabledAndReadonlyStatePropagatesEvenWhenChildrenOptOut(): void
    {
        $context = new ArrayContext(['required' => ['settings.start' => true]]);
        $form = $this->helper()
            ->form($context)
            ->types(['settings' => new ConfiguredType([
                new FieldSpec(
                    'start',
                    FieldConfig::builder()
                        ->disabled(false)
                        ->readonly(false)
                        ->attributes(['disabled' => false]),
                ),
                new FieldSpec('check', FieldConfig::builder()->type('checkbox')),
            ])])
            ->bind();
        $field = $form->field('settings')->type('settings')->resolve();
        $form->open();
        $html = $field->input()->attributes(['disabled' => true, 'readonly' => true])->render();
        $xpath = $this->xpath($html);
        static::assertSame(2.0, $xpath->evaluate('count(//input[@disabled][@readonly])'));
        static::assertSame(0.0, $xpath->evaluate('count(//input[@required])'));
        static::assertSame(0.0, $xpath->evaluate('count(//input[@type="hidden"][not(@disabled)])'));
        static::assertNull($field->focusTarget);
        $form->close();
    }

    public function testSummarySkipsDisabledHiddenEmptyAndOmittedChildren(): void
    {
        $context = new ArrayContext(['errors' => ['period' => ['invalid' => 'Fix range']]]);
        $form = $this->helper()
            ->form($context)
            ->types(['range' => new ConfiguredType([
                new FieldSpec('hidden', FieldConfig::builder()->type('hidden')),
                new FieldSpec('disabled', FieldConfig::builder()->disabled(true)),
                new FieldSpec(
                    'empty',
                    FieldConfig::builder()->type('radio')->choices([]),
                ),
                new FieldSpec('start', new FieldConfig()),
                new FieldSpec('end', new FieldConfig()),
            ])])
            ->bind();
        $field = $form->field('period')->type('range')->partTemplate('composite', 'widgets/start-only')->resolve();
        $html = $form->open() . $field->control() . $form->errorSummary()->render() . $form->close();
        static::assertSame($field->inputs['start']->state->id, $field->focusTarget);
        static::assertNull($field->inputs['end']->focusTarget);
        static::assertStringContainsString('href="#' . $field->inputs['start']->state->id . '"', $html);
    }

    public function testRenderedIneligibleChildrenProduceAnUnlinkedGroupSummary(): void
    {
        $context = new ArrayContext(['errors' => ['period' => ['invalid' => 'Fix range']]]);
        $form = $this->helper()
            ->form($context)
            ->types(['range' => new ConfiguredType([
                new FieldSpec('hidden', FieldConfig::builder()->type('hidden')),
                new FieldSpec('disabled', FieldConfig::builder()->disabled(true)),
                new FieldSpec(
                    'empty',
                    FieldConfig::builder()->type('radio')->choices([]),
                ),
            ])])
            ->bind();
        $field = $form->field('period')->type('range')->resolve();
        $html = $form->open() . $field->control() . $form->errorSummary()->render() . $form->close();
        static::assertNull($field->focusTarget);
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//a[@href])'));
    }

    public function testIndependentlyRenderedChildrenStillRequireTheirParentFeedback(): void
    {
        $form = $this->helper()
            ->form()
            ->types(['range' => new DateRangeType()])
            ->bind();
        $field = $form->field('period')->type('range')->help('Pick dates')->resolve();
        $form->open();
        $field->inputs['start']->input()->render();
        try {
            $form->close();
            static::fail('Expected parent help reference enforcement.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString($field->state->id . '-help', $logicException->getMessage());
        }

        static::assertStringContainsString('Pick dates', $field->help()->render());
        $form->close();
    }

    public function testChildCanExplicitlyReplaceInheritedFeedbackAndParentRequiredDoesNotForceChildren(): void
    {
        $form = $this->helper()
            ->form()
            ->types(['range' => new ConfiguredType([
                new FieldSpec(
                    'start',
                    FieldConfig::builder()->attributes(['aria-describedby' => null]),
                ),
                new FieldSpec('end', new FieldConfig()),
            ])])
            ->bind();
        $field = $form->field('period')
            ->type('range')
            ->required(true)
            ->help('Dates')
            ->resolve();
        static::assertFalse($field->inputs['start']->attributes()->has('aria-describedby'));
        static::assertFalse($field->inputs['start']->attributes()->has('required'));
        static::assertTrue($field->inputs['end']->attributes()->has('aria-describedby'));
    }

    public function testCompositeBodiesRetainThemeVariantsParentLookupAndIntegrationAttributes(): void
    {
        $form = $this->helper()
            ->form()
            ->types(['range' => new DateRangeType()])
            ->theme(new Theme(
                layers: ['widgets/theme'],
            ))
            ->bind();
        $field = $form->field('period')
            ->type('range')
            ->variant('compact')
            ->partTemplate('composite', 'widgets/parent')
            ->attributes(['data-controller' => 'range'])
            ->resolve();
        $html = $form->open() . $field->control() . $form->close();
        static::assertSame(
            1.0,
            $this->xpath($html)->evaluate(
                'count(//article[@data-parent-layout]/section[@data-compact]/div[@data-controller="range"])',
            ),
        );
        static::assertSame(2.0, $this->xpath($html)->evaluate('count(//input[@type="date"])'));
    }

    public function testHiddenChildrenKeepNativeFeedbackSemanticsWhenRenderedIndependently(): void
    {
        $form = $this->helper()
            ->form()
            ->types(['settings' => new ConfiguredType([
                new FieldSpec(
                    'id',
                    FieldConfig::builder()->type('hidden')->value(42),
                ),
            ])])
            ->bind();
        $field = $form->field('settings')->type('settings')->help('Visible group help')->resolve();
        $html = $form->open() . $field->inputs['id']->input()->render() . $form->close();
        static::assertStringNotContainsString('aria-describedby', $html);
        static::assertStringContainsString('name="settings[id]"', $html);
    }

    public function testCompositeElementReceivesWrapperAttributesWhileChildrenKeepConstraints(): void
    {
        $form = $this->helper()
            ->form()
            ->types(['range' => new DateRangeType()])
            ->bind();
        $field = $form->field('period')
            ->type('range')
            ->disabled(true)
            ->readonly(true)
            ->required(true)
            ->attributes(['class' => 'date-range', 'data-controller' => 'calendar', 'min' => '2026-01-01'])
            ->resolve();
        $html = $form->open() . $field->control() . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame($field->state->id, $xpath->evaluate('string(//fieldset/div/@id)'));
        static::assertSame('date-range', $xpath->evaluate('string(//fieldset/div/@class)'));
        static::assertSame('calendar', $xpath->evaluate('string(//fieldset/div/@data-controller)'));
        static::assertSame(
            0.0,
            $xpath->evaluate('count(//div/@name | //div/@required | //div/@disabled | //div/@readonly | //div/@min)'),
        );
        static::assertSame(2.0, $xpath->evaluate('count(//input[@disabled][@readonly])'));
    }
}
