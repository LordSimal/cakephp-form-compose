<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use FormCompose\Test\FormTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

/**
 * Ensures non-choice inputs never consume choice sources or reserve unrendered IDs.
 */
class NonChoiceOptionsTest extends FormTestCase
{
    #[DataProvider('nonChoiceTypes')]
    public function testNonChoiceInputsIgnoreChoiceIteratorsAndTheirIdentities(string $type): void
    {
        $consumption = new stdClass();
        $consumption->started = false;

        $options = static function () use ($consumption): Generator {
            $consumption->started = true;
            yield 'a' => 'A';
        };
        $choices = $options();
        $form = $this->helper()->bind(null, ['type' => 'file']);
        $field = $form->field('sample', ['type' => $type, 'options' => $choices]);
        $form->open();
        $field->input();
        static::assertFalse($consumption->started);
        static::assertSame($choices, $field->state->options['options']);
        $html = $form->control('actual', ['id' => $field->state->id . '-a']);
        static::assertStringContainsString('id="' . $field->state->id . '-a"', $html);
        $form->close();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonChoiceTypes(): array
    {
        return [
            'text' => ['text'],
            'password' => ['password'],
            'textarea' => ['textarea'],
            'checkbox' => ['checkbox'],
            'hidden' => ['hidden'],
            'file' => ['file'],
        ];
    }

    public function testInferredPasswordsIgnoreConventionalChoicesDuringHandwrittenRegistration(): void
    {
        $consumption = new stdClass();
        $consumption->started = false;

        $options = static function () use ($consumption): Generator {
            $consumption->started = true;
            yield 'a' => 'A';
        };
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $helper->getView()->set('passwords', $options());
        $form = $helper->bind();
        $field = $form->field('password');
        $html = $form->open() . '<input type="password"' . (string)$field->attributes() . '>';
        $field->register();
        static::assertSame('password', $field->state->type);
        static::assertFalse($consumption->started);
        $html .= $form->control('actual', ['id' => $field->state->id . '-a']) . $form->close();
        static::assertStringContainsString('_Token[fields]', $html);
    }

    public function testInferredPasswordRenderingDoesNotReserveDiscoveredChoiceIds(): void
    {
        $helper = $this->helper();
        $helper->getView()->set('passwords', ['a' => 'A']);
        $form = $helper->bind();
        $html = $form->open() . $form->control('password');
        $html .= $form->control('actual', ['id' => 'form-compose-1-password-a']) . $form->close();
        static::assertSame(1.0, $this->xpath($html)->evaluate('count(//*[@id="form-compose-1-password-a"])'));
    }
}
