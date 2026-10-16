<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use Cake\Form\FormProtector;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks isolated action tokens, locked payloads, and optional submit names with Cake's protector.
 */
class PostActionSecurityTest extends FormTestCase
{
    public function testActionsDoNotAffectTheParentFormsFieldsOrTarget(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $parent = $helper->bind(null, ['url' => '/parent/save']);
        $parentHtml = $parent->open() . $parent->control('title', ['value' => 'Title']);
        $button = $helper->postButton('Delete', '/records/delete', [
            'method' => 'delete',
            'data' => ['record' => ['id' => 42], 'version' => 0, 'records' => [2 => ['id' => 12]]],
            'block' => 'actions',
            'name' => 'intent',
            'value' => 'remove',
        ]);
        $parentHtml .= $parent->close();
        $actionHtml = $helper->getView()->fetch('actions');
        $session = $helper->getView()->getRequest()->getSession()->id();
        $parentData = ['title' => 'Changed', '_Token' => $this->tokens($parentHtml)];
        $actionData = [
            'record' => ['id' => '42'],
            'version' => '0',
            'records' => [2 => ['id' => '12']],
            '_Token' => $this->tokens($actionHtml),
        ];
        static::assertStringContainsString('name="intent"', $button);
        static::assertTrue(new FormProtector()->validate($parentData, '/parent/save', $session));
        static::assertTrue(new FormProtector()->validate($actionData, '/records/delete', $session));
        static::assertTrue(new FormProtector()->validate(
            $actionData + ['intent' => 'remove'],
            '/records/delete',
            $session,
        ));
        $actionData['record']['id'] = '99';
        static::assertFalse(new FormProtector()->validate($actionData, '/records/delete', $session));
        static::assertFalse(new FormProtector()->validate($parentData, '/records/delete', $session));
    }

    /** @param array<string, mixed> $options Conflicting button identity and payload. */
    #[DataProvider('conflictingNames')]
    public function testNamedButtonsCannotUnlockPayloadsOrOverrideInfrastructure(array $options): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->postButton('Delete', '/delete', $options);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function conflictingNames(): array
    {
        return [
            'exact payload' => [['data' => ['version' => 1], 'name' => 'version']],
            'nested leaf' => [['data' => ['record' => ['id' => 42]], 'name' => 'id']],
            'nested path' => [['data' => ['record' => ['id' => 42]], 'name' => 'record[id]']],
            'method field' => [['name' => '_method']],
            'CSRF field' => [['name' => '_csrfToken']],
            'token field' => [['name' => '_Token[fields]']],
        ];
    }

    /**
     * Extract only the protector's fields from the rendered form.
     *
     * @return array<string, string>
     */
    protected function tokens(string $html): array
    {
        $tokens = [];
        /** @var NodeList<Element>|false $nodes */
        $nodes = $this->xpath($html)->query('//input[starts-with(@name, "_Token[")]');
        static::assertInstanceOf(NodeList::class, $nodes);
        foreach ($nodes as $node) {
            $name = $node->getAttribute('name');
            $value = $node->getAttribute('value');
            static::assertNotNull($name);
            static::assertNotNull($value);
            $tokens[substr($name, 7, -1)] = $value;
        }

        return $tokens;
    }
}
