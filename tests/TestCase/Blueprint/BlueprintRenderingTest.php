<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\ContactFormHelper;
use FormCompose\Test\FormTestCase;
use LogicException;

/**
 * Exercises internal compiled configuration through the shared rendering lifecycle.
 */
class BlueprintRenderingTest extends FormTestCase
{
    public function testCollectionsRenderAtTheirCallSitesAlongsideOrdinaryControls(): void
    {
        $contact = new FormDefinition([])->add(new FieldDefinition('name'))
            ->add(new FieldDefinition('email', FieldConfig::builder()->type('email')->config()));
        $details = new FormDefinition([])->add(new FieldDefinition('notes'));
        $form = $this->helper()->form()->bind();
        $html = $form->open() . $form->control('company')->render();
        $html .= $form->renderDefinition($contact);
        $html .= $form->control('phone')->render();
        $html .= $form->renderDefinition($details);
        $html .= $form->submit('Save')->render() . $form->close();

        $matches = [];
        preg_match_all('/<input[^>]* name="([^"]+)"/', $html, $matches);
        static::assertSame(['company', 'name', 'email', 'phone', 'notes'], $matches[1]);
        static::assertSame(1, substr_count($html, '<form '));
        static::assertSame(1, substr_count($html, '</form>'));
    }

    public function testReuseResolvesEachSessionsDataWithoutLeakingOptions(): void
    {
        $contact = new FormDefinition([])->add(
            new FieldDefinition('email', FieldConfig::builder()->type('email')->label('Account email')->config()),
        );
        $helper = $this->helper();
        $first = $helper->form(new ArrayContext(['data' => ['email' => 'first@example.test']]))->bind();
        $second = $helper->form(new ArrayContext(['data' => ['email' => 'second@example.test']]))->bind();
        $first->open();
        $firstHtml = $first->renderDefinition($contact->with(
            'email',
            FieldConfig::builder()->label('Work email')->config(),
        ));
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
            $first->field($contact->field('email')->path)
                ->apply($contact->field('email')->config)
                ->resolve()
                ->state
                ->id,
            $second->field($contact->field('email')->path)
                ->apply($contact->field('email')->config)
                ->resolve()
                ->state
                ->id,
        );
        static::assertSame('email', $first->field('email')->resolve()->state->type);
        static::assertNull($first->field('email')->resolve()->state->config->type);
        static::assertNull($second->field('email')->resolve()->state->config->type);
    }

    public function testSelectedFieldsSupportManualCompositionAndInlineOverrides(): void
    {
        $contact = new FormDefinition([])->add(new FieldDefinition('name'))
            ->add(
                new FieldDefinition(
                    'email',
                    FieldConfig::builder()->type('email')->label('Email')->help('<Work address>')->config(),
                ),
            );
        $form = $this->helper(['email' => 'posted@example.test'])->form()->bind();
        $email = $form->field($contact->field('email')->path)
            ->apply($contact->field('email')->config->merge(FieldConfig::builder()->label('Contact address')->config()))
            ->resolve();
        $form->open();
        $html =
            $form->control($contact->field('name')->path)->apply($contact->field('name')->config)->render()
            . $form->control('phone')->render();
        $html .=
            $email->label()->render()
            . $email->input()->render()
            . $email->help()->render()
            . $email->errors()->render();
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
        $form = $helper->form()
            ->theme(new Theme(layers: ['themes/application']))
            ->bind();
        $definition = new FormDefinition([])->add(
            new FieldDefinition('website', FieldConfig::builder()->variant('compact')->config()),
        );
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
        $form = $this->helper()->form()->bind();
        $form->open();
        static::assertSame('', $form->renderDefinition(new FormDefinition([])));
        static::assertStringContainsString('name="name"', $form->control('name')->render());
        $form->close();
    }

    public function testDefinitionsRequireAnOpenFormEvenWhenEmpty(): void
    {
        $this->expectException(LogicException::class);
        $this->helper()->form()->bind()->renderDefinition(new FormDefinition([]));
    }

    public function testDefinitionsCannotRenderAfterClosing(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $form->close();
        $this->expectException(LogicException::class);
        $form->renderDefinition(new FormDefinition([])->add(new FieldDefinition('email')));
    }

    public function testRepeatedInputsAcrossDefinitionsAreRejected(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $form->renderDefinition(new FormDefinition([])->add(new FieldDefinition('email')));
        $this->expectException(LogicException::class);
        $form->renderDefinition(new FormDefinition([new FieldDefinition(
            'email',
            FieldConfig::builder()->type('email')->config(),
        )]));
    }
}
