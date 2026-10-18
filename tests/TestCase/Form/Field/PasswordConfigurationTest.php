<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use FormCompose\Binding\FormBinding;
use FormCompose\Form\FormSession;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\Theme;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use TypeError;

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
        $form = $helper->form()->bind();
        $html = $form->open() . $form->control('account.passphrase')->render() . $form->close();
        static::assertStringContainsString('type="password"', $html);
        static::assertStringNotContainsString($secret, $html);
        static::assertStringNotContainsString('passwordFields', $html);
    }

    public function testBindingOverridesReplaceNamesWithoutChangingTheHelperDefault(): void
    {
        $helper = $this->helper();
        $helper->setConfig('passwordFields', ['password', 'passwd', 'passphrase']);

        $override = $helper->form()
            ->passwordFields(['password', 'new_password'])
            ->bind();
        static::assertSame('password', $override->field('account.new_password')->resolve()->state->type);
        static::assertSame('text', $override->field('passphrase')->resolve()->state->type);
        static::assertSame('text', $override->field('passwd')->resolve()->state->type);
        static::assertSame(
            'text',
            $override->field('password')->type('text')->resolve()->state->type,
        );
        static::assertSame('text', $helper->form()->bind()->field('new_password')->resolve()->state->type);
        static::assertSame('password', $helper->form()->bind()->field('passphrase')->resolve()->state->type);
        $html = $override->open();
        static::assertStringNotContainsString('passwordFields', $html);
        static::assertSame('password', $override->field('new_password')->resolve()->state->type);
        $override->close();
    }

    public function testHelperChangesAffectOnlyFutureBindingsAndStayLocal(): void
    {
        $helper = $this->helper();
        $helper->setConfig('passwordFields', ['password', 'passwd', 'passphrase']);

        $first = $helper->form()->bind();
        $helper->setConfig('passwordFields', ['password', 'passwd', 'new_password'], false);
        $second = $helper->form()->bind();
        static::assertSame('password', $first->field('passphrase')->resolve()->state->type);
        static::assertSame('text', $first->field('new_password')->resolve()->state->type);
        static::assertSame('text', $second->field('passphrase')->resolve()->state->type);
        static::assertSame('password', $second->field('new_password')->resolve()->state->type);
        static::assertSame(
            'text',
            new FormHelper($helper->getView())->form()->bind()->field('new_password')->resolve()->state->type,
        );
        $helper->deleteConfig('passwordFields');
        static::assertSame('password', $helper->form()->bind()->field('password')->resolve()->state->type);
        static::assertSame('text', $helper->form()->bind()->field('new_password')->resolve()->state->type);
    }

    public function testEmptyNamesDisableInferenceButExplicitPasswordInputsRemainSafe(): void
    {
        $secret = bin2hex(random_bytes(16));
        $form = $this->helper()->form()->passwordFields([])->bind();
        static::assertSame('text', $form->field('password')->resolve()->state->type);
        $html =
            $form->open()
            . $form->control('password')->type('password')->value($secret)->render()
            . $form->close();
        static::assertStringContainsString('type="password"', $html);
        static::assertStringNotContainsString($secret, $html);
        $helper = $this->helper();
        $helper->setConfig('passwordFields', [], false);
        static::assertSame('text', $helper->form()->bind()->field('password')->resolve()->state->type);
    }

    public function testDirectBindingsRetainTheDefaultPasswordConventions(): void
    {
        $view = $this->helper()->getView();
        $context = $this->helper()->form()->bind()->binding->context;
        $binding = new FormBinding($context, $view->getRequest(), 'direct');
        $theme = Theme::base();
        $form = new FormSession($binding, new RenderContext(new PhpRenderer($view, $theme), $theme));
        static::assertSame('password', $form->field('account.passwd')->resolve()->state->type);
    }

    /**
     * @param mixed $names Invalid password-name configuration.
     */
    #[DataProvider('invalidNames')]
    public function testInvalidNamesFailDuringBinding(mixed $names): void
    {
        $this->expectException(is_array($names) ? InvalidArgumentException::class : TypeError::class);
        $builder = $this->helper()->form();
        $this->invokeBuilder($builder, 'passwordFields', $names);
        $builder->bind();
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
