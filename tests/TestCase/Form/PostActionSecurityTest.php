<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use Cake\Form\FormProtector;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Configuration\ActionConfig;
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
        $parent = $helper->form()->url('/parent/save')->bind();
        $parentHtml =
            $parent->open()
            . $parent->control('title')->value('Title')->render();
        $button = $helper->postButton(label: 'Delete', url: '/records/delete')
            ->method('delete')
            ->data(['record' => ['id' => 42], 'version' => 0, 'records' => [2 => ['id' => 12]]])
            ->block('actions')
            ->attributes(['name' => 'intent', 'value' => 'remove'])
            ->render();
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

    /** Conflicting button identity and payload are rejected before rendering. */
    #[DataProvider('conflictingNames')]
    public function testNamedButtonsCannotUnlockPayloadsOrOverrideInfrastructure(ActionConfig $options): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->postButton(label: 'Delete', url: '/delete')->apply($options)->render();
    }

    /** @return array<string, array{\FormCompose\Configuration\ActionConfig}> */
    public static function conflictingNames(): array
    {
        return [
            'exact payload' => [ActionConfig::builder()
                ->data(['version' => 1])
                ->attributes(['name' => 'version'])
                ->config()],
            'nested leaf' => [ActionConfig::builder()
                ->data(['record' => ['id' => 42]])
                ->attributes(['name' => 'id'])
                ->config()],
            'nested path' => [ActionConfig::builder()
                ->data(['record' => ['id' => 42]])
                ->attributes(['name' => 'record[id]'])
                ->config()],
            'method field' => [ActionConfig::builder()->attributes(['name' => '_method'])->config()],
            'CSRF field' => [ActionConfig::builder()->attributes(['name' => '_csrfToken'])->config()],
            'token field' => [ActionConfig::builder()->attributes(['name' => '_Token[fields]'])->config()],
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
