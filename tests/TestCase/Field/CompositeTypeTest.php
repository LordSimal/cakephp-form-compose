<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Field;

use Cake\View\Form\ArrayContext;
use FormCompose\Test\Fixture\ContactField;
use FormCompose\Test\Fixture\ContactFormHelper;
use FormCompose\Test\Fixture\Widget\ConfiguredType;
use FormCompose\Test\Fixture\Widget\DateRangeType;
use FormCompose\Test\Fixture\Widget\MoneyType;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use LogicException;

/**
 * Tests composite binding and independent PHP layouts through the public form API.
 */
class CompositeTypeTest extends FormTestCase
{
    public function testOneDeclarationSupportsDefaultAndReorderedPartLayouts(): void
    {
        $helper = $this->helper(['period' => ['start' => '2026-10-01', 'end' => '2026-10-17']]);
        foreach ([[], ['templates' => ['composite' => 'widgets/inline']]] as $layout) {
            $form = $helper->bind(null, ['types' => ['date-range' => new DateRangeType()]]);
            $field = $form->field(
                'period',
                $layout + ['type' => 'date-range', 'templateData' => ['caption' => '<Range>']],
            );
            static::assertTrue($field->state->grouped());
            static::assertSame($field->inputs['start'], $field->inputs['start']);
            $initialTarget = $field->focusTarget;
            static::assertNull($initialTarget);
            static::assertSame('period[start]', $field->inputs['start']->state->name);
            static::assertSame('2026-10-01', $field->inputs['start']->state->value);
            $html = $form->open() . $field->control() . $form->close();
            $xpath = $this->xpath($html);
            static::assertSame(1.0, $xpath->evaluate('count(//fieldset/legend[text()="Period"])'));
            static::assertSame(2.0, $xpath->evaluate('count(//input[@type="date"])'));
            static::assertSame($field->inputs['start']->state->id, $field->focusTarget);
            if ($layout === []) {
                static::assertStringNotContainsString('inline-range', $html);
                continue;
            }

            static::assertSame('period[end]', $xpath->evaluate('string(//section/aside/input/@name)'));
            static::assertSame('period[start]', $xpath->evaluate('string(//section/main/input/@name)'));
            static::assertStringContainsString('data-caption="&lt;Range&gt;"', $html);
        }
    }

    public function testNestedNamesCustomIdentitiesAndExplicitEmptyValuesArePreserved(): void
    {
        $form = $this->helper()->bind(null, ['types' => ['details' => new ConfiguredType([
            'zero' => [],
            'no' => [],
            'empty' => [],
            'null' => [],
            'missing' => ['default' => 'fallback'],
            'address.post-code' => ['name' => 'postal', 'id' => 'postal-id'],
        ])]]);
        $field = $form->field('items.0.details', [
            'type' => 'details',
            'name' => 'order[details]',
            'id' => 'details-id',
            'value' => ['zero' => 0, 'no' => false, 'empty' => '', 'null' => null],
        ]);
        foreach (['zero' => 0, 'no' => false, 'empty' => '', 'null' => null, 'missing' => null] as $name => $value) {
            static::assertSame($value, $field->inputs[$name]->state->value);
            static::assertSame('order[details][' . $name . ']', $field->inputs[$name]->state->name);
            static::assertSame('details-id.' . $name, $field->inputs[$name]->state->id);
        }

        static::assertSame('postal', $field->inputs['address.post-code']->state->name);
        static::assertSame('postal-id', $field->inputs['address.post-code']->state->id);
        static::assertSame('items.0.details.address.post-code', $field->inputs['address.post-code']->state->path);
    }

    public function testMissingChildrenResolveContextDefaultsAndExplicitChildValuesWin(): void
    {
        $context = new ArrayContext(['defaults' => ['period' => ['start' => 'context', 'end' => 'context-end']]]);
        $form = $this->helper(['period' => ['start' => null]])->bind($context, ['types' => [
            'range' => new ConfiguredType(['start' => [], 'end' => [], 'explicit' => ['value' => false]]),
        ]]);
        $field = $form->field('period', ['type' => 'range']);
        static::assertNull($field->inputs['start']->state->value);
        static::assertSame('context-end', $field->inputs['end']->state->value);
        static::assertFalse($field->inputs['explicit']->state->value);
        static::assertSame([], $form->field('ordinary')->inputs);
    }

    public function testDifferentNestedCompositeTypesResolveThroughTheSameBinding(): void
    {
        $form = $this->helper(['booking' => ['dates' => ['start' => '2026-10-01']]])->bind(null, ['types' => [
            'booking' => new ConfiguredType(['dates' => ['type' => 'range']]),
            'range' => new DateRangeType(),
        ]]);
        $field = $form->field('booking', ['type' => 'booking']);
        $child = $field->inputs['dates']->inputs['start'];
        static::assertSame('booking[dates][start]', $child->state->name);
        static::assertSame('2026-10-01', $child->state->value);
        $html = $form->open() . $field->control() . $form->close();
        static::assertSame(2.0, $this->xpath($html)->evaluate('count(//fieldset)'));
        static::assertSame($child->state->id, $field->focusTarget);
    }

    public function testNativeChildrenRetainChoiceFallbackMultipleAndHiddenProtocols(): void
    {
        $form = $this->helper()->bind(null, ['types' => ['settings' => new ConfiguredType([
            'id' => ['type' => 'hidden', 'value' => 7],
            'active' => ['type' => 'checkbox', 'value' => false],
            'tags' => ['type' => 'select', 'multiple' => true, 'options' => ['a' => 'A', 'b' => 'B'], 'value' => ['b']],
        ])]]);
        $html = $form->open() . $form->control('settings', ['type' => 'settings']) . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame('7', $xpath->evaluate('string(//input[@name="settings[id]"]/@value)'));
        static::assertSame('0', $xpath->evaluate('string(//input[@name="settings[active]"][@type="hidden"]/@value)'));
        static::assertSame('settings[tags][]', $xpath->evaluate('string(//select/@name)'));
        static::assertSame('b', $xpath->evaluate('string(//option[@selected]/@value)'));
    }

    public function testChildFilesRequireMultipartAndFailedCompositeCanBeRetried(): void
    {
        $form = $this->helper()->bind(null, ['types' => ['upload' => new ConfiguredType([
            'name' => [],
            'file' => ['type' => 'file'],
        ])]]);
        $field = $form->field('upload', ['type' => 'upload']);
        $form->open();
        try {
            $field->control();
            static::fail('Expected multipart enforcement.');
        } catch (LogicException $logicException) {
            static::assertSame('File inputs require a multipart form.', $logicException->getMessage());
        }

        static::assertNull($field->inputs['name']->focusTarget);
        static::assertStringContainsString('name="upload[name]"', $field->inputs['name']->input());
        $form->close();
        $multipart = $this->helper()->bind(null, ['types' => ['upload' => new ConfiguredType(['file' => [
            'type' => 'file',
        ]])]]);
        $html =
            $multipart->open(['type' => 'file'])
            . $multipart->control('upload', ['type' => 'upload'])
            . $multipart->close();
        static::assertStringContainsString('enctype="multipart/form-data"', $html);
        static::assertStringContainsString('type="file"', $html);
    }

    public function testRecursiveDeclarationsFailBeforeUnboundedRendering(): void
    {
        $form = $this->helper()->bind(null, ['types' => ['recursive' => new ConfiguredType(['again' => [
            'type' => 'recursive',
        ]])]]);
        $field = $form->field('tree', ['type' => 'recursive']);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Recursive composite field type: recursive');
        static::assertNotEmpty($field->inputs['again']->inputs);
    }

    public function testInvalidChildPathsAreRejectedDuringInspection(): void
    {
        $form = $this->helper()->bind(null, ['types' => ['invalid' => new ConfiguredType(['bad/path' => []])]]);
        $this->expectException(InvalidArgumentException::class);
        static::assertNotEmpty($form->field('sample', ['type' => 'invalid'])->inputs);
    }

    public function testDocumentedMoneyWidgetPreservesZeroAndSelectsTheCurrency(): void
    {
        $form = $this->helper()->bind(null, ['types' => ['money' => new MoneyType()]]);
        $html =
            $form->open()
            . $form->control('price', [
                'type' => 'money',
                'value' => ['amount' => 0, 'currency' => 'EUR'],
            ])
            . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame('0', $xpath->evaluate('string(//input[@name="price[amount]"]/@value)'));
        static::assertSame('0.01', $xpath->evaluate('string(//input[@name="price[amount]"]/@step)'));
        static::assertSame(
            'EUR',
            $xpath->evaluate('string(//select[@name="price[currency]"]/option[@selected]/@value)'),
        );
        static::assertStringContainsString('money-fields', $html);
    }

    public function testChildrenUseTheApplicationsResolverAndBoundFieldSubclass(): void
    {
        $helper = new ContactFormHelper($this->helper()->getView());
        $form = $helper->bind(null, ['types' => ['range' => new DateRangeType()]]);
        $field = $form->field('period', ['type' => 'range']);
        static::assertInstanceOf(ContactField::class, $field->inputs['start']);
        $html = $form->open() . $field->control() . $form->close();
        static::assertSame(3, substr_count($html, 'data-contact-label'));
    }
}
