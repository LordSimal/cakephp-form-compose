<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\View\Form\ArrayContext;
use FormCompose\Test\Fixture\Authoring\LocalEmailForm;
use FormCompose\Test\Fixture\Blueprint\ChildForm;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileForm;
use FormCompose\Test\FormTestCase;

/**
 * Exercises attributed configuration through public binding and rendering entry points.
 */
class BlueprintTest extends FormTestCase
{
    public function testStaticDeclarationsSupplyDefaultsAndPreserveBoundValues(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['data' => ['name' => 'Ada', 'email' => 'ada@example.test']]))
            ->blueprint(ProfileForm::class)
            ->bind();
        static::assertSame('ada@example.test', $form->field('email')->resolve()->state->value);
        static::assertSame('email', $form->field('email')->resolve()->state->type);
        static::assertNull($form->field('comments.0.body')->resolve()->state->value);
        static::assertFalse($form->field('active')->resolve()->state->value);
        static::assertSame(0, $form->field('count')->resolve()->state->value);
        $html = $form->open() . $form->blueprint()->render() . $form->close();
        static::assertStringContainsString('Full &lt;name&gt;', $html);
        static::assertStringContainsString('Use &lt;work&gt; email.', $html);
        static::assertStringContainsString('value="ada@example.test"', $html);
        static::assertStringContainsString('name="comments[0][body]"', $html);
        static::assertStringContainsString('aria-describedby="form-compose-1-email-help"', $html);
        static::assertStringContainsString('id="form-compose-1-email-help"', $html);
        static::assertStringContainsString('for="form-compose-1-email"', $html);
        static::assertStringNotContainsString('for="form-compose-1-active"', $html);
        static::assertLessThan(strpos($html, 'name="email"'), strpos($html, 'name="name"'));
    }

    public function testPerCallOptionsOverrideDefaultsWithoutLeaking(): void
    {
        $form = $this->helper()->form()->blueprint(ProfileForm::class)->bind();
        $field = $form->field('email')
            ->label(false)
            ->value('')
            ->type('text')
            ->attributes(['data-explicit' => 'yes'])
            ->resolve();
        static::assertFalse($field->state->config->label);
        static::assertSame('', $field->state->value);
        static::assertSame('text', $field->state->type);
        static::assertSame(
            ['class' => 'original', 'data-explicit' => 'yes'],
            $field->state->config->presentation->attributes,
        );
        static::assertSame('Use <work> email.', $field->state->config->help);
        static::assertSame('Email', $form->field('email')->resolve()->state->config->label);
        static::assertSame('email', $form->field('email')->resolve()->state->type);
        static::assertNull($form->field('ad_hoc')->resolve()->state->config->type);
    }

    public function testLocalBlueprintDoesNotReplaceBoundDefaults(): void
    {
        $form = $this->helper()->form()->blueprint(ProfileForm::class)->bind();
        $form->open();

        $html = $form->blueprint(blueprint: LocalEmailForm::class)->render();
        $form->close();
        static::assertStringContainsString('Local email', $html);
        static::assertStringContainsString('type="email"', $html);
        static::assertStringContainsString('name="extra"', $html);
        static::assertSame('Email', $form->field('email')->resolve()->state->config->label);
        static::assertFalse($form->binding->definition?->has('extra'));
    }

    public function testParentDeclarationsAreOverriddenInPlaceAndRemainReusable(): void
    {
        $helper = $this->helper();
        $child = $helper->form()->blueprint(ChildForm::class)->bind();
        $parent = $helper->form()->blueprint(ProfileForm::class)->bind();
        static::assertSame(
            ['name', 'email', 'comments.0.body', 'active', 'count', 'phone'],
            array_column($child->binding->definition->fields ?? [], 'path'),
        );
        static::assertSame('Child email', $child->field('email')->resolve()->state->config->label);
        static::assertSame('email', $child->field('email')->resolve()->state->type);
        static::assertSame('Use <work> email.', $child->field('email')->resolve()->state->config->help);
        static::assertSame('Email', $parent->field('email')->resolve()->state->config->label);
        static::assertNotSame(
            $parent->field('email')->resolve()->state->id,
            $child->field('email')->resolve()->state->id,
        );
    }

    public function testUnselectedDefinitionRendersNothingAndDirectControlsStillWork(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        static::assertSame('', $form->blueprint()->render());
        static::assertStringContainsString('name="email"', $form->control('email')->render());
        $form->close();
        static::assertSame('email', $form->field('email')->resolve()->state->type);
    }

    public function testManualFieldPartsUseBlueprintConfiguration(): void
    {
        $form = $this->helper()->form()->blueprint(ProfileForm::class)->bind();
        $email = $form->field('email')->resolve();
        $form->open();
        $html =
            $email->label()->render()
            . $email->input()->render()
            . $email->help()->render()
            . $email->errors()->render();
        $form->close();
        static::assertStringContainsString('type="email"', $html);
        static::assertStringContainsString('class="original"', $html);
        static::assertStringContainsString('Use &lt;work&gt; email.', $html);
    }
}
