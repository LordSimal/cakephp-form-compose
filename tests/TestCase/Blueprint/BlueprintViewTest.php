<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use FormCompose\Blueprint\BlueprintView;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Composition\AuthorForm;
use InvalidArgumentException;

/**
 * Checks layout batches against scoped configuration and the layout's control extension point.
 */
class BlueprintViewTest extends CompositionTestCase
{
    public function testLayoutBatchesPreserveScopesOptionsOrderAndRemovedFields(): void
    {
        $definition = $this->factory->compile(AuthorForm::class, $this->context());
        $form = $this->helper()->bind();
        $form->open();

        $view = new BlueprintView($form, $definition, 'author');
        $html = $view->controls([
            'first_name' => ['label' => 'Given <name>'],
            'last_name' => ['label' => 'Family name'],
            'internal',
        ]);
        static::assertStringContainsString('name="author[first_name]"', $html);
        static::assertStringContainsString('name="author[last_name]"', $html);
        static::assertStringContainsString('id="form-compose-1-author.first_name"', $html);
        static::assertStringContainsString('Given &lt;name&gt;', $html);
        static::assertStringContainsString('Family name', $html);
        static::assertStringContainsString('Use &lt;given&gt; name.', $html);
        static::assertStringNotContainsString('internal', $html);
        static::assertLessThan(strpos($html, 'name="author[last_name]"'), strpos($html, 'name="author[first_name]"'));
        static::assertSame('', $view->controls([]));
        static::assertSame('', $view->controls(['internal']));
        $form->close();
    }

    public function testUnknownLayoutFieldsRemainErrors(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $view = new BlueprintView($form, new FormDefinition([]));
        $this->expectException(InvalidArgumentException::class);
        $view->controls(['typo']);
    }

    public function testLayoutBatchesValidateAllSyntaxBeforeRendering(): void
    {
        $definition = new FormDefinition([])->add(new FieldDefinition('name'));
        $form = $this->helper()->bind();
        $form->open();

        $view = new BlueprintView($form, $definition);
        try {
            $view->controls(['name', false]);
            static::fail('Expected invalid syntax to fail before rendering.');
        } catch (InvalidArgumentException) {
            static::assertStringContainsString('name="name"', $view->control('name'));
        }

        $form->close();
    }

    public function testLayoutBatchesUseOverriddenControlMethods(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $view = new class($form, new FormDefinition([])->add(new FieldDefinition('name'))) extends BlueprintView {
            /**
             * Decorate controls through the public extension point.
             *
             * @param array<string, mixed> $options Field options.
             */
            public function control(string $field, array $options = []): string
            {
                return '<section data-layout-batch>' . parent::control($field, $options) . '</section>';
            }
        };
        static::assertStringContainsString('data-layout-batch', $view->controls(['name']));
        $form->close();
    }
}
