<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use Cake\Form\FormProtector;
use Cake\View\Form\ArrayContext;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Test\Fixture\Authoring\TitleIdForm;
use FormCompose\Test\FormTestCase;

/**
 * Verifies summary rendering contributes no submitted inputs or security expectations.
 */
class ErrorSummarySecurityTest extends FormTestCase
{
    public function testSummaryAndOrdinaryRenderingAcceptTheSamePayload(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $context = new ArrayContext([
            'defaults' => ['id' => 42, 'title' => 'Value'],
            'errors' => ['title' => ['invalid' => 'Fix title']],
        ]);
        foreach ([false, true] as $summary) {
            $form = $helper->bind($context, ['url' => '/articles/save'], definition: TitleIdForm::class);
            $html = $form->render(options: ['errorSummary' => $summary]);
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

            $payload = ['id' => '42', 'title' => 'Updated', '_Token' => $tokens];
            $session = $helper->getView()->getRequest()->getSession()->id();
            static::assertTrue(new FormProtector()->validate($payload, '/articles/save', $session));
            $payload['id'] = '99';
            static::assertFalse(new FormProtector()->validate($payload, '/articles/save', $session));
        }
    }
}
