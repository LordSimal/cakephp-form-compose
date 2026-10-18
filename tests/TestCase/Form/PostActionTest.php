<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Form\PostAction;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use TypeError;

/** Verifies invalid typed actions cannot emit forms or pollute view blocks. */
class PostActionTest extends FormTestCase
{
    /** @param class-string<\Throwable> $exception Expected failure. */
    #[DataProvider('invalidSettings')]
    public function testInvalidSettingsDoNotPolluteViewBlocks(string $method, mixed $value, string $exception): void
    {
        $helper = $this->helper();
        $this->expectException($exception);
        try {
            $action = $helper->postLink('Delete', '/delete')->block('actions');
            if ($method === 'form-id') {
                $form = FormConfig::builder();
                $this->invokeBuilder($form, 'id', $value);
                $action->form($form->config());
            } else {
                $this->invokeBuilder($action, $method, $value);
            }

            $action->render();
        } finally {
            static::assertSame('', $helper->getView()->fetch('actions'));
            static::assertStringContainsString('<button', $helper->postButton('Continue', '/go')->render());
        }
    }

    /** @return array<string, array{string, mixed, class-string<\Throwable>}> */
    public static function invalidSettings(): array
    {
        return [
            'form type' => ['form', 'wrong', TypeError::class],
            'data type' => ['data', false, TypeError::class],
            'method type' => ['method', [], TypeError::class],
            'get method' => ['method', 'get', InvalidArgumentException::class],
            'unknown method' => ['method', 'trace', InvalidArgumentException::class],
            'confirm type' => ['confirm', true, TypeError::class],
            'empty form id' => ['form-id', '', InvalidArgumentException::class],
            'form id type' => ['form-id', 42, TypeError::class],
            'invalid data path' => ['data', ['bad[path]' => 'value'], InvalidArgumentException::class],
            'method injection' => ['data', ['_method' => 'GET'], InvalidArgumentException::class],
            'CSRF injection' => ['data', ['_csrfToken' => str_repeat('x', 16)], InvalidArgumentException::class],
            'token injection' => ['data', ['_Token' => ['fields' => 'bad']], InvalidArgumentException::class],
            'composite leaf' => ['data', ['object' => new stdClass()], InvalidArgumentException::class],
            'empty nested array' => ['data', ['empty' => []], InvalidArgumentException::class],
            'scalar list' => ['data', ['ids' => [1, 2]], InvalidArgumentException::class],
            'numeric root' => ['data', [0 => 'first'], InvalidArgumentException::class],
            'negative root' => ['data', [-1 => 'first'], InvalidArgumentException::class],
            'button type' => ['attributes', ['type' => 'button'], InvalidArgumentException::class],
            'href override' => ['attributes', ['href' => '/get-delete'], InvalidArgumentException::class],
            'action override' => ['attributes', ['formaction' => '/other'], InvalidArgumentException::class],
            'method override' => ['attributes', ['formmethod' => 'get'], InvalidArgumentException::class],
            'encoding override' => ['attributes', ['formenctype' => 'text/plain'], InvalidArgumentException::class],
            'validation override' => ['attributes', ['formnovalidate' => true], InvalidArgumentException::class],
            'marker override' => [
                'attributes',
                ['data-form-compose-action' => 'other'],
                InvalidArgumentException::class,
            ],
            'template map type' => ['replaceTemplates', false, TypeError::class],
            'template data type' => ['templateData', 'wrong', TypeError::class],
            'block empty' => ['block', '', InvalidArgumentException::class],
            'block whitespace' => ['block', '  ', InvalidArgumentException::class],
            'block type' => ['block', [], TypeError::class],
        ];
    }

    public function testInvalidTriggerKindsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PostAction('image', '/go', new ActionConfig());
    }
}
