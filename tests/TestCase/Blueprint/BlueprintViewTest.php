<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use FormCompose\Blueprint\BlueprintView;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Blueprint\RepeatedDefinition;
use FormCompose\Builder\ControlBuilder;
use FormCompose\Builder\FieldConfigBuilder;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Form\Field\FieldSpec;
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
        $form = $this->helper()->form()->bind();
        $form->open();

        $view = new BlueprintView($form, $definition, 'author');
        $html = $view->controls([
            new FieldSpec('first_name', FieldConfig::builder()->label('Given <name>')->config()),
            new FieldSpec('last_name', FieldConfig::builder()->label('Family name')->config()),
            'internal',
        ])->render();
        static::assertStringContainsString('name="author[first_name]"', $html);
        static::assertStringContainsString('name="author[last_name]"', $html);
        static::assertStringContainsString('id="form-compose-1-author.first_name"', $html);
        static::assertStringContainsString('Given &lt;name&gt;', $html);
        static::assertStringContainsString('Family name', $html);
        static::assertStringContainsString('Use &lt;given&gt; name.', $html);
        static::assertStringNotContainsString('internal', $html);
        static::assertLessThan(strpos($html, 'name="author[last_name]"'), strpos($html, 'name="author[first_name]"'));
        static::assertSame('', $view->controls([])->render());
        static::assertSame('', $view->controls(['internal'])->render());
        $form->close();
    }

    public function testUnknownLayoutFieldsRemainErrors(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();

        $view = new BlueprintView($form, new FormDefinition([]));
        $this->expectException(InvalidArgumentException::class);
        $view->controls(['typo'])->render();
    }

    public function testLayoutBatchesValidateAllSyntaxBeforeRendering(): void
    {
        $definition = new FormDefinition([])->add(new FieldDefinition('name'));
        $form = $this->helper()->form()->bind();
        $form->open();

        $view = new BlueprintView($form, $definition);
        try {
            $view->controls(['name', false])->render();
            static::fail('Expected invalid syntax to fail before rendering.');
        } catch (InvalidArgumentException) {
            static::assertStringContainsString('name="name"', $view->control('name')->render());
        }

        $form->close();
    }

    public function testLayoutBatchesUseOverriddenControlMethods(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();

        $view = new class($form, new FormDefinition([])->add(new FieldDefinition('name'))) extends BlueprintView {
            /**
             * Decorate controls through the public extension point.
             *
             */
            public function control(string $field, FieldConfig|FieldConfigBuilder|null $config = null): ControlBuilder
            {
                $base = parent::control($field, $config);

                return new class(
                    static fn(FieldConfig $settings): BoundField => $base->apply($settings)->resolve(),
                    $base->config(),
                    $base->owner,
                ) extends ControlBuilder {
                    /** Decorate the normal rendering boundary. */
                    public function render(): string
                    {
                        return '<section data-layout-batch>' . parent::render() . '</section>';
                    }
                };
            }
        };
        static::assertStringContainsString('data-layout-batch', $view->controls(['name'])->render());
        $form->close();
    }

    public function testCompiledRowSelectionCannotBeChangedByLayoutPresentation(): void
    {
        $rows = new FormDefinition([])->embed('2', new FormDefinition([new FieldDefinition('body')]));
        $definition = new FormDefinition([new RepeatedDefinition('comments', 'comment', [2], rows: $rows)]);
        $form = $this->helper()->form()->bind();
        $view = new BlueprintView($form, $definition);
        $form->open();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Select repeated row keys in the blueprint declaration before binding.');
        try {
            $view->embeddedMany('comments')->keys([7])->render();
        } finally {
            static::assertStringContainsString('comments[2][body]', $view->embeddedMany('comments')->render());
            $form->close();
        }
    }
}
