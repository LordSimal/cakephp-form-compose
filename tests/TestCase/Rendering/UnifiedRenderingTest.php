<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

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
        $form = $helper->form()->method('patch')->theme($theme)->bind();
        $open = $form->open();
        static::assertStringContainsString('data-form-shell', $open);
        static::assertStringContainsString('class="application-form"', $open);
        static::assertStringContainsString('data-hidden', $open);
        static::assertStringContainsString('value="PATCH"', $open);
        static::assertStringContainsString('data-native-input', $form->control('title')->render());
        $button = $form->submit('Save')->render();
        static::assertStringContainsString('data-button-shell', $button);
        static::assertStringContainsString('class="application-button"', $button);
        static::assertStringContainsString('data-form-end', $form->close());
    }

    public function testExplicitAndVariantButtonTemplates(): void
    {
        $form = $this->helper()
            ->form()
            ->theme(new Theme(['themes/unified']))
            ->bind();
        $form->open();
        static::assertStringContainsString(
            'data-primary',
            $form->submit('Save')->variant('primary')->render(),
        );
        static::assertStringContainsString(
            'data-explicit-button',
            $form->submit('Save')->partTemplate('control', 'themes/explicit/button')->render(),
        );
        $form->close();
    }

    public function testExplicitFormTemplatesIncludeInfrastructureOverrides(): void
    {
        $form = $this->helper()
            ->form()
            ->method('patch')
            ->partTemplate('open', 'themes/explicit/open')
            ->partTemplate('close', 'themes/explicit/close')
            ->partTemplate('hidden', 'themes/explicit/hidden')
            ->bind();
        $open = $form->open();
        static::assertStringContainsString('data-explicit-open', $open);
        static::assertStringContainsString('data-explicit-hidden', $open);
        static::assertStringContainsString('data-explicit-close', $form->close());
    }

    public function testChoiceMarkupCanBeReplacedWithoutReplacingControlSemantics(): void
    {
        $form = $this->helper()
            ->form()
            ->theme(new Theme(['themes/unified']))
            ->bind();
        $form->open();

        $select = $form->control('status')
            ->choices(['a' => 'Active'])
            ->value('a')
            ->render();
        static::assertStringContainsString('data-option', $select);
        static::assertStringContainsString('selected="selected"', $select);
        static::assertStringContainsString('value="a"', $select);
        $form->close();
    }

    public function testFailedOpeningCanBeRetried(): void
    {
        $helper = $this->helper();
        $renderer = new FailOnceRenderer(new PhpRenderer($helper->getView()), 'form', 'open');
        $form = $helper->form()->renderer($renderer)->bind();
        try {
            $form->open();
            static::fail('Expected the opening template to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Template failed', $runtimeException->getMessage());
        }

        static::assertStringContainsString('<form', $form->open());
        static::assertStringContainsString('</form>', $form->close());
    }

    public function testFailedClosingCanBeRetried(): void
    {
        $helper = $this->helper();
        $renderer = new FailOnceRenderer(new PhpRenderer($helper->getView()), 'form', 'close');
        $form = $helper->form()->renderer($renderer)->bind();
        $form->open();
        $form->control('name')->render();
        try {
            $form->close();
            static::fail('Expected the closing template to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Template failed', $runtimeException->getMessage());
        }

        static::assertStringContainsString('</form>', $form->close());
    }

    public function testClosingCannotRegisterInputsAfterTokensArePrepared(): void
    {
        $helper = $this->helper();
        $form = $helper->form()
            ->renderer(new AppendInputRenderer(new PhpRenderer($helper->getView())))
            ->bind();
        $form->open();
        try {
            $form->close();
            static::fail('Expected a late input to be rejected.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('current phase is Finalizing', $logicException->getMessage());
        }

        static::assertStringContainsString('name="after_failure"', $form->control('after_failure')->render());
        static::assertStringContainsString('</form>', $form->close());
    }
}
