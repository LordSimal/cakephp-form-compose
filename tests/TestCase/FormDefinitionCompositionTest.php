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
        $child = new FormDefinition([])->add(new FieldDefinition('email', [
            'type' => 'email',
        ]))->add(new FieldDefinition('internal'));
        $original = new FormDefinition([])->embed('post', new FormDefinition([])->embed('author', $child));
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
        $child = new FormDefinition([])->add(new FieldDefinition('email'))
            ->add(new FieldDefinition('internal'))
            ->remove('internal');
        $original = new FormDefinition([])->embed('post', new FormDefinition([])->embed('author', $child));
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
        $definition = new FormDefinition([])->add(new FieldDefinition('email'))
            ->remove('email')
            ->add(new FieldDefinition('email', ['type' => 'email']));
        $form = $this->helper()->bind();
        $view = new DefinitionView($form, $definition);
        static::assertTrue($view->has('email'));
        $html = $form->open() . $view->control('email') . $form->close();
        static::assertStringContainsString('type="email"', $html);
    }

    public function testConditionalPartsRequireAvailableFields(): void
    {
        $definition = new FormDefinition([])->add(new FieldDefinition('email'))->remove('email');
        $view = new DefinitionView($this->helper()->bind(), $definition);
        static::assertFalse($view->has('email'));
        static::assertFalse($view->has('typo'));
        static::assertSame('', $view->control('email'));
        $this->expectException(InvalidArgumentException::class);
        $view->field('email');
    }

    public function testUnknownControlsAreNotSilentlyHidden(): void
    {
        $view = new DefinitionView($this->helper()->bind(), new FormDefinition([]));
        $this->expectException(InvalidArgumentException::class);
        $view->control('typo');
    }

    public function testUnknownEmbedsFailClearly(): void
    {
        $view = new DefinitionView($this->helper()->bind(), new FormDefinition([]));
        $this->expectException(InvalidArgumentException::class);
        $view->embedded('typo');
    }

    public function testTreeEditsAllowNonOverlappingExplicitDotPaths(): void
    {
        $child = new FormDefinition([])->add(new FieldDefinition('email'));
        $definition = new FormDefinition([])->embed('author', $child)->add(new FieldDefinition('author.nickname'));
        $edited = $definition->with('author.nickname', ['label' => 'Nickname'])->remove('author.email');
        static::assertSame('Nickname', $edited->field('author.nickname')->options['label']);
        static::assertTrue($edited->embedded('author')->index->wasRemoved('email'));
        static::assertSame(['author.nickname'], array_column($edited->fields, 'path'));
    }

    public function testBuilderSnapshotsPreservePresentationAndResolvedEmbeds(): void
    {
        $builder = new FormBuilder()->withResolver(static fn(
            string $mount,
            string $identifier,
        ): FormDefinition => new FormDefinition([new FieldDefinition('email')]));
        $original = $builder->build();
        $builder->embed('author', 'author-blueprint');
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
        new FormDefinition([])->remove('typo');
    }
}
