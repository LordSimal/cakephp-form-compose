<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\View\Form\ArrayContext;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\ContactFormHelper;
use FormCompose\Test\FormTestCase;
use LogicException;

/**
 * Exercises internal compiled configuration through the shared rendering lifecycle.
 */
class DefinitionRenderingTest extends FormTestCase
{
    public function testCollectionsRenderAtTheirCallSitesAlongsideOrdinaryControls(): void
    {
        $contact = new FormDefinition([])->add(new FieldDefinition('name'))
            ->add(new FieldDefinition('email', ['type' => 'email']));
        $details = new FormDefinition([])->add(new FieldDefinition('notes'));
        $form = $this->helper()->bind();
        $html = $form->open() . $form->control('company');
        $html .= $form->renderDefinition($contact);
        $html .= $form->control('phone');
        $html .= $form->renderDefinition($details);
        $html .= $form->submit('Save') . $form->close();

        $matches = [];
        preg_match_all('/<input[^>]* name="([^"]+)"/', $html, $matches);
        static::assertSame(['company', 'name', 'email', 'phone', 'notes'], $matches[1]);
        static::assertSame(1, substr_count($html, '<form '));
        static::assertSame(1, substr_count($html, '</form>'));
    }

    public function testReuseResolvesEachSessionsDataWithoutLeakingOptions(): void
    {
        $contact = new FormDefinition([])->add(new FieldDefinition('email', [
            'type' => 'email',
            'label' => 'Account email',
        ]));
        $helper = $this->helper();
        $first = $helper->bind(new ArrayContext(['data' => ['email' => 'first@example.test']]));
        $second = $helper->bind(new ArrayContext(['data' => ['email' => 'second@example.test']]));
        $first->open();
        $firstHtml = $first->renderDefinition($contact->with('email', ['label' => 'Work email']));
        $first->close();
        $second->open();
        $secondHtml = $second->renderDefinition($contact);
        $second->close();

        static::assertStringContainsString('Work email', $firstHtml);
        static::assertStringContainsString('value="first@example.test"', $firstHtml);
        static::assertStringContainsString('Account email', $secondHtml);
        static::assertStringContainsString('value="second@example.test"', $secondHtml);
        static::assertStringNotContainsString('first@example.test', $secondHtml);
        static::assertNotSame(
            $first->field($contact->field('email')->path, $contact->field('email')->options)->state->id,
            $second->field($contact->field('email')->path, $contact->field('email')->options)->state->id,
        );
        static::assertSame('email', $first->field('email')->state->type);
        static::assertSame([], $first->field('email')->state->options);
        static::assertSame([], $second->field('email')->state->options);
    }

    public function testSelectedFieldsSupportManualCompositionAndInlineOverrides(): void
    {
        $contact = new FormDefinition([])->add(new FieldDefinition('name'))
            ->add(new FieldDefinition('email', ['type' => 'email', 'label' => 'Email', 'help' => '<Work address>']));
        $form = $this->helper(['email' => 'posted@example.test'])->bind();
        $email = $form->field($contact->field('email')->path, array_replace($contact->field('email')->options, [
            'label' => 'Contact address',
        ]));
        $form->open();
        $html =
            $form->control($contact->field('name')->path, $contact->field('name')->options) . $form->control('phone');
        $html .= $email->label() . $email->input() . $email->help() . $email->errors();
        $form->close();

        static::assertStringContainsString('Contact address', $html);
        static::assertStringContainsString('type="email"', $html);
        static::assertStringContainsString('value="posted@example.test"', $html);
        static::assertStringContainsString('&lt;Work address&gt;', $html);
        static::assertStringContainsString('aria-describedby="' . $email->state->id . '-help"', $html);
        static::assertStringContainsString('for="' . $email->state->id . '"', $html);
    }

    public function testDefinitionsUseTheNormalThemeAndResolverExtensionPoints(): void
    {
        $helper = new ContactFormHelper($this->helper()->getView());
        $form = $helper->bind(theme: new Theme(layers: ['themes/application']));
        $definition = new FormDefinition([])->add(new FieldDefinition('website', ['variant' => 'compact']));
        $form->open();
        $html = $form->renderDefinition($definition);
        $form->close();

        static::assertStringContainsString('data-variant="compact"', $html);
        static::assertStringContainsString('data-contact-label', $html);
        static::assertStringContainsString('type="url"', $html);
        static::assertStringContainsString('value="https://example.test"', $html);
    }

    public function testEmptyDefinitionsRenderNothingInsideAnOpenForm(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        static::assertSame('', $form->renderDefinition(new FormDefinition([])));
        static::assertStringContainsString('name="name"', $form->control('name'));
        $form->close();
    }

    public function testDefinitionsRequireAnOpenFormEvenWhenEmpty(): void
    {
        $this->expectException(LogicException::class);
        $this->helper()->bind()->renderDefinition(new FormDefinition([]));
    }

    public function testDefinitionsCannotRenderAfterClosing(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $form->close();
        $this->expectException(LogicException::class);
        $form->renderDefinition(new FormDefinition([])->add(new FieldDefinition('email')));
    }

    public function testRepeatedInputsAcrossDefinitionsAreRejected(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $form->renderDefinition(new FormDefinition([])->add(new FieldDefinition('email')));
        $this->expectException(LogicException::class);
        $form->renderDefinition(new FormDefinition([new FieldDefinition('email', ['type' => 'email'])]));
    }
}
