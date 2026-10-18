<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\View\Helper;

use Cake\View\Form\ArrayContext;
use FormCompose\Binding\FormBinding;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Configuration\ButtonConfig;
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
        $html = $this->helper(attributes: ['stripParentEmail' => true])
            ->form(new ArrayContext(['data' => ['title' => '<Title>', 'author' => ['first_name' => 'Ada']]]))
            ->blueprint(PostForm::class)
            ->url('/articles/save')
            ->method('patch')
            ->attributes(['class' => 'profile'])
            ->submit('Save <profile>')
            ->submitConfig(
                ButtonConfig::builder()
                    ->partTemplate('control', 'themes/explicit/button')
                    ->attributes(['class' => 'primary']),
            )
            ->render();
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
        static::assertSame(['post', 'author'], array_column($this->probe->calls, 'name'));
        static::assertSame(1, substr_count($html, '<form '));
        static::assertSame(1, substr_count($html, '</form>'));
    }

    public function testAliasesAndIndependentFormsKeepSeparateIdsAndDefaults(): void
    {
        $helper = $this->helper();
        $first = $helper->form()->blueprint('composed-post')->submit(false)->render();
        $second = $helper->form()->blueprint('composed-post')->render();
        static::assertStringContainsString('id="form-compose-1-title"', $first);
        static::assertStringContainsString('id="form-compose-2-title"', $second);
        static::assertStringNotContainsString('<button', $first);
        static::assertStringContainsString('>Submit</button>', $second);
        static::assertSame(['post', 'author', 'post', 'author'], array_column($this->probe->calls, 'name'));
    }

    public function testBlueprintsUseTheSessionReturnedByHelperExtensions(): void
    {
        $helper = new ContactFormHelper($this->helper()->getView());
        $html = $helper->form()->blueprint(WebsiteForm::class)->submit(new Html('<strong>Save</strong>'))->render();
        static::assertStringContainsString('type="url"', $html);
        static::assertStringContainsString('value="https://example.test"', $html);
        static::assertStringContainsString('data-contact-label', $html);
        static::assertStringContainsString('<strong>Save</strong>', $html);
    }

    public function testUnknownBlueprintDoesNotEchoPartialOutput(): void
    {
        ob_start();
        try {
            $this->helper()->form()->blueprint('missing-blueprint')->render();
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
                     */
                    public function render(): string
                    {
                        return '<section data-session-render>' . parent::render() . '</section>';
                    }
                };
            }
        };
        $html = $helper->form()->blueprint(NameForm::class)->submit('Save')->render();
        static::assertStringContainsString('data-session-render', $html);
        static::assertStringContainsString('name="name"', $html);
        static::assertStringContainsString('>Save</button>', $html);
    }
}
