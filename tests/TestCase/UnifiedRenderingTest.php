<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\AppendInputRenderer;
use FormCompose\Test\Fixture\FailOnceRenderer;
use FormCompose\Test\FormTestCase;
use LogicException;
use RuntimeException;

class UnifiedRenderingTest extends FormTestCase
{
    public function testFormButtonHiddenAndNativePartsShareThemeLookup(): void
    {
        $helper = $this->helper(attributes: ['csrfToken' => bin2hex(random_bytes(16))]);
        $theme = new Theme(['themes/unified'], [
            'form.open' => ['class' => 'application-form'],
            'button' => ['class' => 'application-button'],
        ]);
        $form = $helper->bind(null, ['method' => 'patch'], theme: $theme);
        $open = $form->open();
        static::assertStringContainsString('data-form-shell', $open);
        static::assertStringContainsString('class="application-form"', $open);
        static::assertStringContainsString('data-hidden', $open);
        static::assertStringContainsString('value="PATCH"', $open);
        static::assertStringContainsString('data-native-input', $form->control('title'));
        $button = $form->submit('Save');
        static::assertStringContainsString('data-button-shell', $button);
        static::assertStringContainsString('class="application-button"', $button);
        static::assertStringContainsString('data-form-end', $form->close());
    }

    public function testExplicitAndVariantButtonTemplates(): void
    {
        $form = $this->helper()->bind(theme: new Theme(['themes/unified']));
        $form->open();
        static::assertStringContainsString('data-primary', $form->submit('Save', ['variant' => 'primary']));
        static::assertStringContainsString('data-explicit-button', $form->submit('Save', ['templates' => [
            'control' => 'themes/explicit/button',
        ]]));
        $form->close();
    }

    public function testExplicitFormTemplatesIncludeInfrastructureOverrides(): void
    {
        $form = $this->helper()->bind(null, [
            'method' => 'patch',
            'templates' => [
                'open' => 'themes/explicit/open',
                'close' => 'themes/explicit/close',
                'hidden' => 'themes/explicit/hidden',
            ],
        ]);
        $open = $form->open();
        static::assertStringContainsString('data-explicit-open', $open);
        static::assertStringContainsString('data-explicit-hidden', $open);
        static::assertStringContainsString('data-explicit-close', $form->close());
    }

    public function testChoiceMarkupCanBeReplacedWithoutReplacingControlSemantics(): void
    {
        $form = $this->helper()->bind(theme: new Theme(['themes/unified']));
        $form->open();
        $select = $form->control('status', ['options' => ['a' => 'Active'], 'value' => 'a']);
        static::assertStringContainsString('data-option', $select);
        static::assertStringContainsString('selected="selected"', $select);
        static::assertStringContainsString('value="a"', $select);
        $form->close();
    }

    public function testFailedOpeningCanBeRetried(): void
    {
        $helper = $this->helper();
        $renderer = new FailOnceRenderer(new PhpRenderer($helper->getView()), 'form', 'open');
        $form = $helper->bind(renderer: $renderer);
        try {
            $form->open();
            static::fail('Expected the opening template to fail.');
        } catch (RuntimeException $exception) {
            static::assertSame('Template failed', $exception->getMessage());
        }
        static::assertStringContainsString('<form', $form->open());
        static::assertStringContainsString('</form>', $form->close());
    }

    public function testFailedClosingCanBeRetried(): void
    {
        $helper = $this->helper();
        $renderer = new FailOnceRenderer(new PhpRenderer($helper->getView()), 'form', 'close');
        $form = $helper->bind(renderer: $renderer);
        $form->open();
        $form->control('name');
        try {
            $form->close();
            static::fail('Expected the closing template to fail.');
        } catch (RuntimeException $exception) {
            static::assertSame('Template failed', $exception->getMessage());
        }
        static::assertStringContainsString('</form>', $form->close());
    }

    public function testClosingCannotRegisterInputsAfterTokensArePrepared(): void
    {
        $helper = $this->helper();
        $form = $helper->bind(renderer: new AppendInputRenderer(new PhpRenderer($helper->getView())));
        $form->open();
        try {
            $form->close();
            static::fail('Expected a late input to be rejected.');
        } catch (LogicException $exception) {
            static::assertStringContainsString('current phase is Finalizing', $exception->getMessage());
        }
        static::assertStringContainsString('name="after_failure"', $form->control('after_failure'));
        static::assertStringContainsString('</form>', $form->close());
    }
}
