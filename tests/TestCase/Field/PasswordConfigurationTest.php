<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Field;

use FormCompose\Binding\FormBinding;
use FormCompose\FormSession;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\Theme;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies configurable password conventions through helper and direct binding APIs.
 */
class PasswordConfigurationTest extends FormTestCase
{
    public function testRegisteredHelperConfigurationProtectsApplicationPasswordNames(): void
    {
        $secret = bin2hex(random_bytes(16));
        $view = $this->helper(['account' => ['passphrase' => $secret]])->getView();
        $helper = $view->loadHelper('Compose', [
            'className' => 'FormCompose.Form',
            'passwordFields' => ['password', 'passwd', 'passphrase'],
        ]);
        static::assertInstanceOf(FormHelper::class, $helper);
        $form = $helper->bind();
        $html = $form->open() . $form->control('account.passphrase') . $form->close();
        static::assertStringContainsString('type="password"', $html);
        static::assertStringNotContainsString($secret, $html);
        static::assertStringNotContainsString('passwordFields', $html);
    }

    public function testBindingOverridesReplaceNamesWithoutChangingTheHelperDefault(): void
    {
        $helper = $this->helper();
        $helper->setConfig('passwordFields', ['password', 'passwd', 'passphrase']);

        $override = $helper->bind(null, ['passwordFields' => ['password', 'new_password']]);
        static::assertSame('password', $override->field('account.new_password')->state->type);
        static::assertSame('text', $override->field('passphrase')->state->type);
        static::assertSame('text', $override->field('passwd')->state->type);
        static::assertSame('text', $override->field('password', ['type' => 'text'])->state->type);
        static::assertSame('text', $helper->bind()->field('new_password')->state->type);
        static::assertSame('password', $helper->bind()->field('passphrase')->state->type);
        $html = $override->open(['passwordFields' => ['different']]);
        static::assertStringNotContainsString('passwordFields', $html);
        static::assertSame('password', $override->field('new_password')->state->type);
        $override->close();
    }

    public function testHelperChangesAffectOnlyFutureBindingsAndStayLocal(): void
    {
        $helper = $this->helper();
        $helper->setConfig('passwordFields', ['password', 'passwd', 'passphrase']);

        $first = $helper->bind();
        $helper->setConfig('passwordFields', ['password', 'passwd', 'new_password'], false);
        $second = $helper->bind();
        static::assertSame('password', $first->field('passphrase')->state->type);
        static::assertSame('text', $first->field('new_password')->state->type);
        static::assertSame('text', $second->field('passphrase')->state->type);
        static::assertSame('password', $second->field('new_password')->state->type);
        static::assertSame('text', new FormHelper($helper->getView())->bind()->field('new_password')->state->type);
        $helper->deleteConfig('passwordFields');
        static::assertSame('password', $helper->bind()->field('password')->state->type);
        static::assertSame('text', $helper->bind()->field('new_password')->state->type);
    }

    public function testEmptyNamesDisableInferenceButExplicitPasswordInputsRemainSafe(): void
    {
        $secret = bin2hex(random_bytes(16));
        $form = $this->helper()->bind(null, ['passwordFields' => []]);
        static::assertSame('text', $form->field('password')->state->type);
        $html = $form->open() . $form->control('password', ['type' => 'password', 'value' => $secret]) . $form->close();
        static::assertStringContainsString('type="password"', $html);
        static::assertStringNotContainsString($secret, $html);
        $helper = $this->helper();
        $helper->setConfig('passwordFields', [], false);
        static::assertSame('text', $helper->bind()->field('password')->state->type);
    }

    public function testDirectBindingsRetainTheDefaultPasswordConventions(): void
    {
        $view = $this->helper()->getView();
        $context = $this->helper()->bind()->binding->context;
        $binding = new FormBinding($context, $view->getRequest(), 'direct');
        $theme = Theme::base();
        $form = new FormSession($binding, new RenderContext(new PhpRenderer($view, $theme), $theme));
        static::assertSame('password', $form->field('account.passwd')->state->type);
    }

    /**
     * @param mixed $names Invalid password-name configuration.
     */
    #[DataProvider('invalidNames')]
    public function testInvalidNamesFailDuringBinding(mixed $names): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->bind(null, ['passwordFields' => $names]);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidNames(): array
    {
        return [
            'string' => ['password'],
            'null' => [null],
            'associative map' => [['password' => true]],
            'integer item' => [[1]],
            'empty name' => [['']],
            'full path' => [['account.password']],
        ];
    }
}
