<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\Form\FormProtector;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Test\FormTestCase;
use LogicException;

/**
 * Verifies late presentation failures cannot leak input names into Cake protection.
 */
class ControlTransactionTest extends FormTestCase
{
    public function testAFeedbackCollisionRollsBackPendingSubmissions(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->form()->url('/articles/save')->bind();
        $html = $form->open() . $form->control('existing')->id('hint')->render();
        try {
            $form->control('rejected')
                ->help('Hint')
                ->partAttributes('help', ['id' => 'hint'])
                ->render();
            static::fail('Expected a collision after input rendering.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('already been rendered', $logicException->getMessage());
        }

        $html .= $form->close();
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

        static::assertTrue(new FormProtector()->validate(
            ['existing' => 'valid', '_Token' => $tokens],
            '/articles/save',
            $helper->getView()->getRequest()->getSession()->id(),
        ));
    }
}
