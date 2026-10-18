<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use Cake\Form\FormProtector;
use Cake\View\Form\ArrayContext;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Blueprint\BlueprintView;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Authoring\BillingInputsForm;
use FormCompose\Test\Fixture\Composition\PostForm;
use LogicException;
use RuntimeException;

/**
 * Verifies one protection lifecycle across nested layouts, omissions, and failures.
 */
class BlueprintSecurityTest extends CompositionTestCase
{
    public function testNestedLayoutsProtectOnlyRenderedInputsAndLockedValues(): void
    {
        $csrf = bin2hex(random_bytes(16));
        $helper = $this->helper(attributes: ['formTokenData' => [], 'csrfToken' => $csrf, 'stripParentEmail' => true]);
        $form = $helper->form()
            ->url('/articles/save')
            ->method('patch')
            ->blueprint(PostForm::class)
            ->bind();
        static::assertNotNull($form->binding->definition);
        $view = new BlueprintView($form, $form->binding->definition);
        static::assertTrue($view->has('author'));
        static::assertFalse($view->has('author.email'));
        $view->field('author.last_name')->resolve()->attributes();
        $html = $form->open() . $form->blueprint()->render() . $form->close();
        $data = [
            'title' => 'Title',
            'author' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'id' => '7'],
            'body' => 'Body',
            '_Token' => $this->tokens($html),
        ];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($data, '/articles/save', $session));
        $extra = $data;
        $extra['author']['email'] = 'injected@example.test';
        static::assertFalse(new FormProtector()->validate($extra, '/articles/save', $session));
        $extra = $data;
        $extra['author']['internal'] = 'injected';
        static::assertFalse(new FormProtector()->validate($extra, '/articles/save', $session));
        $changed = $data;
        $changed['author']['id'] = '8';
        static::assertFalse(new FormProtector()->validate($changed, '/articles/save', $session));
        static::assertSame(1, substr_count($html, 'name="_csrfToken"'));
        static::assertStringContainsString('value="' . $csrf . '"', $html);
        static::assertStringContainsString('name="_method" value="PATCH"', $html);
    }

    public function testFailedParentLayoutRollsBackSuccessfulChildRegistrations(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->form()->url('/articles/save')->bind();
        $html = $form->open() . $form->control('existing')->render();
        try {
            $form->blueprint(blueprint: PostForm::class)
                ->partTemplate('control', 'composition/failure')
                ->render();
            static::fail('Expected the enclosing layout to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Definition layout failed.', $runtimeException->getMessage());
        }

        $html .= $form->control('title')->render() . $form->close();
        $data = ['existing' => 'Existing', 'title' => 'Title', '_Token' => $this->tokens($html)];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($data, '/articles/save', $session));
        static::assertFalse(new FormProtector()->validate(
            $data + ['author' => ['id' => '7']],
            '/articles/save',
            $session,
        ));
        static::assertNull($form->binding->definition);
    }

    public function testFailedLayoutCanBeRetriedUsingTheSameCompiledDefinition(): void
    {
        $helper = $this->helper();
        $form = $helper->form()->blueprint(PostForm::class)->bind();
        $html = $form->open();
        try {
            $form->blueprint()
                ->partTemplate('control', 'composition/failure')
                ->render();
            static::fail('Expected the enclosing layout to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Definition layout failed.', $runtimeException->getMessage());
        }

        $html .= $form->blueprint()->render() . $form->close();
        static::assertSame(1, substr_count($html, 'name="title"'));
        static::assertSame(1, substr_count($html, 'name="author[first_name]"'));
        static::assertSame(['post', 'author'], array_column($this->probe->calls, 'name'));
    }

    public function testOverlappingMountsRetainDuplicateInputProtection(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $form->blueprint(blueprint: PostForm::class)->render();
        $this->expectException(LogicException::class);
        $form->blueprint(blueprint: PostForm::class)->render();
    }

    public function testMountedNativeInputsKeepFallbacksFilesAndDisabledProtection(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->form(new ArrayContext([
            'data' => ['billing' => ['tags' => ['b'], 'status' => 'a', 'active' => false]],
        ]))
            ->multipart()
            ->url('/articles/save')
            ->blueprint(BillingInputsForm::class)
            ->bind();
        $html = $form->open() . $form->blueprint()->render() . $form->close();
        $xpath = $this->xpath($html);
        static::assertTrue($xpath->evaluate('boolean(//form[@enctype="multipart/form-data"])'));
        static::assertTrue($xpath->evaluate(
            'boolean(//select[@name="billing[tags][]"][@multiple]/option[@value="b"][@selected])',
        ));
        static::assertTrue($xpath->evaluate(
            'boolean(//input[@type="radio"][@name="billing[status]"][@value="a"][@checked])',
        ));
        static::assertTrue($xpath->evaluate(
            'boolean(//input[@type="file"][@name="billing[attachment]"][not(@value)])',
        ));
        static::assertTrue($xpath->evaluate('boolean(//input[@type="hidden"][@name="billing[active]"][@value="0"])'));
        static::assertStringNotContainsString('/private/path.txt', $html);
        $data = [
            'billing' => ['tags' => ['b'], 'status' => 'a', 'attachment' => '', 'active' => '0'],
            '_Token' => $this->tokens($html),
        ];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($data, '/articles/save', $session));
        $data['billing']['disabled'] = 'injected';
        static::assertFalse(new FormProtector()->validate($data, '/articles/save', $session));
    }

    public function testMultipartFailureRollsBackEarlierControlsInTheDefinition(): void
    {
        $form = $this->helper()->form()->blueprint(BillingInputsForm::class)->bind();
        $form->open();
        try {
            $form->blueprint()->render();
            static::fail('Expected file inputs to require multipart.');
        } catch (LogicException $logicException) {
            static::assertSame('File inputs require a multipart form.', $logicException->getMessage());
        }

        static::assertStringContainsString('name="billing[tags][]"', $form->control('billing.tags')->render());
        $form->close();
    }

    /**
     * Extract real Cake protection fields for submission validation.
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
