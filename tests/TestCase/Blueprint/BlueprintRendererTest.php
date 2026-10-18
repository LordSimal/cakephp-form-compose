<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\BlueprintRenderer;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Blueprint\RepeatedDefinition;
use FormCompose\Configuration\EmbedConfig;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Form\InputRegistry;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\Theme;
use FormCompose\Security\FormProtection;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Authoring\BodyForm;
use FormCompose\Test\Fixture\Authoring\EmptyForm;
use FormCompose\Test\Fixture\Composition\AuthorForm;
use LogicException;
use TypeError;

/**
 * Checks field shorthand, manual parts, and definition presentation normalization.
 */
class BlueprintRendererTest extends CompositionTestCase
{
    public function testManualFieldPartsUseScopedNamesOverridesAndFeedback(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext([
                'data' => ['author' => ['email' => 'author@example.test']],
                'errors' => ['author' => ['email' => ['invalid' => '<Invalid email>']]],
            ]))
            ->bind();
        $html =
            $form->open()
            . $form->blueprint(blueprint: AuthorForm::class, at: 'author')
                ->partTemplate('control', 'composition/parts')
                ->render()
            . $form->close();
        static::assertStringContainsString('data-parts', $html);
        static::assertStringContainsString('Explicit email', $html);
        static::assertStringContainsString('name="author[email]"', $html);
        static::assertStringContainsString('value="author@example.test"', $html);
        static::assertStringContainsString('for="form-compose-1-author.email"', $html);
        static::assertStringContainsString('aria-invalid="true"', $html);
        static::assertStringContainsString('id="form-compose-1-author.email-errors"', $html);
        static::assertStringContainsString('&lt;Invalid email&gt;', $html);
        static::assertStringContainsString('&lt;Work&gt; email', $html);
    }

    public function testLayoutSelectionsUseTheCanonicalPartMap(): void
    {
        $presentation = new PresentationConfig();
        $presentation = $presentation->template('composition/author')->partTemplate('control', 'composition/post');
        static::assertSame(['control' => 'composition/post'], $presentation->templates);
    }

    public function testFieldTemplatesCanBeReplacedWithoutAlteringTheSourceConfig(): void
    {
        $original = FieldConfig::builder()->template('themes/custom/control');
        $replacement = $original->template('themes/application/control');
        static::assertSame('themes/custom/control', $original->presentation->templates['control']);
        static::assertSame('themes/application/control', $replacement->presentation->templates['control']);
    }

    public function testDefinitionTemplateDataMustBeAnArray(): void
    {
        $this->expectException(TypeError::class);
        $this->invokeBuilder(new PresentationConfig(), 'templateData', 'invalid');
    }

    public function testRepeatedRenderingRequiresCompiledRows(): void
    {
        $helper = $this->helper();
        $form = $helper->form()->bind();
        $renderer = new BlueprintRenderer(new RenderContext(new PhpRenderer($helper->getView()), Theme::base()));
        $inputs = new InputRegistry(new FormProtection($form->binding->request));
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Repeated embeds must be compiled before rendering.');
        $renderer->renderMany($form, new RepeatedDefinition('comments', EmptyForm::class), new EmbedConfig(), $inputs);
    }

    public function testRepeatedRenderingOnlyRendersMountedRowsFromCompiledDefinitions(): void
    {
        $row = new FormDefinition([])->add(new FieldDefinition('body'));
        $rows = new FormDefinition([])->add(new FieldDefinition('unmounted'))->embed('2', $row);
        $form = $this->helper(['comments' => [2 => ['body' => 'Mounted']]])
            ->form()
            ->bind();
        $form->open();

        $html = $form->renderRepeated(
            'comments',
            new RepeatedDefinition('comments', BodyForm::class, keys: [2], rows: $rows),
        );
        static::assertStringContainsString('name="comments[2][body]"', $html);
        static::assertStringNotContainsString('unmounted', $html);
        static::assertStringContainsString(
            'name="comments[unmounted]"',
            $form->control('comments.unmounted')->render(),
        );
        $form->close();
    }
}
