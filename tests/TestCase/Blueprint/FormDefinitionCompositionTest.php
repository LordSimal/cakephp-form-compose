<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Blueprint\BlueprintView;
use FormCompose\Blueprint\FormContext;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Blueprint\RepeatedDefinition;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Test\Fixture\Authoring\EmailForm;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;

/**
 * Verifies immutable tree edits and conditional layout membership.
 */
class FormDefinitionCompositionTest extends FormTestCase
{
    public function testCompiledSnapshotsKeepRemovalAndBindingDefaultsIsolated(): void
    {
        $original = new FormDefinition([
            new FieldDefinition(
                'email',
                FieldConfig::builder()->type('email')->label('Email'),
            ),
            new FieldDefinition('internal_note'),
        ]);
        $selected = $original->remove('internal_note');
        $form = $this->helper()->form()->bind();
        $view = new BlueprintView($form, $selected);
        static::assertTrue($original->has('internal_note'));
        static::assertFalse($selected->has('internal_note'));
        static::assertSame('email', $view->field('email')->resolve()->state->type);
        $html = $form->open() . $view->render() . $form->close();
        static::assertStringNotContainsString('name="internal_note"', $html);
    }

    public function testNestedOverridesAndRemovalPreserveBothViewsOfTheDefinition(): void
    {
        $child = new FormDefinition([])->add(
            new FieldDefinition('email', FieldConfig::builder()->type('email')),
        )->add(new FieldDefinition('internal'));
        $original = new FormDefinition([])->embed('post', new FormDefinition([])->embed('author', $child));
        $edited = $original->with('post.author.email', FieldConfig::builder()->label('Contact'))
            ->remove('post.author.internal');
        static::assertSame(['post.author.email'], array_column($edited->fields, 'path'));
        static::assertSame('Contact', $edited->embedded(path: 'post.author')->field('email')->config->label);
        static::assertFalse($edited->embedded(path: 'post')->has('author.internal'));
        static::assertTrue($original->has('post.author.internal'));
        static::assertTrue($edited->embedded(path: 'post.author')->index->wasRemoved('internal'));
        static::assertTrue($edited->index->wasRemoved('post.author.internal'));
        static::assertSame('email', $original->field('post.author.email')->config->type);
    }

    public function testWholeEmbedRemovalRetainsDeepRemovalHistory(): void
    {
        $child = new FormDefinition([])->add(new FieldDefinition('email'))
            ->add(new FieldDefinition('internal'))
            ->remove('internal');
        $original = new FormDefinition([])->embed('post', new FormDefinition([])->embed('author', $child));
        $removed = $original->remove('post');
        $view = new BlueprintView($this->helper()->form()->bind(), $removed);
        static::assertSame([], $removed->fields);
        static::assertFalse($view->has('post.author.email'));
        static::assertSame('', $view->embedded(path: 'post')->render());
        static::assertSame('', $view->control('post.author.email')->render());
        static::assertSame('', $view->control('post.author.internal')->render());
        static::assertSame('', $view->embedded(path: 'post.author')->render());
        static::assertTrue($original->has('post.author.email'));
    }

    public function testReaddingAFieldMakesItAvailableAgain(): void
    {
        $definition = new FormDefinition([])->add(new FieldDefinition('email'))
            ->remove('email')
            ->add(new FieldDefinition('email', FieldConfig::builder()->type('email')));
        $form = $this->helper()->form()->bind();
        $view = new BlueprintView($form, $definition);
        static::assertTrue($view->has('email'));
        $html = $form->open() . $view->control('email')->render() . $form->close();
        static::assertStringContainsString('type="email"', $html);
    }

    public function testConditionalPartsRequireAvailableFields(): void
    {
        $definition = new FormDefinition([])->add(new FieldDefinition('email'))->remove('email');
        $view = new BlueprintView($this->helper()->form()->bind(), $definition);
        static::assertFalse($view->has('email'));
        static::assertFalse($view->has('typo'));
        static::assertSame('', $view->control('email')->render());
        $this->expectException(InvalidArgumentException::class);
        $view->field('email')->resolve();
    }

    public function testUnknownControlsAreNotSilentlyHidden(): void
    {
        $view = new BlueprintView($this->helper()->form()->bind(), new FormDefinition([]));
        $this->expectException(InvalidArgumentException::class);
        $view->control('typo')->render();
    }

    public function testUnknownEmbedsFailClearly(): void
    {
        $view = new BlueprintView($this->helper()->form()->bind(), new FormDefinition([]));
        $this->expectException(InvalidArgumentException::class);
        $view->embedded(path: 'typo')->render();
    }

    public function testTreeEditsAllowNonOverlappingExplicitDotPaths(): void
    {
        $child = new FormDefinition([])->add(new FieldDefinition('email'));
        $definition = new FormDefinition([])->embed('author', $child)->add(new FieldDefinition('author.nickname'));
        $edited = $definition->with('author.nickname', FieldConfig::builder()->label('Nickname'))
            ->remove('author.email');
        static::assertSame('Nickname', $edited->field('author.nickname')->config->label);
        static::assertTrue($edited->embedded(path: 'author')->index->wasRemoved('email'));
        static::assertSame(['author.nickname'], array_column($edited->fields, 'path'));
    }

    public function testBuilderSnapshotsPreservePresentationAndResolvedEmbeds(): void
    {
        $factory = new BlueprintFactory();
        $context = new FormContext(new ArrayContext([]), $this->helper()->getView()->getRequest());
        $builder = new BlueprintBuilder(
            new FormDefinition([]),
            static fn(string $path, string $identifier): FormDefinition => $factory->compile(
                $identifier,
                $context->at($path),
            ),
            static fn(RepeatedDefinition $entry): RepeatedDefinition => $factory->repeated($entry, $context),
        );
        $original = $builder->build();
        $builder->embed('author', EmailForm::class);
        $builder->template('composition/parts')->templateData(['marker' => 'local']);

        $snapshot = $builder->build();
        $builder->field('author.email')->label('Changed');
        static::assertFalse($original->has('author'));
        static::assertSame('Default email', $snapshot->field('author.email')->config->label);
        static::assertSame('composition/parts', $snapshot->presentation->templates['control']);
        static::assertSame(['marker' => 'local'], $snapshot->presentation->templateData);
    }

    public function testRemovingUnknownPathsFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FormDefinition([])->remove('typo');
    }
}
