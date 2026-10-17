<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies empty selection semantics preserve explicit values and application choices.
 */
class SelectPlaceholderTest extends FormTestCase
{
    /**
     * @param array<string, mixed> $options Select options.
     */
    #[DataProvider('emptyOptions')]
    public function testEmptyOptionBehavior(array $options, int $expected): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('status', ['type' => 'select', 'options' => ['a' => 'A']] + $options);
        static::assertSame((float)$expected, $this->xpath($html)->evaluate('count(//option[@value=""])'));
        $form->close();
    }

    /**
     * @return array<string, array{array<string, mixed>, int}>
     */
    public static function emptyOptions(): array
    {
        return [
            'optional' => [[], 1],
            'required' => [['required' => true], 0],
            'disabled' => [['empty' => false], 0],
            'explicit blank' => [['empty' => true], 1],
            'explicit label' => [['empty' => 'Choose…'], 1],
            'empty label' => [['empty' => ''], 1],
            'multiple' => [['multiple' => true], 0],
            'explicit multiple' => [['multiple' => true, 'empty' => true], 1],
        ];
    }

    public function testEmptyLabelsEscapeAndDoNotReplaceExistingEmptyChoices(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('status', ['options' => ['a' => 'A'], 'empty' => '<Choose>']);
        static::assertStringContainsString('&lt;Choose&gt;', $html);
        $existing = $form->control('other', [
            'options' => ['Group' => ['' => 'Existing', 'a' => 'A']],
            'empty' => 'Ignored',
        ]);
        static::assertSame(1.0, $this->xpath($existing)->evaluate('count(//option[@value=""])'));
        static::assertStringContainsString('Existing', $existing);
        static::assertStringNotContainsString('Ignored', $existing);
        $form->close();
    }

    public function testZeroAndFalseAreNotSelectedAsTheEmptyPlaceholder(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('zero', ['options' => [0 => 'Zero'], 'value' => 0]);
        $html .= $form->control('off', ['options' => [0 => 'Off'], 'value' => false]);
        static::assertSame(2.0, $this->xpath($html)->evaluate('count(//option[@value="0"][@selected])'));
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//option[@value=""][@selected])'));
        $form->close();
    }

    public function testInvalidEmptyOptionFailsBeforeRegisteringInputs(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        try {
            $form->control('status', ['options' => [], 'empty' => []]);
            static::fail('Expected invalid empty configuration to fail.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertSame(
                'The empty option must be a boolean or a string.',
                $invalidArgumentException->getMessage(),
            );
        }

        static::assertStringContainsString('name="status"', $form->control('status'));
        $form->close();
    }

    /**
     * @param mixed $value Bound value.
     * @param array<string, mixed> $options Explicit selection behavior.
     */
    #[DataProvider('retainedSelections')]
    public function testRetainedBlankOptionsRespectBoundValues(mixed $value, array $options, string $selected): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('status', [
            'options' => [
                ['value' => 'a', 'text' => 'A'],
                'Other' => ['b' => 'B'],
                'Group' => [
                    ['value' => '0', 'text' => 'Zero'],
                    ['value' => '', 'text' => 'Existing <blank>', 'data-empty' => 'kept'],
                ],
                ['value' => '', 'text' => 'Second blank'],
            ],
            'value' => $value,
        ] + $options);
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

    /** @return array<string, array{mixed, array<string, mixed>, string}> */
    public static function retainedSelections(): array
    {
        return [
            'null' => [null, [], ''],
            'nonempty' => ['a', [], 'a'],
            'zero' => [0, [], '0'],
            'false' => [false, [], '0'],
            'disabled placeholder' => [null, ['empty' => false], 'none'],
            'multiple' => [null, ['multiple' => true, 'empty' => true], 'none'],
        ];
    }
}
