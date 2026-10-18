<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use FormCompose\Configuration\ChoiceConfig;
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
        $form = $this->helper()
            ->form()
            ->theme(new Theme(defaults: [$part => ['id' => 'choice-wrapper']]))
            ->bind();
        $form->open();
        $form->control('status')
            ->type($type)
            ->choices(['Group' => ['a' => 'A']])
            ->render();
        $this->expectException(LogicException::class);
        $form->control('other')->id('choice-wrapper')->render();
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
        $form = $this->helper()->form()->bind();
        $form->open();
        try {
            $form->control('status')
                ->type('radio')
                ->choices([new ChoiceConfig('a', 'A', [], '')])
                ->render();
            static::fail('Expected an invalid emitted identity.');
        } catch (LogicException $logicException) {
            static::assertSame('Rendered element IDs must be nonempty.', $logicException->getMessage());
        }

        static::assertStringContainsString('name="status"', $form->control('status')->render());
        $form->close();
    }
}
