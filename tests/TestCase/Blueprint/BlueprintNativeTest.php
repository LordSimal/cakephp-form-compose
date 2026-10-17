<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\Form\FormProtector;
use Cake\View\Form\ArrayContext;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Test\Fixture\Blueprint\Valid\InputForm;
use FormCompose\Test\FormTestCase;

/**
 * Verifies declared native controls preserve choice, accessibility and submission behavior.
 */
class BlueprintNativeTest extends FormTestCase
{
    public function testChoicesFilesAndDisabledFieldsUseTheOrdinaryNativeContracts(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $context = new ArrayContext([
            'data' => ['tags' => ['b'], 'status' => 'a'],
            'errors' => ['status' => ['invalid' => 'Choose <one>']],
            'required' => ['status' => true],
        ]);
        $form = $helper->bind($context, ['type' => 'file', 'url' => '/articles/save'], blueprint: InputForm::class);
        $html = $form->open() . $form->blueprint() . $form->close();
        $xpath = $this->xpath($html);
        static::assertTrue($xpath->evaluate('boolean(//form[@enctype="multipart/form-data"])'));
        static::assertTrue($xpath->evaluate(
            'boolean(//select[@name="tags[]" and @multiple]/option[@value="b" and @selected])',
        ));
        static::assertTrue($xpath->evaluate('boolean(//input[@type="hidden" and @name="tags"])'));
        static::assertTrue($xpath->evaluate('boolean(//input[@type="radio" and @value="a" and @checked])'));
        static::assertTrue($xpath->evaluate('boolean(//input[@type="file" and @name="attachment" and not(@value)])'));
        static::assertTrue($xpath->evaluate('boolean(//input[@name="disabled" and @disabled])'));
        static::assertStringContainsString('&lt;Alpha&gt;', $html);
        static::assertStringContainsString('&lt;Active&gt;', $html);
        static::assertStringContainsString('Choose &lt;one&gt;', $html);
        static::assertStringContainsString(
            'aria-describedby="form-compose-1-status-help form-compose-1-status-errors"',
            $html,
        );
        static::assertStringContainsString('id="form-compose-1-status-help"', $html);
        static::assertStringContainsString('id="form-compose-1-status-errors"', $html);
        static::assertStringNotContainsString('/private/path.txt', $html);
        /** @var NodeList<Element>|false $nodes */
        $nodes = $xpath->query('//input[starts-with(@name, "_Token[")]');
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

        $submitted = ['tags' => ['b'], 'status' => 'a', 'attachment' => '', '_Token' => $tokens];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($submitted, '/articles/save', $session));
        static::assertFalse(new FormProtector()->validate(
            $submitted + ['disabled' => 'injected'],
            '/articles/save',
            $session,
        ));
    }
}
