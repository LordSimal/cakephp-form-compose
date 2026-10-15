<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\View\Form\ArrayContext;
use FormCompose\Attribute\Field;
use FormCompose\FormDefinition;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Composition\AuthorForm;
use InvalidArgumentException;

/**
 * Checks field shorthand, manual parts, and definition presentation normalization.
 */
class DefinitionRendererTest extends CompositionTestCase
{
    public function testManualFieldPartsUseScopedNamesOverridesAndFeedback(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'data' => ['author' => ['email' => 'author@example.test']],
            'errors' => ['author' => ['email' => ['invalid' => '<Invalid email>']]],
        ]));
        $html = $form->open() . $form->definition(AuthorForm::class, at: 'author', options: [
            'template' => 'composition/parts',
        ]) . $form->close();
        static::assertStringContainsString('data-parts', $html);
        static::assertStringContainsString('Explicit email', $html);
        static::assertStringContainsString('name="author[email]"', $html);
        static::assertStringContainsString('value="author@example.test"', $html);
        static::assertStringContainsString('for="form-compose-1-author-email"', $html);
        static::assertStringContainsString('aria-invalid="true"', $html);
        static::assertStringContainsString('id="form-compose-1-author-email-errors"', $html);
        static::assertStringContainsString('&lt;Invalid email&gt;', $html);
        static::assertStringContainsString('&lt;Work&gt; email', $html);
    }

    public function testDefinitionTemplateConflictsFailBeforeRenderingInputs(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $this->expectException(InvalidArgumentException::class);
        $form->definition(FormDefinition::make(), options: [
            'template' => 'composition/author',
            'templates' => ['control' => 'composition/post'],
        ]);
    }

    public function testFieldTemplateConflictsStillFailThroughTheNormalPipeline(): void
    {
        $definition = new FormDefinition([new Field('email', template: 'one', templates: [
            'control' => 'two',
        ])->definition()]);
        $form = $this->helper()->bind();
        $form->open();
        $this->expectException(InvalidArgumentException::class);
        $form->definition($definition);
    }

    public function testDefinitionTemplateDataMustBeAnArray(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $this->expectException(InvalidArgumentException::class);
        $form->definition(FormDefinition::make(), options: ['templateData' => 'invalid']);
    }
}
