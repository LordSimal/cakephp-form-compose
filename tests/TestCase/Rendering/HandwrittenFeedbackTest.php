<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\View\Form\ArrayContext;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies explicit registration of application-owned feedback elements.
 */
class HandwrittenFeedbackTest extends FormTestCase
{
    public function testHandwrittenFeedbackReservesConfiguredIdsWithoutAddingSubmissions(): void
    {
        $form = $this->helper(attributes: ['formTokenData' => []])->form()->bind();
        $field = $form->field('title')
            ->help('Helpful text')
            ->partAttributes('help', ['id' => 'title-hint'])
            ->resolve();
        $html = $form->open() . $field->input()->render();
        $html .= '<p' . (string)$field->attributes('help') . '>Handwritten help</p>';
        $form->registerFeedback($field, 'help');
        $html .= $form->close();
        static::assertStringContainsString('aria-describedby="title-hint"', $html);
        static::assertSame(1.0, $this->xpath($html)->evaluate('count(//*[@id="title-hint"])'));
        $debug = $this->xpath($html)->evaluate('string(//input[@name="_Token[debug]"]/@value)');
        static::assertIsString($debug);
        static::assertStringNotContainsString('title-hint', $debug);
    }

    public function testHandwrittenInputMustAlsoSupplyItsReferencedErrors(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['title' => ['bad' => 'Fix title']]]))
            ->bind();
        $field = $form->field('title')->resolve();
        $form->open();
        $html = '<input' . (string)$field->attributes() . '>';
        $field->register();
        try {
            $form->close();
            static::fail('Expected handwritten feedback to be registered.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('title-errors', $logicException->getMessage());
        }

        $html .= '<p' . (string)$field->attributes('errors') . '>Fix title</p>';
        $form->registerFeedback($field, 'errors');
        $html .= $form->close();
        static::assertStringContainsString('id="form-compose-1-title-errors"', $html);
    }

    public function testHandwrittenFeedbackCannotReuseARenderedIdentity(): void
    {
        $form = $this->helper()->form()->bind();
        $field = $form->field('title')->help('Hint')->resolve();
        $form->open();
        $field->help()->render();
        $this->expectException(LogicException::class);
        $form->registerFeedback($field, 'help');
    }

    #[DataProvider('invalidParts')]
    public function testOnlyFeedbackPartsCanBeRegistered(string $part): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $this->expectException(InvalidArgumentException::class);
        $form->registerFeedback($form->field('title')->resolve(), $part);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidParts(): array
    {
        return ['input' => ['input'], 'label' => ['label'], 'unknown' => ['unknown']];
    }
}
