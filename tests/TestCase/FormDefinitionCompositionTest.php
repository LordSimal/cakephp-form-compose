<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use FormCompose\DefinitionView;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormBuilder;
use FormCompose\FormDefinition;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use LogicException;

/**
 * Verifies immutable tree edits and conditional layout membership.
 */
class FormDefinitionCompositionTest extends FormTestCase
{
    public function testNestedOverridesAndRemovalPreserveBothViewsOfTheDefinition(): void
    {
        $child = FormDefinition::make()->add(FieldDefinition::make('email', 'email'))->add(FieldDefinition::make(
            'internal',
        ));
        $original = FormDefinition::make()->embed('post', FormDefinition::make()->embed('author', $child));
        $edited = $original->with('post.author.email', ['label' => 'Contact'])->remove('post.author.internal');
        static::assertSame(['post.author.email'], array_column($edited->fields, 'path'));
        static::assertSame('Contact', $edited->embedded('post.author')->field('email')->options['label']);
        static::assertFalse($edited->embedded('post')->has('author.internal'));
        static::assertTrue($original->has('post.author.internal'));
        static::assertTrue($edited->embedded('post.author')->index->wasRemoved('internal'));
        static::assertTrue($edited->index->wasRemoved('post.author.internal'));
        static::assertSame('email', $original->field('post.author.email')->options['type']);
    }

    public function testWholeEmbedRemovalRetainsDeepRemovalHistory(): void
    {
        $child = FormDefinition::make()
            ->add(FieldDefinition::make('email'))
            ->add(FieldDefinition::make('internal'))
            ->remove('internal');
        $original = FormDefinition::make()->embed('post', FormDefinition::make()->embed('author', $child));
        $removed = $original->remove('post');
        $view = new DefinitionView($this->helper()->bind(), $removed);
        static::assertSame([], $removed->fields);
        static::assertFalse($view->has('post.author.email'));
        static::assertSame('', $view->embedded('post'));
        static::assertSame('', $view->control('post.author.email'));
        static::assertSame('', $view->control('post.author.internal'));
        static::assertSame('', $view->embedded('post.author'));
        static::assertTrue($original->has('post.author.email'));
    }

    public function testReaddingAFieldMakesItAvailableAgain(): void
    {
        $definition = FormDefinition::make()
            ->add(FieldDefinition::make('email'))
            ->remove('email')
            ->add(FieldDefinition::make('email', 'email'));
        $form = $this->helper()->bind();
        $view = new DefinitionView($form, $definition);
        static::assertTrue($view->has('email'));
        $html = $form->open() . $view->control('email') . $form->close();
        static::assertStringContainsString('type="email"', $html);
    }

    public function testConditionalPartsRequireAvailableFields(): void
    {
        $definition = FormDefinition::make()->add(FieldDefinition::make('email'))->remove('email');
        $view = new DefinitionView($this->helper()->bind(), $definition);
        static::assertFalse($view->has('email'));
        static::assertFalse($view->has('typo'));
        static::assertSame('', $view->control('email'));
        $this->expectException(InvalidArgumentException::class);
        $view->field('email');
    }

    public function testUnknownControlsAreNotSilentlyHidden(): void
    {
        $view = new DefinitionView($this->helper()->bind(), FormDefinition::make());
        $this->expectException(InvalidArgumentException::class);
        $view->control('typo');
    }

    public function testUnknownEmbedsFailClearly(): void
    {
        $view = new DefinitionView($this->helper()->bind(), FormDefinition::make());
        $this->expectException(InvalidArgumentException::class);
        $view->embedded('typo');
    }

    public function testTreeEditsAllowNonOverlappingExplicitDotPaths(): void
    {
        $child = FormDefinition::make()->add(FieldDefinition::make('email'));
        $definition = FormDefinition::make()->embed('author', $child)->add(FieldDefinition::make('author.nickname'));
        $edited = $definition->with('author.nickname', ['label' => 'Nickname'])->remove('author.email');
        static::assertSame('Nickname', $edited->field('author.nickname')->options['label']);
        static::assertTrue($edited->embedded('author')->index->wasRemoved('email'));
        static::assertSame(['author.nickname'], array_column($edited->fields, 'path'));
    }

    public function testBuilderSnapshotsPreservePresentationAndPrebuiltEmbeds(): void
    {
        $builder = new FormBuilder();
        $original = $builder->build();
        $builder->embed('author', FormDefinition::make()->add(FieldDefinition::make('email')));
        $builder->presentation(['template' => 'composition/parts', 'templateData' => ['marker' => 'local']]);

        $snapshot = $builder->build();
        $builder->with('author.email', ['label' => 'Changed']);
        static::assertFalse($original->has('author'));
        static::assertSame([], $snapshot->field('author.email')->options);
        static::assertSame('composition/parts', $snapshot->options['template']);
        static::assertSame(['marker' => 'local'], $snapshot->options['templateData']);
    }

    public function testStandaloneBuilderExplainsMissingBlueprintResolver(): void
    {
        $this->expectException(LogicException::class);
        new FormBuilder()->embed('author', 'profile');
    }

    public function testRemovingUnknownPathsFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FormDefinition::make()->remove('typo');
    }
}
