<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use FormCompose\Rendering\Theme;
use FormCompose\Test\FormTestCase;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks identities on themed choice wrappers as well as actual choice inputs.
 */
class ChoiceIdentityTest extends FormTestCase
{
    #[DataProvider('choiceParts')]
    public function testThemedChoiceIdsAreReserved(string $type, string $part): void
    {
        $form = $this->helper()->bind(theme: new Theme(defaults: [$part => ['id' => 'choice-wrapper']]));
        $form->open();
        $form->control('status', ['type' => $type, 'options' => ['Group' => ['a' => 'A']]]);
        $this->expectException(LogicException::class);
        $form->control('other', ['id' => 'choice-wrapper']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function choiceParts(): array
    {
        return [
            'optgroup' => ['select', 'choice-group'],
            'radio group' => ['radio', 'choice-group'],
            'choice container' => ['radio', 'choice-container'],
            'choice label' => ['multicheckbox', 'choice-label'],
        ];
    }

    public function testAnEmptyChoiceIdFailsWithoutReservingTheField(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        try {
            $form->control('status', ['type' => 'radio', 'options' => [['value' => 'a', 'text' => 'A', 'id' => '']]]);
            static::fail('Expected an invalid emitted identity.');
        } catch (LogicException $logicException) {
            static::assertSame('Rendered element IDs must be nonempty.', $logicException->getMessage());
        }

        static::assertStringContainsString('name="status"', $form->control('status'));
        $form->close();
    }
}
