<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use Cake\View\Form\ArrayContext;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldSpec;
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
        foreach ([
            new FieldConfig(),
            FieldConfig::builder()->partTemplate('composite', 'widgets/inline'),
        ] as $layout) {
            $form = $helper->form()
                ->types(['date-range' => new DateRangeType()])
                ->bind();
            $field = $form->field('period')
                ->apply(
                    FieldConfig::builder()
                        ->type('date-range')
                        ->templateData(['caption' => '<Range>'])
                        ->merge($layout),
                )
                ->resolve();
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
            if ($layout->presentation->templates === []) {
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
        $form = $this->helper()
            ->form()
            ->types(['details' => new ConfiguredType([
                new FieldSpec('zero', new FieldConfig()),
                new FieldSpec('no', new FieldConfig()),
                new FieldSpec('empty', new FieldConfig()),
                new FieldSpec('null', new FieldConfig()),
                new FieldSpec('missing', FieldConfig::builder()->default('fallback')),
                new FieldSpec(
                    'address.post-code',
                    FieldConfig::builder()->name('postal')->id('postal-id'),
                ),
            ])])
            ->bind();
        $field = $form->field('items.0.details')
            ->type('details')
            ->name('order[details]')
            ->id('details-id')
            ->value(['zero' => 0, 'no' => false, 'empty' => '', 'null' => null])
            ->resolve();
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
        $form = $this->helper(['period' => ['start' => null]])
            ->form($context)
            ->types([
                'range' => new ConfiguredType([
                    new FieldSpec('start', new FieldConfig()),
                    new FieldSpec('end', new FieldConfig()),
                    new FieldSpec('explicit', FieldConfig::builder()->value(false)),
                ]),
            ])
            ->bind();
        $field = $form->field('period')->type('range')->resolve();
        static::assertNull($field->inputs['start']->state->value);
        static::assertSame('context-end', $field->inputs['end']->state->value);
        static::assertFalse($field->inputs['explicit']->state->value);
        static::assertSame([], $form->field('ordinary')->resolve()->inputs);
    }

    public function testDifferentNestedCompositeTypesResolveThroughTheSameBinding(): void
    {
        $form = $this->helper(['booking' => ['dates' => ['start' => '2026-10-01']]])
            ->form()
            ->types([
                'booking' => new ConfiguredType([new FieldSpec(
                    'dates',
                    FieldConfig::builder()->type('range'),
                )]),
                'range' => new DateRangeType(),
            ])
            ->bind();
        $field = $form->field('booking')->type('booking')->resolve();
        $child = $field->inputs['dates']->inputs['start'];
        static::assertSame('booking[dates][start]', $child->state->name);
        static::assertSame('2026-10-01', $child->state->value);
        $html = $form->open() . $field->control() . $form->close();
        static::assertSame(2.0, $this->xpath($html)->evaluate('count(//fieldset)'));
        static::assertSame($child->state->id, $field->focusTarget);
    }

    public function testNativeChildrenRetainChoiceFallbackMultipleAndHiddenProtocols(): void
    {
        $form = $this->helper()
            ->form()
            ->types(['settings' => new ConfiguredType([
                new FieldSpec(
                    'id',
                    FieldConfig::builder()->type('hidden')->value(7),
                ),
                new FieldSpec(
                    'active',
                    FieldConfig::builder()->type('checkbox')->value(false),
                ),
                new FieldSpec(
                    'tags',
                    FieldConfig::builder()
                        ->type('select')
                        ->multiple(true)
                        ->choices(['a' => 'A', 'b' => 'B'])
                        ->value(['b']),
                ),
            ])])
            ->bind();
        $html =
            $form->open()
            . $form->control('settings')->type('settings')->render()
            . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame('7', $xpath->evaluate('string(//input[@name="settings[id]"]/@value)'));
        static::assertSame('0', $xpath->evaluate('string(//input[@name="settings[active]"][@type="hidden"]/@value)'));
        static::assertSame('settings[tags][]', $xpath->evaluate('string(//select/@name)'));
        static::assertSame('b', $xpath->evaluate('string(//option[@selected]/@value)'));
    }

    public function testChildFilesRequireMultipartAndFailedCompositeCanBeRetried(): void
    {
        $form = $this->helper()
            ->form()
            ->types(['upload' => new ConfiguredType([
                new FieldSpec('name', new FieldConfig()),
                new FieldSpec('file', FieldConfig::builder()->type('file')),
            ])])
            ->bind();
        $field = $form->field('upload')->type('upload')->resolve();
        $form->open();
        try {
            $field->control();
            static::fail('Expected multipart enforcement.');
        } catch (LogicException $logicException) {
            static::assertSame('File inputs require a multipart form.', $logicException->getMessage());
        }

        static::assertNull($field->inputs['name']->focusTarget);
        static::assertStringContainsString('name="upload[name]"', $field->inputs['name']->input()->render());
        $form->close();
        $multipart = $this->helper()
            ->form()
            ->types(['upload' => new ConfiguredType([new FieldSpec(
                'file',
                FieldConfig::builder()->type('file'),
            )])])
            ->multipart()
            ->bind();
        $html =
            $multipart->open()
            . $multipart->control('upload')->type('upload')->render()
            . $multipart->close();
        static::assertStringContainsString('enctype="multipart/form-data"', $html);
        static::assertStringContainsString('type="file"', $html);
    }

    public function testRecursiveDeclarationsFailBeforeUnboundedRendering(): void
    {
        $form = $this->helper()
            ->form()
            ->types(['recursive' => new ConfiguredType([new FieldSpec(
                'again',
                FieldConfig::builder()->type('recursive'),
            )])])
            ->bind();
        $field = $form->field('tree')->type('recursive')->resolve();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Recursive composite field type: recursive');
        static::assertNotEmpty($field->inputs['again']->inputs);
    }

    public function testInvalidChildPathsAreRejectedDuringAuthoring(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FieldSpec('bad/path');
    }

    public function testDocumentedMoneyWidgetPreservesZeroAndSelectsTheCurrency(): void
    {
        $form = $this->helper()
            ->form()
            ->types(['money' => new MoneyType()])
            ->bind();
        $html =
            $form->open()
            . $form->control('price')
                ->type('money')
                ->value(['amount' => 0, 'currency' => 'EUR'])
                ->render()
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
        $form = $helper->form()
            ->types(['range' => new DateRangeType()])
            ->bind();
        $field = $form->field('period')->type('range')->resolve();
        static::assertInstanceOf(ContactField::class, $field->inputs['start']);
        $html = $form->open() . $field->control() . $form->close();
        static::assertSame(3, substr_count($html, 'data-contact-label'));
    }
}
