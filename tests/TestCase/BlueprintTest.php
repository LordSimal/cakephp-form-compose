<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

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
        $form = $this->helper()->bind(
            new ArrayContext(['data' => ['name' => 'Ada', 'email' => 'ada@example.test']]),
            definition: ProfileForm::class,
        );
        static::assertSame('ada@example.test', $form->field('email')->state->value);
        static::assertSame('email', $form->field('email')->state->type);
        static::assertNull($form->field('comments.0.body')->state->value);
        static::assertFalse($form->field('active')->state->value);
        static::assertSame(0, $form->field('count')->state->value);
        $html = $form->open() . $form->definition() . $form->close();
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
        $form = $this->helper()->bind(definition: ProfileForm::class);
        $field = $form->field('email', [
            'label' => false,
            'value' => '',
            'type' => 'text',
            'attributes' => ['data-explicit' => 'yes'],
        ]);
        static::assertFalse($field->state->options['label']);
        static::assertSame('', $field->state->value);
        static::assertSame('text', $field->state->type);
        static::assertSame(['data-explicit' => 'yes'], $field->state->options['attributes']);
        static::assertSame('Use <work> email.', $field->state->options['help']);
        static::assertSame('Email', $form->field('email')->state->options['label']);
        static::assertSame('email', $form->field('email')->state->type);
        static::assertSame([], $form->field('ad_hoc')->state->options);
    }

    public function testLocalBlueprintDoesNotReplaceBoundDefaults(): void
    {
        $form = $this->helper()->bind(definition: ProfileForm::class);
        $form->open();

        $html = $form->definition(LocalEmailForm::class);
        $form->close();
        static::assertStringContainsString('Local email', $html);
        static::assertStringContainsString('type="email"', $html);
        static::assertStringContainsString('name="extra"', $html);
        static::assertSame('Email', $form->field('email')->state->options['label']);
        static::assertFalse($form->binding->definition?->has('extra'));
    }

    public function testParentDeclarationsAreOverriddenInPlaceAndRemainReusable(): void
    {
        $helper = $this->helper();
        $child = $helper->bind(definition: ChildForm::class);
        $parent = $helper->bind(definition: ProfileForm::class);
        static::assertSame(
            ['name', 'email', 'comments.0.body', 'active', 'count', 'phone'],
            array_column($child->binding->definition->fields ?? [], 'path'),
        );
        static::assertSame('Child email', $child->field('email')->state->options['label']);
        static::assertSame('email', $child->field('email')->state->type);
        static::assertSame('Use <work> email.', $child->field('email')->state->options['help']);
        static::assertSame('Email', $parent->field('email')->state->options['label']);
        static::assertNotSame($parent->field('email')->state->id, $child->field('email')->state->id);
    }

    public function testUnselectedDefinitionRendersNothingAndDirectControlsStillWork(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        static::assertSame('', $form->definition());
        static::assertStringContainsString('name="email"', $form->control('email'));
        $form->close();
        static::assertSame('text', $form->field('email')->state->type);
    }

    public function testManualFieldPartsUseBlueprintConfiguration(): void
    {
        $form = $this->helper()->bind(definition: ProfileForm::class);
        $email = $form->field('email');
        $form->open();
        $html = $email->label() . $email->input() . $email->help() . $email->errors();
        $form->close();
        static::assertStringContainsString('type="email"', $html);
        static::assertStringContainsString('class="original"', $html);
        static::assertStringContainsString('Use &lt;work&gt; email.', $html);
    }
}
