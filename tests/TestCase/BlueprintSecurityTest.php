<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\Container\Container;
use Cake\Form\FormProtector;
use Cake\View\Form\ArrayContext;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Test\BlueprintTestCase;
use FormCompose\Test\Fixture\Blueprint\Invalid\FailingForm;
use FormCompose\Test\Fixture\Blueprint\Valid\BillingAddressForm;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileForm;
use RuntimeException;

/**
 * Ensures declarations and discovery preserve the rendered-input security contract.
 */
class BlueprintSecurityTest extends BlueprintTestCase
{
    public function testConditionalFieldsAndDiscoveredCompositeInputsRemainProtected(): void
    {
        $factory = new BlueprintFactory(new Container(), 'testBlueprints');
        $csrf = bin2hex(random_bytes(16));
        $helper = $this->helper(attributes: ['formTokenData' => [], 'csrfToken' => $csrf]);
        $helper->setConfig('blueprintFactory', $factory);

        $form = $helper->bind(
            new ArrayContext(['data' => ['label' => 'Email']]),
            ['url' => '/articles/save'],
            definition: 'conditional',
        );
        $form->field('kind')->attributes();
        $html = $form->open();
        $html .= $form->control('email') . $form->control('id');
        $html .= $form->control('period', ['type' => 'date-range']);
        $html .= $form->close();
        $data = [
            'email' => 'user@example.test',
            'id' => '42',
            'period' => ['start' => '2', 'end' => '9'],
            '_Token' => $this->tokens($html),
        ];
        $session = $helper->getView()->getRequest()->getSession()->id();
        $protector = new FormProtector();
        static::assertStringContainsString('name="_csrfToken" value="' . $csrf . '"', $html);
        static::assertTrue($protector->validate($data, '/articles/save', $session));
        static::assertFalse($protector->validate(array_replace($data, ['id' => '43']), '/articles/save', $session));
        static::assertFalse($protector->validate($data + ['kind' => 'work'], '/articles/save', $session));
        static::assertFalse($protector->validate($data + ['internal_note' => 'injected'], '/articles/save', $session));
        static::assertFalse($protector->validate($data + ['active' => '1'], '/articles/save', $session));
        unset($data['period']['end']);
        static::assertFalse($protector->validate($data, '/articles/save', $session));
    }

    public function testRuntimeAddedFieldsRenderAndRetainSubmissionProtection(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => [], 'canManageAccounts' => true]);
        $helper->setConfig('blueprintFactory', new BlueprintFactory(new Container(), 'testBlueprints'));

        $form = $helper->bind(
            new ArrayContext(['data' => ['label' => 'Work <email>']]),
            ['url' => '/articles/save'],
            definition: 'conditional',
        );
        $html = $form->render();
        static::assertStringContainsString('Work &lt;email&gt;', $html);
        static::assertStringContainsString('name="kind"', $html);
        static::assertStringContainsString('type="hidden" name="active" value="0"', $html);
        static::assertStringContainsString('Account enabled', $html);
        static::assertStringNotContainsString('name="internal_note"', $html);
        $data = [
            'email' => 'user@example.test',
            'id' => '42',
            'kind' => 'work',
            'active' => '0',
            '_Token' => $this->tokens($html),
        ];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($data, '/articles/save', $session));
        static::assertFalse(new FormProtector()->validate(
            $data + ['internal_note' => 'injected'],
            '/articles/save',
            $session,
        ));
        static::assertFalse(new FormProtector()->validate(
            array_replace($data, ['id' => '43']),
            '/articles/save',
            $session,
        ));
    }

    public function testCollectionRenderingProtectsNestedFieldsAndCheckboxFallbacks(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(null, ['url' => '/articles/save'], definition: ProfileForm::class);
        $html = $form->open() . $form->definition() . $form->close();
        $data = [
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'comments' => [['body' => 'Changed']],
            'active' => '0',
            'count' => '0',
            '_Token' => $this->tokens($html),
        ];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($data, '/articles/save', $session));
        static::assertStringContainsString('type="hidden" name="active" value="0"', $html);
        static::assertFalse(new FormProtector()->validate($data + ['extra' => 'injected'], '/articles/save', $session));
    }

    public function testLocalDefinitionsShareProtectionAndKeepRemovedFieldsUnregistered(): void
    {
        $csrf = bin2hex(random_bytes(16));
        $helper = $this->helper(attributes: ['formTokenData' => [], 'csrfToken' => $csrf]);
        $helper->setConfig('blueprintFactory', new BlueprintFactory(new Container(), 'testBlueprints'));

        $form = $helper->bind(
            new ArrayContext(['data' => ['label' => 'Contact email']]),
            ['url' => '/articles/save', 'method' => 'patch'],
        );
        $html = $form->open();
        $html .= $form->definition('conditional');
        $html .= $form->definition(BillingAddressForm::class);
        $html .= $form->control('period', ['type' => 'date-range']);
        static::assertSame('email', $form->field('email')->state->type);
        $html .= $form->close();
        $data = [
            'email' => 'user@example.test',
            'id' => '42',
            'kind' => 'work',
            'billing' => ['street' => 'Main Street', 'city' => 'Brussels'],
            'period' => ['start' => '2', 'end' => '9'],
            '_Token' => $this->tokens($html),
        ];
        $session = $helper->getView()->getRequest()->getSession()->id();
        $protector = new FormProtector();
        static::assertTrue($protector->validate($data, '/articles/save', $session));
        static::assertFalse($protector->validate(array_replace($data, ['id' => '43']), '/articles/save', $session));
        static::assertFalse($protector->validate($data + ['active' => '1'], '/articles/save', $session));
        static::assertFalse($protector->validate($data + ['internal_note' => 'injected'], '/articles/save', $session));
        unset($data['billing']['city']);
        static::assertFalse($protector->validate($data, '/articles/save', $session));
        static::assertSame(1, substr_count($html, 'name="_csrfToken"'));
        static::assertStringContainsString('name="_method" value="PATCH"', $html);
        static::assertStringContainsString('value="' . $csrf . '"', $html);
    }

    public function testFailedLocalHooksDoNotRegisterPartialConfiguration(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(null, ['url' => '/articles/save']);
        $html = $form->open();
        try {
            $form->definition(FailingForm::class);
            static::fail('Expected configuration failure.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Configuration failed.', $runtimeException->getMessage());
        }

        $html .= $form->control('title') . $form->close();
        $data = ['title' => 'Edited', '_Token' => $this->tokens($html)];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($data, '/articles/save', $session));
        static::assertFalse(new FormProtector()->validate(
            $data + ['partial' => 'injected'],
            '/articles/save',
            $session,
        ));
        static::assertNull($form->binding->definition);
    }

    /**
     * Extract the real protection data generated by CakePHP from hidden inputs.
     *
     * @return array<string, string>
     */
    protected function tokens(string $html): array
    {
        /** @var NodeList<Element>|false $nodes */
        $nodes = $this->xpath($html)->query('//input[starts-with(@name, "_Token[")]');
        static::assertInstanceOf(NodeList::class, $nodes);
        $tokens = [];
        foreach ($nodes as $node) {
            static::assertInstanceOf(Element::class, $node);
            $name = $node->getAttribute('name');
            $value = $node->getAttribute('value');
            static::assertNotNull($name);
            static::assertNotNull($value);
            $tokens[substr($name, 7, -1)] = $value;
        }

        return $tokens;
    }
}
