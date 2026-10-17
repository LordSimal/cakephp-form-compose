<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use Cake\Form\FormProtector;
use Cake\View\Form\ArrayContext;
use Dom\Element;
use Dom\NodeList;
use FormCompose\BlueprintView;
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
        $form = $helper->bind(null, ['url' => '/articles/save', 'method' => 'patch'], blueprint: PostForm::class);
        static::assertNotNull($form->binding->definition);
        $view = new BlueprintView($form, $form->binding->definition);
        static::assertTrue($view->has('author'));
        static::assertFalse($view->has('author.email'));
        $view->field('author.last_name')->attributes();
        $html = $form->open() . $form->blueprint() . $form->close();
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
        $form = $helper->bind(null, ['url' => '/articles/save']);
        $html = $form->open() . $form->control('existing');
        try {
            $form->blueprint(PostForm::class, options: ['template' => 'composition/failure']);
            static::fail('Expected the enclosing layout to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Definition layout failed.', $runtimeException->getMessage());
        }

        $html .= $form->control('title') . $form->close();
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
        $form = $helper->bind(blueprint: PostForm::class);
        $html = $form->open();
        try {
            $form->blueprint(options: ['template' => 'composition/failure']);
            static::fail('Expected the enclosing layout to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Definition layout failed.', $runtimeException->getMessage());
        }

        $html .= $form->blueprint() . $form->close();
        static::assertSame(1, substr_count($html, 'name="title"'));
        static::assertSame(1, substr_count($html, 'name="author[first_name]"'));
        static::assertSame(['author', 'post'], array_column($this->probe->calls, 'name'));
    }

    public function testOverlappingMountsRetainDuplicateInputProtection(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $form->blueprint(PostForm::class);
        $this->expectException(LogicException::class);
        $form->blueprint(PostForm::class);
    }

    public function testMountedNativeInputsKeepFallbacksFilesAndDisabledProtection(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(
            new ArrayContext([
                'data' => ['billing' => ['tags' => ['b'], 'status' => 'a', 'active' => false]],
            ]),
            ['type' => 'file', 'url' => '/articles/save'],
            blueprint: BillingInputsForm::class,
        );
        $html = $form->open() . $form->blueprint() . $form->close();
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
        $form = $this->helper()->bind(blueprint: BillingInputsForm::class);
        $form->open();
        try {
            $form->blueprint();
            static::fail('Expected file inputs to require multipart.');
        } catch (LogicException $logicException) {
            static::assertSame('File inputs require a multipart form.', $logicException->getMessage());
        }

        static::assertStringContainsString('name="billing[tags][]"', $form->control('billing.tags'));
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
