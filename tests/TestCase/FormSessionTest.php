<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\Form\FormProtector;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\View\Form\ArrayContext;
use Dom\Element;
use Dom\NodeList;
use FormCompose\FormSession;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\Theme;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Authoring\EmailForm;
use FormCompose\Test\Fixture\Authoring\FailingPostForm;
use FormCompose\Test\Fixture\Authoring\LocalEmailForm;
use FormCompose\Test\Fixture\Authoring\SecureForm;
use FormCompose\Test\Fixture\Composition\PostForm;
use LogicException;
use RuntimeException;

/**
 * Verifies convenient rendering preserves session state, extensions, and real protection tokens.
 */
class FormSessionTest extends CompositionTestCase
{
    public function testBoundDefaultsRenderOnceThroughAnExplicitThemeAndRenderer(): void
    {
        $helper = $this->helper();
        $theme = new Theme(layers: ['themes/unified']);
        $form = $helper->bind(
            new ArrayContext(['data' => ['title' => 'Original']]),
            theme: $theme,
            renderer: new PhpRenderer($helper->getView(), $theme),
            blueprint: PostForm::class,
        );
        static::assertCount(2, $this->probe->calls);
        $html = $form->render(options: ['class' => 'complete'], submit: 'Save', submitOptions: [
            'variant' => 'primary',
        ]);
        static::assertCount(2, $this->probe->calls);
        static::assertStringContainsString('value="Original"', $html);
        static::assertStringContainsString('class="complete"', $html);
        static::assertStringContainsString('data-form-shell', $html);
        static::assertStringContainsString('data-primary', $html);
    }

    public function testAnExplicitDefinitionRendersLocallyWithoutReplacingBindingDefaults(): void
    {
        $form = $this->helper()->bind(blueprint: EmailForm::class);
        $defaults = $form->binding->definition;
        $html = $form->render(LocalEmailForm::class);
        static::assertStringContainsString('Local email', $html);
        static::assertStringContainsString('type="email"', $html);
        static::assertSame($defaults, $form->binding->definition);
        static::assertSame('Default email', $form->field('email')->state->options['label']);
    }

    public function testSessionCanResolveAnExplicitBlueprint(): void
    {
        $html = $this->helper()->bind()->render('composed-post', submit: false);
        static::assertStringContainsString('name="author[first_name]"', $html);
        static::assertSame(['author', 'post'], array_column($this->probe->calls, 'name'));
    }

    public function testDefaultSubmitIsTranslatedWhenRenderingRatherThanBinding(): void
    {
        $locale = I18n::getLocale();
        $form = $this->helper()->bind();
        try {
            I18n::setTranslator(
                'default',
                static function (): Package {
                    $package = new Package();
                    $package->setMessages(['Submit' => 'Versturen']);

                    return $package;
                },
                'nl_BE',
            );
            I18n::setLocale('nl_BE');
            static::assertStringContainsString('>Versturen</button>', $form->render());
        } finally {
            I18n::clear();
            I18n::setLocale($locale);
        }
    }

    public function testEmptyFormsAndExplicitButtonLabelsRemainSupported(): void
    {
        $helper = $this->helper();
        $html = $helper->bind()->render(submit: false);
        static::assertStringContainsString('<form ', $html);
        static::assertStringContainsString('</form>', $html);
        static::assertStringNotContainsString('<button', $html);
        static::assertStringContainsString('>Save &lt;all&gt;</button>', $helper->bind()->render(submit: 'Save <all>'));
        static::assertStringContainsString(
            '<strong>Save</strong>',
            $helper->bind()->render(submit: new Html('<strong>Save</strong>')),
        );
        static::assertStringContainsString('></button>', $helper->bind()->render(submit: ''));
    }

    public function testCompleteFormsPreserveMultipartCsrfMethodAndSubmissionProtection(): void
    {
        $csrf = bin2hex(random_bytes(16));
        $helper = $this->helper(attributes: ['csrfToken' => $csrf, 'formTokenData' => []]);
        $html = $helper->render(
            SecureForm::class,
            new ArrayContext(['data' => ['inputs' => ['tags' => ['b'], 'status' => 'a']]]),
            ['type' => 'file', 'method' => 'patch', 'url' => '/articles/save'],
            submitOptions: ['name' => 'save', 'value' => 'yes'],
        );
        $xpath = $this->xpath($html);
        static::assertTrue($xpath->evaluate('boolean(//form[@enctype="multipart/form-data"])'));
        static::assertTrue($xpath->evaluate('boolean(//input[@name="_method"][@value="PATCH"])'));
        static::assertTrue($xpath->evaluate('boolean(//input[@name="_csrfToken"][@value="' . $csrf . '"])'));
        static::assertTrue($xpath->evaluate('boolean(//input[@type="file"][@name="inputs[attachment]"])'));
        static::assertTrue($xpath->evaluate('boolean(//select[@name="inputs[tags][]"]/option[@value="b"][@selected])'));
        $data = [
            'author' => ['id' => '7'],
            'inputs' => ['tags' => ['b'], 'status' => 'a', 'attachment' => ''],
            '_Token' => $this->tokens($html),
        ];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($data, '/articles/save', $session));
        static::assertTrue(new FormProtector()->validate($data + ['save' => 'yes'], '/articles/save', $session));
        $data['author']['id'] = '8';
        static::assertFalse(new FormProtector()->validate($data, '/articles/save', $session));
        $data['author']['id'] = '7';
        $data['inputs']['disabled'] = 'injected';
        static::assertFalse(new FormProtector()->validate($data, '/articles/save', $session));
        static::assertSame(1, substr_count($html, 'name="_Token[fields]"'));
    }

    public function testCompleteRenderingRejectsOpenAndPreviouslyRenderedSessions(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $this->assertCannotRenderAgain($form);
        $form->close();
        $this->assertCannotRenderAgain($form);
        $complete = $this->helper()->bind();
        $complete->render();
        $this->assertCannotRenderAgain($complete);
    }

    public function testFailedCompleteRenderingDoesNotEchoOrAutomaticallyClose(): void
    {
        $helper = $this->helper();
        $form = $helper->bind(blueprint: FailingPostForm::class);
        ob_start();
        try {
            $form->render();
            static::fail('Expected the layout to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Definition layout failed.', $runtimeException->getMessage());
            static::assertSame('', ob_get_contents());
        } finally {
            ob_end_clean();
        }

        static::assertStringContainsString('name="title"', $form->control('title'));
        $form->close();
        static::assertStringContainsString('</form>', $helper->render(PostForm::class));
    }

    /**
     * Verify the fresh-session requirement without changing the original lifecycle.
     */
    protected function assertCannotRenderAgain(FormSession $form): void
    {
        try {
            $form->render();
            static::fail('Expected opening a session twice to fail.');
        } catch (LogicException $logicException) {
            static::assertSame('A form session can only be opened once.', $logicException->getMessage());
        }
    }

    /**
     * Extract real Cake tokens for validating successful and tampered submissions.
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
            $name = $node->getAttribute('name');
            $value = $node->getAttribute('value');
            static::assertNotNull($name);
            static::assertNotNull($value);
            $tokens[substr($name, 7, -1)] = $value;
        }

        return $tokens;
    }
}
