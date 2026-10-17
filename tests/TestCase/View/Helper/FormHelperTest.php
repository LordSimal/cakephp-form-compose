<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\View\Helper;

use Cake\View\Form\ArrayContext;
use FormCompose\Binding\FormBinding;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Form\FormSession;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\RenderContext;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Authoring\NameForm;
use FormCompose\Test\Fixture\Authoring\WebsiteForm;
use FormCompose\Test\Fixture\Composition\PostForm;
use FormCompose\Test\Fixture\ContactFormHelper;
use FormCompose\View\Helper\FormHelper;
use InvalidArgumentException;

/**
 * Exercises complete forms through real helpers, discovery, layouts, and session extensions.
 */
class FormHelperTest extends CompositionTestCase
{
    public function testClassRenderingCompilesEachBlueprintOnceWithItsLayoutAndContext(): void
    {
        $html = $this->helper(attributes: ['stripParentEmail' => true])->render(
            PostForm::class,
            new ArrayContext(['data' => ['title' => '<Title>', 'author' => ['first_name' => 'Ada']]]),
            ['url' => '/articles/save', 'method' => 'patch', 'class' => 'profile'],
            submit: 'Save <profile>',
            submitOptions: ['class' => 'primary', 'templates' => ['control' => 'themes/explicit/button']],
        );
        $xpath = $this->xpath($html);
        static::assertTrue($xpath->evaluate('boolean(//form[@action="/articles/save"][@class="profile"])'));
        static::assertTrue($xpath->evaluate('boolean(//input[@name="title"][@value="<Title>"])'));
        static::assertTrue($xpath->evaluate('boolean(//input[@name="author[first_name]"][@value="Ada"])'));
        static::assertStringContainsString('data-author', $html);
        static::assertStringContainsString('data-explicit-button', $html);
        static::assertStringContainsString('class="primary"', $html);
        static::assertStringContainsString('Save &lt;profile&gt;', $html);
        static::assertStringNotContainsString('name="author[email]"', $html);
        static::assertStringNotContainsString('name="author[internal]"', $html);
        static::assertSame(['author', 'post'], array_column($this->probe->calls, 'name'));
        static::assertSame(1, substr_count($html, '<form '));
        static::assertSame(1, substr_count($html, '</form>'));
    }

    public function testAliasesAndIndependentFormsKeepSeparateIdsAndDefaults(): void
    {
        $helper = $this->helper();
        $first = $helper->render('composed-post', submit: false);
        $second = $helper->render('composed-post');
        static::assertStringContainsString('id="form-compose-1-title"', $first);
        static::assertStringContainsString('id="form-compose-2-title"', $second);
        static::assertStringNotContainsString('<button', $first);
        static::assertStringContainsString('>Submit</button>', $second);
        static::assertSame(['author', 'post', 'author', 'post'], array_column($this->probe->calls, 'name'));
    }

    public function testBlueprintsUseTheSessionReturnedByHelperExtensions(): void
    {
        $helper = new ContactFormHelper($this->helper()->getView());
        $html = $helper->render(
            WebsiteForm::class,
            submit: new Html('<strong>Save</strong>'),
        );
        static::assertStringContainsString('type="url"', $html);
        static::assertStringContainsString('value="https://example.test"', $html);
        static::assertStringContainsString('data-contact-label', $html);
        static::assertStringContainsString('<strong>Save</strong>', $html);
    }

    public function testUnknownBlueprintDoesNotEchoPartialOutput(): void
    {
        ob_start();
        try {
            $this->helper()->render('missing-blueprint');
            static::fail('Expected blueprint resolution to fail.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('Unknown form blueprint', $invalidArgumentException->getMessage());
            static::assertSame('', ob_get_contents());
        } finally {
            ob_end_clean();
        }
    }

    public function testHelperRenderingUsesTheSessionsOverriddenRenderMethod(): void
    {
        $helper = new class($this->helper()->getView()) extends FormHelper {
            /**
             * Supply a session with application complete-form rendering behavior.
             */
            protected function createSession(
                FormBinding $binding,
                RenderContext $rendering,
                BlueprintFactory $blueprints,
            ): FormSession {
                return new class($binding, $rendering, $blueprints) extends FormSession {
                    /**
                     * Decorate complete forms through the session's rendering extension point.
                     *
                     * @param array<string, mixed> $options Opening options.
                     * @param array<string, mixed> $submitOptions Button options.
                     */
                    public function render(
                        ?string $blueprint = null,
                        array $options = [],
                        string|Html|false|null $submit = null,
                        array $submitOptions = [],
                    ): string {
                        return (
                            '<section data-session-render>'
                                . parent::render($blueprint, $options, $submit, $submitOptions)
                                . '</section>'
                        );
                    }
                };
            }
        };
        $html = $helper->render(NameForm::class, submit: 'Save');
        static::assertStringContainsString('data-session-render', $html);
        static::assertStringContainsString('name="name"', $html);
        static::assertStringContainsString('>Save</button>', $html);
    }
}
