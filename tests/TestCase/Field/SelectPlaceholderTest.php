<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Field;

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
}
