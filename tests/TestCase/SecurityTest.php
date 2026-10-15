<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\Form\FormProtector;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Test\Fixture\FailOnceRenderer;
use FormCompose\Test\Fixture\RangeType;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use RuntimeException;

class SecurityTest extends FormTestCase
{
    public function testDefinitionsOnlySecureRenderedFieldsAndPreserveLockedValues(): void
    {
        $contact = FormDefinition::make()
            ->add(FieldDefinition::make('email', 'email'))
            ->add(FieldDefinition::make('unused'));
        $fields = FormDefinition::make()
            ->add(FieldDefinition::make('id', 'hidden')->with(['value' => '42']))
            ->add(FieldDefinition::make('active', 'checkbox'))
            ->add(FieldDefinition::make('comments.0.body'));
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(null, ['url' => '/articles/save']);
        $form->field($contact->field('unused'))->attributes();
        $email = $form->field($contact->field('email'));
        $html = $form->open() . $form->control('title');
        $html .= $email->label() . $email->input();
        $html .= $form->definition($fields) . $form->close();
        $data = [
            'title' => 'Edited',
            'email' => 'user@example.test',
            'id' => '42',
            'active' => '0',
            'comments' => [['body' => 'Changed']],
            '_Token' => $this->tokens($html),
        ];
        $protector = new FormProtector();
        $sessionId = $helper->getView()->getRequest()->getSession()->id();

        static::assertTrue($protector->validate($data, '/articles/save', $sessionId));
        static::assertFalse($protector->validate(array_replace($data, ['id' => '43']), '/articles/save', $sessionId));
        static::assertFalse($protector->validate($data + ['unused' => 'injected'], '/articles/save', $sessionId));
    }

    /**
     * Extract form-protection token values from rendered hidden inputs.
     *
     * @return array<string, string>
     */
    private function tokens(string $html): array
    {
        /** @var NodeList<Element>|false $nodes */
        $nodes = $this->xpath($html)->query('//input[starts-with(@name, "_Token[")]');
        $tokens = [];
        static::assertInstanceOf(NodeList::class, $nodes);
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

    public function testInspectionDoesNotSecureFieldsAndHiddenValuesAreLocked(): void
    {
        $csrf = bin2hex(random_bytes(16));
        $helper = $this->helper(attributes: ['formTokenData' => [], 'csrfToken' => $csrf]);
        $form = $helper->bind(null, ['url' => '/articles/save']);
        $unused = $form->field('never_rendered');
        $unused->attributes();
        $html = $form->open();
        static::assertStringContainsString('value="' . $csrf . '"', $html);
        $html .= $form->control('title', ['template' => 'themes/custom/control']);
        $html .= $form->control('id', ['type' => 'hidden', 'value' => '42']);
        $html .= $form->control('disabled', ['disabled' => true]);
        $html .= $form->close();
        $tokens = $this->tokens($html);
        $data = ['title' => 'Edited', 'id' => '42', '_Token' => $tokens];
        $protector = new FormProtector();
        $sessionId = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue($protector->validate($data, '/articles/save', $sessionId));
        static::assertFalse($protector->validate(array_replace($data, ['id' => '43']), '/articles/save', $sessionId));
        static::assertFalse($protector->validate($data + ['unexpected' => 'x'], '/articles/save', $sessionId));
    }

    public function testManualAndCompositeInputsShareSubmissionProtection(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(null, ['url' => '/articles/save', 'types' => ['range' => new RangeType()]]);
        $html = $form->open();
        $manual = $form->field('manual');
        $html .= '<input' . (string)$manual->attributes() . '>';
        $manual->register();
        $html .= $form->control('period', ['type' => 'range']);
        $html .= $form->close();
        $data = ['manual' => 'text', 'period' => ['start' => '2', 'end' => '9'], '_Token' => $this->tokens($html)];
        $protector = new FormProtector();
        $id = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue($protector->validate($data, '/articles/save', $id));
        unset($data['period']['end']);
        static::assertFalse($protector->validate($data, '/articles/save', $id));
    }

    public function testChoiceAndNestedFieldsValidateWithBrowserPayloads(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(null, ['url' => '/articles/save']);
        $html = $form->open();
        $html .= $form->control('active', ['type' => 'checkbox']);
        $html .= $form->control('comments.0.body');
        $html .= $form->control('tags', ['multiple' => true, 'options' => ['a' => 'A']]);
        $html .= $form->control('dynamic', ['secure' => false]);
        $html .= $form->close();
        $data = [
            'active' => '0',
            'comments' => [['body' => 'Changed']],
            'tags' => ['a'],
            'dynamic' => 'changed',
            '_Token' => $this->tokens($html),
        ];
        static::assertTrue(new FormProtector()->validate(
            $data,
            '/articles/save',
            $helper->getView()->getRequest()->getSession()->id(),
        ));
    }

    public function testNamedSubmitButtonsAreOptional(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(null, ['url' => '/articles/save']);
        $html = $form->open() . $form->control('title');
        $html .= $form->submit('Save', ['name' => 'intent', 'value' => 'save']);
        $html .= $form->submit('Preview', ['name' => 'intent', 'value' => 'preview']);
        $html .= $form->close();
        $data = ['title' => 'Edited', 'intent' => 'preview', '_Token' => $this->tokens($html)];
        $protector = new FormProtector();
        $sessionId = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue($protector->validate($data, '/articles/save', $sessionId));
        unset($data['intent']);
        static::assertTrue($protector->validate($data, '/articles/save', $sessionId));
    }

    public function testFailedButtonTemplateDoesNotUnlockAnUnrenderedName(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $renderer = new FailOnceRenderer(new PhpRenderer($helper->getView()), 'button', 'control');
        $form = $helper->bind(null, ['url' => '/articles/save'], renderer: $renderer);
        $html = $form->open() . $form->control('title');
        try {
            $form->submit('Save', ['name' => 'intent']);
            static::fail('Expected the button template to fail.');
        } catch (RuntimeException $exception) {
            static::assertSame('Template failed', $exception->getMessage());
        }
        $html .= $form->close();
        $data = ['title' => 'Edited', '_Token' => $this->tokens($html)];
        $protector = new FormProtector();
        $sessionId = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue($protector->validate($data, '/articles/save', $sessionId));
        static::assertFalse($protector->validate($data + ['intent' => 'injected'], '/articles/save', $sessionId));
    }

    public function testFailedProtectedClosingCanBeRetriedWithValidTokens(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $renderer = new FailOnceRenderer(new PhpRenderer($helper->getView()), 'form', 'close');
        $form = $helper->bind(null, ['url' => '/articles/save'], renderer: $renderer);
        $html = $form->open() . $form->control('id', ['type' => 'hidden', 'value' => '7']);
        try {
            $form->close();
            static::fail('Expected the closing template to fail.');
        } catch (RuntimeException $exception) {
            static::assertSame('Template failed', $exception->getMessage());
        }
        $html .= $form->close();
        $data = ['id' => '7', '_Token' => $this->tokens($html)];
        static::assertTrue(new FormProtector()->validate(
            $data,
            '/articles/save',
            $helper->getView()->getRequest()->getSession()->id(),
        ));
    }

    public function testTemplateDataCannotReplacePreparedVariablesOrSubmissionIdentity(): void
    {
        $data = [
            'field' => 'fake',
            'form' => 'fake',
            'state' => 'fake',
            'parent' => 'fake',
            'hidden' => [],
            'attributes' => ['name' => 'unexpected'],
            'name' => 'unexpected',
            'value' => '99',
        ];
        $helper = $this->helper(attributes: ['formTokenData' => [], 'csrfToken' => bin2hex(random_bytes(16))]);
        $form = $helper->bind(null, ['url' => '/articles/save', 'templateData' => $data]);
        $html = $form->open();
        $html .= $form->control('title', ['template' => 'themes/data/control', 'templateData' => $data]);
        $html .= $form->control('id', ['type' => 'hidden', 'value' => '42', 'templateData' => $data]);
        $html .= $form->submit('Save', [
            'name' => 'intent',
            'value' => 'save',
            'templates' => ['control' => 'themes/data/part'],
            'templateData' => $data,
        ]);
        $html .= $form->close();
        $submitted = ['title' => 'Edited', 'id' => '42', 'intent' => 'save', '_Token' => $this->tokens($html)];
        $session = $helper->getView()->getRequest()->getSession()->id();
        $protector = new FormProtector();

        static::assertTrue($protector->validate($submitted, '/articles/save', $session));
        static::assertFalse($protector->validate(
            array_replace($submitted, ['id' => '99']),
            '/articles/save',
            $session,
        ));
        static::assertStringNotContainsString('name="unexpected"', $html);
        static::assertStringContainsString('name="_csrfToken"', $html);
    }

    public function testInvalidButtonDataDoesNotUnlockAnUnrenderedName(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(null, ['url' => '/articles/save']);
        $html = $form->open() . $form->control('title');
        try {
            $form->submit('Save', ['name' => 'intent', 'templateData' => null]);
            static::fail('Expected invalid template metadata to fail.');
        } catch (InvalidArgumentException $exception) {
            static::assertSame('The templateData option must be an array.', $exception->getMessage());
        }
        $html .= $form->close();
        $submitted = ['title' => 'Edited', '_Token' => $this->tokens($html)];
        $protector = new FormProtector();
        $session = $helper->getView()->getRequest()->getSession()->id();

        static::assertTrue($protector->validate($submitted, '/articles/save', $session));
        static::assertFalse($protector->validate($submitted + ['intent' => 'injected'], '/articles/save', $session));
    }
}
