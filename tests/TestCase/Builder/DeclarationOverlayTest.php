<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Builder;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\BlueprintView;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Blueprint\RepeatedDefinition;
use FormCompose\Configuration\EmbedConfig;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;

/** Verifies applied declaration settings survive compilation and render through scoped layouts. */
class DeclarationOverlayTest extends FormTestCase
{
    public function testFieldOverlaysRetainTypeAndExtensionSettingsThroughCompilation(): void
    {
        $builder = $this->builder();
        $builder->add('email', new FieldConfig(type: 'email', label: 'Original'))
            ->apply(FieldConfig::builder()->label('Contact email'))
            ->extensionData(['integration' => 'crm', 'zero' => 0]);
        $definition = $builder->build();
        $form = $this->helper()->form()->bind();
        $view = new BlueprintView($form, $definition);
        $field = $view->field('email')->resolve();
        static::assertSame(['integration' => 'crm', 'zero' => 0], $field->state->config->extensionData);
        $html = $form->open() . $view->render() . $form->close();
        static::assertSame('email', $this->xpath($html)->evaluate('string(//input/@type)'));
        static::assertSame('Contact email', $this->xpath($html)->evaluate('string(//label)'));
    }

    public function testSingleEmbedOverlaysPreservePreviousPresentationAndChildFields(): void
    {
        $builder = $this->builder();
        $builder->embed('author', 'author')
            ->template('composition/parts')
            ->templateData(['retained' => 'yes'])
            ->apply(PresentationConfig::builder()->templateData(['added' => 'yes']));
        $definition = $builder->build();
        static::assertSame(
            ['retained' => 'yes', 'added' => 'yes'],
            $definition->index->embedPresentation['author']->templateData,
        );
        $form = $this->helper()->form()->bind();
        $html = $form->open() . new BlueprintView($form, $definition)->render() . $form->close();
        static::assertStringContainsString('data-parts', $html);
        static::assertStringContainsString('name="author[email]"', $html);
    }

    public function testRepeatedOverlaysPreserveSelectedRowsThroughCompilationAndRendering(): void
    {
        $builder = $this->builder();
        $builder->embedMany('comments', 'comment')
            ->keys([2])
            ->apply(EmbedConfig::builder()->label('Discussion')->errors(false));
        $definition = $builder->build();
        static::assertSame('Discussion', $definition->index->repeats['comments']->config->label);
        static::assertFalse($definition->index->repeats['comments']->config->errors);
        $form = $this->helper()->form()->bind();
        $html = $form->open() . new BlueprintView($form, $definition)->render() . $form->close();
        static::assertStringContainsString('name="comments[2][body]"', $html);
    }

    public function testEditingAnEmbedAsAFieldIsRejected(): void
    {
        $builder = $this->builder();
        $builder->embed('author', 'author');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot configure an embed as a field: author');
        $builder->field('author');
    }

    public function testCompiledPresentationOverlaysRenderWithoutMutatingTheOriginal(): void
    {
        $base = new FormDefinition([new FieldDefinition('email')])->presentation(
            PresentationConfig::builder()->template('composition/parts')->templateData(['original' => true]),
        );
        $derived = $base->presentation(PresentationConfig::builder()->templateData(['added' => true]));
        static::assertSame(['original' => true], $base->presentation->templateData);
        static::assertSame(['original' => true, 'added' => true], $derived->presentation->templateData);
        $form = $this->helper()->form()->bind();
        $html = $form->open() . new BlueprintView($form, $derived)->render() . $form->close();
        static::assertStringContainsString('data-parts', $html);
        static::assertStringContainsString('name="email"', $html);
    }

    /** Compile lightweight child definitions while retaining the ordinary declaration pipeline. */
    protected function builder(): BlueprintBuilder
    {
        return new BlueprintBuilder(
            new FormDefinition([]),
            static fn(string $path, string $blueprint): FormDefinition => new FormDefinition([new FieldDefinition(
                'email',
            )]),
            static fn(RepeatedDefinition $entry): RepeatedDefinition => new RepeatedDefinition(
                $entry->path,
                $entry->blueprint,
                $entry->keys,
                $entry->config,
                new FormDefinition([])->embed('2', new FormDefinition([new FieldDefinition('body')])),
            ),
        );
    }
}
