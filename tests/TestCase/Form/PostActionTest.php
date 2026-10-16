<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use FormCompose\Form\PostAction;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

/**
 * Checks invalid action configuration before forms, scripts, or view blocks are emitted.
 */
class PostActionTest extends FormTestCase
{
    /** @param array<string, mixed> $options Invalid configuration. */
    #[DataProvider('invalidOptions')]
    public function testInvalidOptionsDoNotPolluteViewBlocks(array $options): void
    {
        $helper = $this->helper();
        try {
            $helper->postLink('Delete', '/delete', ['block' => 'actions'] + $options);
            static::fail('Expected invalid action options.');
        } catch (InvalidArgumentException) {
            static::assertSame('', $helper->getView()->fetch('actions'));
        }
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidOptions(): array
    {
        return [
            'form bag' => [['form' => 'wrong']],
            'data bag' => [['data' => false]],
            'method type' => [['method' => []]],
            'get method' => [['method' => 'get']],
            'unknown method' => [['method' => 'trace']],
            'confirmation type' => [['confirm' => true]],
            'empty id' => [['form' => ['id' => '']]],
            'numeric id' => [['form' => ['id' => 42]]],
            'invalid data path' => [['data' => ['bad[path]' => 'value']]],
            'method injection' => [['data' => ['_method' => 'GET']]],
            'CSRF injection' => [['data' => ['_csrfToken' => bin2hex(random_bytes(8))]]],
            'token injection' => [['data' => ['_Token' => ['fields' => 'bad']]]],
            'composite leaf' => [['data' => ['object' => new stdClass()]]],
            'empty nested array' => [['data' => ['empty' => []]]],
            'scalar list' => [['data' => ['ids' => [1, 2]]]],
            'numeric root' => [['data' => [0 => 'first']]],
            'negative numeric root' => [['data' => [-1 => 'first']]],
            'button type' => [['type' => 'button']],
            'href override' => [['href' => '/get-delete']],
            'action override' => [['formaction' => '/other']],
            'method override' => [['formmethod' => 'get']],
            'encoding override' => [['formenctype' => 'text/plain']],
            'validation override' => [['formnovalidate' => true]],
            'form marker override' => [['data-form-compose-action' => 'other']],
            'template conflict' => [['template' => 'actions/link', 'templates' => ['control' => 'other']]],
            'templates bag' => [['templates' => false]],
            'template data' => [['templateData' => 'wrong']],
        ];
    }

    /** @param mixed $block Invalid placement value. */
    #[DataProvider('invalidBlocks')]
    public function testInvalidBlockOptionsAreRejected(mixed $block): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PostAction('button', '/go', ['block' => $block]);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidBlocks(): array
    {
        return ['empty' => [''], 'whitespace' => ['  '], 'array' => [[]], 'number' => [1]];
    }

    public function testInvalidTriggerKindsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PostAction('input', '/go', []);
    }

    public function testDisabledConfirmationsAndInlinePlacementAreNormalized(): void
    {
        foreach ([false, '', null] as $confirm) {
            $action = new PostAction('button', '/go', ['confirm' => $confirm, 'block' => false]);
            static::assertNull($action->block);
            static::assertNull($action->confirm);
        }
    }
}
