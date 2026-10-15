<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\View\Form\ArrayContext;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\Theme;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;

class RenderingTest extends FormTestCase
{
    public function testLabelsHelpErrorsAndValuesAreEscapedAndAssociated(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'errors' => ['email' => ['invalid' => '<script>bad</script>']],
        ]));
        $field = $form->field('email', [
            'type' => 'email',
            'label' => '<b>Email</b>',
            'help' => '<i>Help</i>',
            'value' => '"><script>bad</script>',
        ]);
        $form->open(['url' => '/articles/save']);
        $html = $field->control();
        static::assertStringContainsString('&lt;b&gt;Email&lt;/b&gt;', $html);
        static::assertStringContainsString('&lt;i&gt;Help&lt;/i&gt;', $html);
        static::assertStringNotContainsString('<script>', $html);
        static::assertStringContainsString('aria-invalid="true"', $html);
        static::assertStringContainsString(
            'aria-describedby="' . $field->state->id . '-help ' . $field->state->id . '-errors"',
            $html,
        );
        static::assertStringContainsString('id="' . $field->state->id . '-errors"', $html);
        static::assertStringContainsString('for="' . $field->state->id . '"', $html);
        $form->close();
    }

    public function testSuppressedPartsDoNotProduceAriaReferences(): void
    {
        $field = $this->helper()
            ->bind(new ArrayContext(['errors' => ['name' => ['bad']]]))
            ->field('name', ['label' => false, 'help' => false, 'errors' => false]);
        static::assertSame('', $field->label());
        static::assertSame('', $field->help());
        static::assertSame('', $field->errors());
        static::assertArrayNotHasKey('aria-describedby', $field->attributes()->values);
    }

    public function testTrustedMarkupRequiresExplicitWrapper(): void
    {
        $field = $this->helper()
            ->bind()
            ->field('name', ['label' => new Html('<b>Name</b>'), 'help' => new Html('<em>Help</em>')]);
        static::assertStringContainsString('<b>Name</b>', $field->label());
        static::assertStringContainsString('<em>Help</em>', $field->help());
    }

    public function testNativeControlsSupportChoicesAndSpecialValues(): void
    {
        $form = $this->helper()->bind();
        $form->open(['url' => '/articles/save']);
        static::assertStringContainsString('value="0"', $form->control('off', ['value' => false]));
        static::assertStringContainsString('&lt;body&gt;', $form->control('body', [
            'type' => 'textarea',
            'value' => '<body>',
        ]));
        static::assertStringContainsString('selected="selected"', $form->control('status', [
            'options' => ['a' => 'Active', 'b' => 'Blocked'],
            'value' => 'a',
        ]));
        $checkbox = $form->control('active', ['type' => 'checkbox', 'value' => true]);
        static::assertStringContainsString('type="hidden"', $checkbox);
        static::assertStringContainsString('checked="checked"', $checkbox);
        static::assertStringNotContainsString('secret', $form->control('password', [
            'type' => 'password',
            'value' => 'secret',
        ]));
        static::assertStringNotContainsString('<label', $form->control('id', ['type' => 'hidden', 'value' => '2']));
        $form->close();
    }

    public function testGroupedControlsUseLegendsAndUniqueChoiceIds(): void
    {
        $form = $this->helper()->bind();
        $form->open(['url' => '/articles/save']);
        $radio = $form->control('status', [
            'type' => 'radio',
            'options' => ['a' => 'Active', 'b' => 'Blocked'],
            'value' => 'b',
        ]);
        static::assertStringContainsString('<fieldset>', $radio);
        static::assertStringContainsString('<legend>Status</legend>', $radio);
        static::assertStringContainsString('id="form-compose-1-status-a"', $radio);
        static::assertStringContainsString('id="form-compose-1-status-b"', $radio);
        $checks = $form->control('tags', [
            'type' => 'multicheckbox',
            'options' => ['a' => 'A', 'b' => 'B'],
            'value' => ['a'],
        ]);
        static::assertStringContainsString('name="tags[]"', $checks);
        $multiple = $form->control('categories', ['options' => ['a' => 'A'], 'multiple' => true, 'value' => ['a']]);
        static::assertStringContainsString('name="categories[]"', $multiple);
        $form->close();
    }

    public function testThemeDefaultsAndFieldAttributesAreMerged(): void
    {
        $theme = new Theme(defaults: [
            'input' => ['class' => 'control'],
            'email.input' => ['class' => 'email-control'],
            'label' => ['class' => 'caption'],
        ]);
        $field = $this->helper()
            ->bind(theme: $theme)
            ->field('email', [
                'type' => 'email',
                'attributes' => ['class' => 'wide', 'x-model' => 'email'],
                'partAttributes' => ['help' => ['class' => 'hint']],
            ]);
        static::assertSame('email-control wide', $field->attributes()->values['class']);
        static::assertSame('email', $field->attributes()->values['x-model']);
        static::assertSame('caption', $field->attributes('label')->values['class']);
        static::assertSame('hint', $field->attributes('help')->values['class']);
    }

    public function testReservedInputAttributesCannotSilentlyChangeSecurityIdentity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->bind()->field('email')->attributes(extra: ['name' => 'other']);
    }

    public function testUnknownThemeNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->bind(theme: 'UnknownTheme');
    }

    public function testHiddenControlsDoNotReferenceUnrenderedFeedback(): void
    {
        $form = $this->helper()->bind(new ArrayContext(['errors' => ['id' => ['invalid']]]));
        $form->open(['url' => '/articles/save']);
        $html = $form->control('id', ['type' => 'hidden', 'help' => 'Invisible help']);
        static::assertStringNotContainsString('aria-describedby', $html);
        static::assertStringNotContainsString('aria-invalid', $html);
        $form->close();
    }
}
