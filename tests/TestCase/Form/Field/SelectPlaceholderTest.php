<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use FormCompose\Configuration\ChoiceConfig;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Test\FormTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use TypeError;

/**
 * Verifies empty selection semantics preserve explicit values and application choices.
 */
class SelectPlaceholderTest extends FormTestCase
{
    #[DataProvider('emptyOptions')]
    public function testEmptyOptionBehavior(FieldConfig $options, int $expected): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();

        $html = $form->control('status')
            ->apply($options->merge(FieldConfig::builder()->type('select')->choices(['a' => 'A'])))
            ->render();
        static::assertSame((float)$expected, $this->xpath($html)->evaluate('count(//option[@value=""])'));
        $form->close();
    }

    /**
     * @return array<string, array{\FormCompose\Configuration\FieldConfig, int}>
     */
    public static function emptyOptions(): array
    {
        return [
            'optional' => [new FieldConfig(), 1],
            'required' => [FieldConfig::builder()->required(true), 0],
            'disabled' => [FieldConfig::builder()->placeholder(false), 0],
            'explicit blank' => [FieldConfig::builder()->placeholder(true), 1],
            'explicit label' => [FieldConfig::builder()->placeholder('Choose…'), 1],
            'empty label' => [FieldConfig::builder()->placeholder(''), 1],
            'multiple' => [FieldConfig::builder()->multiple(true), 0],
            'explicit multiple' => [
                FieldConfig::builder()->multiple(true)->placeholder(true),
                1,
            ],
        ];
    }

    public function testEmptyLabelsEscapeAndDoNotReplaceExistingEmptyChoices(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();

        $html = $form->control('status')
            ->choices(['a' => 'A'])
            ->placeholder('<Choose>')
            ->render();
        static::assertStringContainsString('&lt;Choose&gt;', $html);
        $existing = $form->control('other')
            ->choices(['Group' => ['' => 'Existing', 'a' => 'A']])
            ->placeholder('Ignored')
            ->render();
        static::assertSame(1.0, $this->xpath($existing)->evaluate('count(//option[@value=""])'));
        static::assertStringContainsString('Existing', $existing);
        static::assertStringNotContainsString('Ignored', $existing);
        $form->close();
    }

    public function testZeroAndFalseAreNotSelectedAsTheEmptyPlaceholder(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();

        $html = $form->control('zero')
            ->choices([0 => 'Zero'])
            ->value(0)
            ->render();
        $html .= $form->control('off')
            ->choices([0 => 'Off'])
            ->value(false)
            ->render();
        static::assertSame(2.0, $this->xpath($html)->evaluate('count(//option[@value="0"][@selected])'));
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//option[@value=""][@selected])'));
        $form->close();
    }

    public function testInvalidEmptyOptionFailsBeforeRegisteringInputs(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $this->expectException(TypeError::class);
        try {
            $this->invokeBuilder($form->control('status')->choices([]), 'placeholder', []);
        } finally {
            static::assertStringContainsString('name="status"', $form->control('status')->render());
            $form->close();
        }
    }

    /**
     * @param mixed $value Bound value.
     */
    #[DataProvider('retainedSelections')]
    public function testRetainedBlankOptionsRespectBoundValues(
        mixed $value,
        FieldConfig $options,
        string $selected,
    ): void {
        $form = $this->helper()->form()->bind();
        $form->open();

        $html = $form->control('status')
            ->apply(
                $options->merge(
                    FieldConfig::builder()
                        ->choices([
                            new ChoiceConfig('a', 'A', []),
                            'Other' => ['b' => 'B'],
                            'Group' => [
                                new ChoiceConfig('0', 'Zero', []),
                                new ChoiceConfig('', 'Existing <blank>', ['data-empty' => 'kept']),
                            ],
                            new ChoiceConfig('', 'Second blank', []),
                        ])
                        ->value($value),
                ),
            )
            ->render();
        $xpath = $this->xpath($html);
        static::assertSame(
            $selected === 'none' ? '' : $selected,
            $xpath->evaluate('string(//option[@selected]/@value)'),
        );
        static::assertSame($selected === 'none' ? 0 : 1, $xpath->query('//option[@selected]')->length);
        static::assertSame('kept', $xpath->evaluate('string(//optgroup/option[@value=""]/@data-empty)'));
        static::assertStringContainsString('Existing &lt;blank&gt;', $html);
        static::assertSame(2, $xpath->query('//option[@value=""]')->length);
        $form->close();
    }

    /** @return array<string, array{mixed, \FormCompose\Configuration\FieldConfig, string}> */
    public static function retainedSelections(): array
    {
        return [
            'null' => [null, new FieldConfig(), ''],
            'nonempty' => ['a', new FieldConfig(), 'a'],
            'zero' => [0, new FieldConfig(), '0'],
            'false' => [false, new FieldConfig(), '0'],
            'disabled placeholder' => [null, FieldConfig::builder()->placeholder(false), 'none'],
            'multiple' => [
                null,
                FieldConfig::builder()->multiple(true)->placeholder(true),
                'none',
            ],
        ];
    }
}
