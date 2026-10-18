<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\View\Helper;

use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileForm;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

/**
 * Verifies helper theme defaults through real registration, rendering, and action shortcuts.
 */
class DefaultThemeTest extends FormTestCase
{
    public function testHelperRegistrationSuppliesTemplateLayersAndAttributeDefaults(): void
    {
        $view = $this->helper()->getView();
        $helper = $view->loadHelper('Compose', [
            'className' => 'FormCompose.Form',
            'theme' => new Theme(
                layers: ['themes/application', 'themes/library'],
                defaults: [
                    'input' => ['data-theme' => '<configured>'],
                    'button' => ['class' => 'configured-button'],
                ],
            ),
        ]);
        static::assertInstanceOf(FormHelper::class, $helper);
        $form = $helper->form()->bind();
        $html = $form->open() . $form->control('email')->render() . $form->submit('Save')->render() . $form->close();
        static::assertStringContainsString('data-layout="application"', $html);
        static::assertStringContainsString('data-layout="library"', $html);
        static::assertStringContainsString('data-theme="&lt;configured&gt;"', $html);
        static::assertStringContainsString('class="configured-button"', $html);
    }

    public function testExplicitThemeReplacesTheDefaultForOneBinding(): void
    {
        $helper = new FormHelper($this->helper()->getView(), ['theme' => new Theme(
            layers: ['themes/application'],
            defaults: ['input' => ['data-default' => true]],
        )]);
        $form = $helper->form()
            ->theme(new Theme(
                layers: ['themes/library'],
                defaults: ['input' => ['data-override' => true]],
            ))
            ->bind();
        $html = $form->open() . $form->control('email')->render() . $form->close();
        static::assertStringContainsString('data-layout="library"', $html);
        static::assertStringContainsString('data-override', $html);
        static::assertStringNotContainsString('data-layout="application"', $html);
        static::assertStringNotContainsString('data-default', $html);

        $next = $helper->form()->bind();
        $html = $next->open() . $next->control('email')->render() . $next->close();
        static::assertStringContainsString('data-layout="application"', $html);
        static::assertStringContainsString('data-default', $html);
        static::assertStringNotContainsString('data-override', $html);
    }

    public function testExplicitBaseBypassesConfiguredThemeWithoutChangingIt(): void
    {
        $helper = new FormHelper($this->helper()->getView(), ['theme' => new Theme(['themes/application'])]);
        $base = $helper->form()->theme(Theme::base())->bind();
        $html = $base->open() . $base->control('email')->render() . $base->close();
        static::assertStringNotContainsString('data-layout=', $html);
        static::assertStringContainsString('name="email"', $html);

        $configured = $helper->form()->bind();
        $html = $configured->open() . $configured->control('email')->render() . $configured->close();
        static::assertStringContainsString('data-layout="application"', $html);
    }

    public function testConfigurationChangesOnlyAffectFutureBindingsAndStayLocalToTheHelper(): void
    {
        $helper = new FormHelper($this->helper()->getView(), ['theme' => new Theme(['themes/application'])]);
        $first = $helper->form()->bind();
        $helper->setConfig('theme', new Theme(['themes/library']));
        $second = $helper->form()->bind();
        $firstHtml = $first->open() . $first->control('email')->render() . $first->close();
        $secondHtml = $second->open() . $second->control('email')->render() . $second->close();
        static::assertStringContainsString('data-layout="application"', $firstHtml);
        static::assertStringNotContainsString('data-layout="library"', $firstHtml);
        static::assertStringContainsString('data-layout="library"', $secondHtml);
        static::assertStringNotContainsString('data-layout="application"', $secondHtml);

        $other = new FormHelper($helper->getView())->form()->bind();
        $otherHtml = $other->open() . $other->control('email')->render() . $other->close();
        static::assertStringNotContainsString('data-layout=', $otherHtml);
        $helper->setConfig('theme');
        $reset = $helper->form()->bind();
        static::assertStringNotContainsString(
            'data-layout=',
            $reset->open() . $reset->control('email')->render() . $reset->close(),
        );
    }

    public function testCompleteFormRenderingInheritsTheHelperTheme(): void
    {
        $helper = new FormHelper($this->helper()->getView(), ['theme' => new Theme(
            layers: ['themes/application'],
            defaults: ['button' => ['class' => 'configured-button']],
        )]);
        $html = $helper->form()->blueprint(ProfileForm::class)->submit('Save')->render();
        static::assertStringContainsString('data-layout="application"', $html);
        static::assertStringContainsString('class="configured-button"', $html);
    }

    #[DataProvider('actionKinds')]
    public function testActionShortcutsShareThemeDefaultsAndOverrides(string $kind): void
    {
        $helper = new FormHelper($this->helper()->getView(), ['theme' => new Theme(defaults: [
            'form.open' => ['data-theme' => 'configured'],
            'button' => ['class' => 'configured-trigger'],
            'action.control' => ['class' => 'configured-trigger'],
        ])]);
        $render = static fn(Theme|string|null $theme = null): string => $kind === 'button'
            ? $helper->postButton(label: 'Go', url: '/go', theme: $theme)->render()
            : $helper->postLink(label: 'Go', url: '/go', theme: $theme)->render();
        $html = $render();
        static::assertStringContainsString('data-theme="configured"', $html);
        static::assertStringContainsString('class="configured-trigger"', $html);

        $override = $render(new Theme(defaults: ['form.open' => ['data-theme' => 'override']]));
        static::assertStringContainsString('data-theme="override"', $override);
        static::assertStringNotContainsString('configured', $override);
        static::assertStringNotContainsString('configured', $render('Base'));
        static::assertStringContainsString('class="configured-trigger"', $render());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function actionKinds(): array
    {
        return ['button' => ['button'], 'link' => ['link']];
    }

    /**
     * @param mixed $theme Invalid helper configuration.
     */
    #[DataProvider('invalidThemes')]
    public function testInvalidConfiguredThemesFailClearly(mixed $theme): void
    {
        $helper = new FormHelper($this->helper()->getView(), ['theme' => $theme]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Use Base or a Theme instance.');
        $helper->form()->bind();
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidThemes(): array
    {
        return [
            'unknown name' => ['Unknown'],
            'array' => [['layers' => ['themes/application']]],
            'boolean' => [false],
            'unrelated object' => [new stdClass()],
        ];
    }

    public function testExplicitThemeTakesPrecedenceOverInvalidHelperConfiguration(): void
    {
        $helper = new FormHelper($this->helper()->getView(), ['theme' => false]);
        $form = $helper->form()->theme(Theme::base())->bind();
        static::assertStringContainsString(
            'name="email"',
            $form->open() . $form->control('email')->render() . $form->close(),
        );
        $this->expectException(InvalidArgumentException::class);
        $helper->form()->bind();
    }
}
