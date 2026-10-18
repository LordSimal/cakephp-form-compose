<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use Cake\Form\FormProtector;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Test\Fixture\FailOnceRenderer;
use FormCompose\Test\Fixture\RangeType;
use FormCompose\Test\FormTestCase;
use RuntimeException;
use TypeError;

class SecurityTest extends FormTestCase
{
    public function testDefinitionsOnlySecureRenderedFieldsAndPreserveLockedValues(): void
    {
        $contact = new FormDefinition([])->add(
            new FieldDefinition('email', FieldConfig::builder()->type('email')),
        )
            ->add(new FieldDefinition('unused'));
        $fields = new FormDefinition([])->add(
            new FieldDefinition(
                'id',
                FieldConfig::builder()->type('hidden')->value('42'),
            ),
        )
            ->add(new FieldDefinition('active', FieldConfig::builder()->type('checkbox')))
            ->add(new FieldDefinition('comments.0.body'));
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(config: new FormConfig(url: '/articles/save'));
        $form->field($contact->field('unused')->path)
            ->apply($contact->field('unused')->config)
            ->resolve()
            ->attributes();
        $email = $form->field($contact->field('email')->path)->apply($contact->field('email')->config)->resolve();
        $html = $form->open() . $form->control('title')->render();
        $html .= $email->label()->render() . $email->input()->render();
        $html .= $form->renderDefinition($fields) . $form->close();
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
        $form = $helper->bind(config: new FormConfig(url: '/articles/save'));
        $unused = $form->field('never_rendered')->resolve();
        $unused->attributes();

        $html = $form->open();
        static::assertStringContainsString('value="' . $csrf . '"', $html);
        $html .= $form->control('title')->partTemplate('control', 'themes/custom/control')->render();
        $html .= $form->control('id')->type('hidden')->value('42')->render();
        $html .= $form->control('disabled')->disabled(true)->render();
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
        $form = $helper->form()
            ->url('/articles/save')
            ->types(['range' => new RangeType()])
            ->bind();
        $html = $form->open();
        $manual = $form->field('manual')->resolve();
        $html .= '<input' . (string)$manual->attributes() . '>';
        $manual->register();
        $html .= $form->control('period')->type('range')->render();
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
        $form = $helper->form()->url('/articles/save')->bind();
        $html = $form->open();
        $html .= $form->control('active')->type('checkbox')->render();
        $html .= $form->control('comments.0.body')->render();
        $html .= $form->control('tags')
            ->multiple(true)
            ->choices(['a' => 'A'])
            ->render();
        $html .= $form->control('dynamic')->secure(false)->render();
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
        $form = $helper->form()->url('/articles/save')->bind();
        $html = $form->open() . $form->control('title')->render();
        $html .= $form->submit('Save')->name('intent')->value('save')->render();
        $html .= $form->submit('Preview')->name('intent')->value('preview')->render();
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
        $form = $helper->form()
            ->url('/articles/save')
            ->renderer($renderer)
            ->bind();
        $html = $form->open() . $form->control('title')->render();
        try {
            $form->submit('Save')->name('intent')->render();
            static::fail('Expected the button template to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Template failed', $runtimeException->getMessage());
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
        $form = $helper->form()
            ->url('/articles/save')
            ->renderer($renderer)
            ->bind();
        $html =
            $form->open()
            . $form->control('id')->type('hidden')->value('7')->render();
        try {
            $form->close();
            static::fail('Expected the closing template to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Template failed', $runtimeException->getMessage());
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
        $form = $helper->form()->url('/articles/save')->templateData($data)->bind();
        $html = $form->open();
        $html .= $form->control('title')->partTemplate('control', 'themes/data/control')->templateData($data)->render();
        $html .= $form->control('id')
            ->type('hidden')
            ->value('42')
            ->templateData($data)
            ->render();
        $html .= $form->submit('Save')
            ->name('intent')
            ->value('save')
            ->partTemplate('control', 'themes/data/part')
            ->templateData($data)
            ->render();
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
        $form = $helper->form()->url('/articles/save')->bind();
        $html = $form->open() . $form->control('title')->render();
        $this->expectException(TypeError::class);
        try {
            $this->invokeBuilder($form->submit('Save')->name('intent'), 'templateData', null);
        } finally {
            $html .= $form->close();
            $submitted = ['title' => 'Edited', '_Token' => $this->tokens($html)];
            $protector = new FormProtector();
            $session = $helper->getView()->getRequest()->getSession()->id();
            static::assertTrue($protector->validate($submitted, '/articles/save', $session));
            static::assertFalse($protector->validate(
                $submitted + ['intent' => 'injected'],
                '/articles/save',
                $session,
            ));
        }
    }
}
