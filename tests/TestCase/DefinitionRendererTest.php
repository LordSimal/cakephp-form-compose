<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\View\Form\ArrayContext;
use FormCompose\Attribute\Field;
use FormCompose\DefinitionRenderer;
use FormCompose\Field\FieldDefinition;
use FormCompose\Form\InputRegistry;
use FormCompose\FormDefinition;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\Theme;
use FormCompose\RepeatedDefinition;
use FormCompose\Security\FormProtection;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Composition\AuthorForm;
use InvalidArgumentException;
use LogicException;

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
        $form->renderDefinition(new FormDefinition([]), options: [
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
        $form->renderDefinition($definition);
    }

    public function testDefinitionTemplateDataMustBeAnArray(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $this->expectException(InvalidArgumentException::class);
        $form->renderDefinition(new FormDefinition([]), options: ['templateData' => 'invalid']);
    }

    public function testRepeatedRenderingRequiresCompiledRows(): void
    {
        $helper = $this->helper();
        $form = $helper->bind();
        $renderer = new DefinitionRenderer(new RenderContext(new PhpRenderer($helper->getView()), Theme::base()));
        $inputs = new InputRegistry(new FormProtection($form->binding->request));
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Repeated embeds must be compiled before rendering.');
        $renderer->renderMany($form, new RepeatedDefinition('comments', new FormDefinition([])), [], $inputs);
    }

    public function testRepeatedRenderingOnlyRendersMountedRowsFromCompiledDefinitions(): void
    {
        $row = new FormDefinition([])->add(new FieldDefinition('body'));
        $rows = new FormDefinition([])->add(new FieldDefinition('unmounted'))->embed('2', $row);
        $form = $this->helper(['comments' => [2 => ['body' => 'Mounted']]])->bind();
        $form->open();

        $html = $form->renderRepeated('comments', new RepeatedDefinition('comments', $row, keys: [2], rows: $rows));
        static::assertStringContainsString('name="comments[2][body]"', $html);
        static::assertStringNotContainsString('unmounted', $html);
        static::assertStringContainsString('name="comments[unmounted]"', $form->control('comments.unmounted'));
        $form->close();
    }
}
