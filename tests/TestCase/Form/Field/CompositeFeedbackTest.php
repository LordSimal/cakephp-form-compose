<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use Cake\View\Form\ArrayContext;
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
        $form = $this->helper()->bind($context, ['types' => ['range' => new DateRangeType()]]);
        $field = $form->field('period', ['type' => 'range', 'help' => 'Pick dates']);
        static::assertSame(['End must follow start'], $field->state->messages);
        static::assertSame(['Fix start'], $field->inputs['start']->state->messages);
        $html = $form->open() . $field->control() . $form->errorSummary() . $form->close();
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
        $form = $this->helper()->bind($context, ['types' => ['settings' => new ConfiguredType([
            'start' => ['disabled' => false, 'readonly' => false, 'attributes' => ['disabled' => false]],
            'check' => ['type' => 'checkbox'],
        ])]]);
        $field = $form->field('settings', ['type' => 'settings']);
        $form->open();
        $html = $field->input(['disabled' => true, 'readonly' => true]);
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
        $form = $this->helper()->bind($context, ['types' => ['range' => new ConfiguredType([
            'hidden' => ['type' => 'hidden'],
            'disabled' => ['disabled' => true],
            'empty' => ['type' => 'radio', 'options' => []],
            'start' => [],
            'end' => [],
        ])]]);
        $field = $form->field('period', ['type' => 'range', 'templates' => ['composite' => 'widgets/start-only']]);
        $html = $form->open() . $field->control() . $form->errorSummary() . $form->close();
        static::assertSame($field->inputs['start']->state->id, $field->focusTarget);
        static::assertNull($field->inputs['end']->focusTarget);
        static::assertStringContainsString('href="#' . $field->inputs['start']->state->id . '"', $html);
    }

    public function testRenderedIneligibleChildrenProduceAnUnlinkedGroupSummary(): void
    {
        $context = new ArrayContext(['errors' => ['period' => ['invalid' => 'Fix range']]]);
        $form = $this->helper()->bind($context, ['types' => ['range' => new ConfiguredType([
            'hidden' => ['type' => 'hidden'],
            'disabled' => ['disabled' => true],
            'empty' => ['type' => 'radio', 'options' => []],
        ])]]);
        $field = $form->field('period', ['type' => 'range']);
        $html = $form->open() . $field->control() . $form->errorSummary() . $form->close();
        static::assertNull($field->focusTarget);
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//a[@href])'));
    }

    public function testIndependentlyRenderedChildrenStillRequireTheirParentFeedback(): void
    {
        $form = $this->helper()->bind(null, ['types' => ['range' => new DateRangeType()]]);
        $field = $form->field('period', ['type' => 'range', 'help' => 'Pick dates']);
        $form->open();
        $field->inputs['start']->input();
        try {
            $form->close();
            static::fail('Expected parent help reference enforcement.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString($field->state->id . '-help', $logicException->getMessage());
        }

        static::assertStringContainsString('Pick dates', $field->help());
        $form->close();
    }

    public function testChildCanExplicitlyReplaceInheritedFeedbackAndParentRequiredDoesNotForceChildren(): void
    {
        $form = $this->helper()->bind(null, ['types' => ['range' => new ConfiguredType([
            'start' => ['attributes' => ['aria-describedby' => null]],
            'end' => [],
        ])]]);
        $field = $form->field('period', ['type' => 'range', 'required' => true, 'help' => 'Dates']);
        static::assertFalse($field->inputs['start']->attributes()->has('aria-describedby'));
        static::assertFalse($field->inputs['start']->attributes()->has('required'));
        static::assertTrue($field->inputs['end']->attributes()->has('aria-describedby'));
    }

    public function testCompositeBodiesRetainThemeVariantsParentLookupAndIntegrationAttributes(): void
    {
        $form = $this->helper()->bind(
            null,
            ['types' => ['range' => new DateRangeType()]],
            theme: new Theme(
                layers: ['widgets/theme'],
            ),
        );
        $field = $form->field('period', [
            'type' => 'range',
            'variant' => 'compact',
            'templates' => ['composite' => 'widgets/parent'],
            'attributes' => ['data-controller' => 'range'],
        ]);
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
        $form = $this->helper()->bind(null, ['types' => ['settings' => new ConfiguredType([
            'id' => ['type' => 'hidden', 'value' => 42],
        ])]]);
        $field = $form->field('settings', ['type' => 'settings', 'help' => 'Visible group help']);
        $html = $form->open() . $field->inputs['id']->input() . $form->close();
        static::assertStringNotContainsString('aria-describedby', $html);
        static::assertStringContainsString('name="settings[id]"', $html);
    }

    public function testCompositeElementReceivesWrapperAttributesWhileChildrenKeepConstraints(): void
    {
        $form = $this->helper()->bind(null, ['types' => ['range' => new DateRangeType()]]);
        $field = $form->field('period', [
            'type' => 'range',
            'disabled' => true,
            'readonly' => true,
            'required' => true,
            'attributes' => ['class' => 'date-range', 'data-controller' => 'calendar', 'min' => '2026-01-01'],
        ]);
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
