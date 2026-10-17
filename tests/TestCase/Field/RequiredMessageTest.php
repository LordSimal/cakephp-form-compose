<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Field;

use Cake\Form\Form;
use Cake\Form\FormProtector;
use Cake\View\Form\ArrayContext;
use FormCompose\FormContext;
use FormCompose\Test\AssociationTestCase;

/**
 * Reads Cake required-validation metadata without introducing validation or rendering side effects.
 */
class RequiredMessageTest extends AssociationTestCase
{
    public function testRequiredMessagesAreAvailableToTemplatesAndEscapedAsOrdinaryAttributeText(): void
    {
        $message = 'Enter a <name> with "quotes" & details';
        $form = $this->helper()->bind(new ArrayContext(['required' => ['name' => $message]]));
        $field = $form->field('name');
        static::assertSame($message, $field->state->requiredMessage);
        static::assertTrue($field->state->constraints['required']);
        $html =
            $form->open() . $field->input(['data-required-message' => $field->state->requiredMessage]) . $form->close();
        static::assertStringContainsString('&lt;name&gt;', $html);
        static::assertSame($message, $this->xpath($html)->evaluate('string(//input/@data-required-message)'));
        static::assertStringNotContainsString('oninvalid=', $html);
        static::assertStringNotContainsString('oninput=', $html);
    }

    public function testRequiredMetadataRemainsSeparateFromEffectiveInputConstraints(): void
    {
        $context = new ArrayContext(['required' => [
            'name' => 'Name required',
            'tags' => 'Pick tags',
            'empty' => '',
            'zero' => '0',
        ]]);
        $form = $this->helper()->bind($context);
        static::assertSame('', $form->field('empty')->state->requiredMessage);
        static::assertSame('0', $form->field('zero')->state->requiredMessage);
        static::assertNull($form->field('optional')->state->requiredMessage);
        static::assertNull($form->field('optional', ['required' => true])->state->requiredMessage);
        static::assertSame('Pick tags', $form->field('tags._ids')->state->requiredMessage);
        foreach ([['required' => false], ['disabled' => true], ['type' => 'hidden']] as $options) {
            $field = $form->field('name', $options);
            static::assertSame('Name required', $field->state->requiredMessage);
            static::assertFalse($field->state->constraints['required']);
        }

        $default = new ArrayContext(['required' => ['name' => true]]);
        static::assertSame(
            $default->getRequiredMessage('name'),
            $this->helper()->bind($default)->field('name')->state->requiredMessage,
        );
    }

    public function testOrmAssociationsAndScopedBindingsUseTheirOwnValidatorMessages(): void
    {
        $this->comments->getValidator()->notEmptyString('body', 'Write a comment');
        $this->details->getValidator()->notEmptyString('summary', 'Write a summary');
        $helper = $this->helper();
        $form = $helper->bind($this->article(), ['context' => ['table' => $this->articles]]);
        static::assertSame('Write a comment', $form->field('comments.0.body')->state->requiredMessage);
        static::assertSame('Write a summary', $form->field('details.summary')->state->requiredMessage);
        $scoped = FormContext::fromBinding($form->binding)->at('comments.0')->context;
        static::assertSame('Write a comment', $helper->bind($scoped)->field('body')->state->requiredMessage);
    }

    public function testCakeFormContextsExposeTheirExistingValidatorMessages(): void
    {
        $model = new Form();
        $model->getValidator()->notEmptyString('subject', 'Provide a subject');
        $form = $this->helper()->bind($model);
        static::assertSame('Provide a subject', $form->field('subject')->state->requiredMessage);
        static::assertTrue($form->field('subject')->state->constraints['required']);
    }

    public function testInspectingMessagesDoesNotRegisterInputsOrUnlockUnrenderedFields(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(new ArrayContext(['required' => ['unrendered' => 'Required text']]));
        static::assertSame('Required text', $form->field('unrendered')->state->requiredMessage);
        $html = $form->open() . $form->control('visible') . $form->close();
        static::assertStringNotContainsString('Required text', $html);
        static::assertStringNotContainsString('data-required-message', $html);
        $xpath = $this->xpath($html);
        $tokens = [];
        foreach (['fields', 'unlocked', 'debug'] as $key) {
            $value = $xpath->evaluate('string(//input[@name="_Token[' . $key . ']"]/@value)');
            static::assertIsString($value);
            $tokens[$key] = $value;
        }

        $payload = ['visible' => 'OK', '_Token' => $tokens];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($payload, '/articles/save', $session));
        static::assertFalse(new FormProtector()->validate(
            $payload + ['unrendered' => 'Injected'],
            '/articles/save',
            $session,
        ));
    }
}
