<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use ArrayIterator;
use Cake\View\Form\ArrayContext;
use FormCompose\DefinitionView;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;
use FormCompose\RepeatedDefinition;
use FormCompose\Test\Fixture\Composition\CustomCompositionHelper;
use FormCompose\Test\Fixture\Repeat\CommentForm;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;

/**
 * Verifies row layouts, nested repetition, and normal service extension boundaries.
 */
class RepeatedRenderingTest extends FormTestCase
{
    public function testNestedRepeatedDefinitionsKeepAbsoluteNamesAndRelativeViews(): void
    {
        $child = new FormDefinition([])->add(new FieldDefinition('body'));
        $parent = new FormDefinition([])->add(new RepeatedDefinition('comments', $child));
        $definition = new FormDefinition([])->add(new RepeatedDefinition('posts', $parent));
        $form = $this->bindCompiled($definition, helper: $this->helper([
            'posts' => ['draft' => ['comments' => [3 => ['body' => 'Nested']]]],
        ]));
        static::assertTrue($form->binding->definition?->has('posts.draft.comments.3.body'));
        static::assertStringContainsString('name="posts[draft][comments][3][body]"', $form->render());
    }

    public function testQuerySourcesAndContextIterablesPreserveKeys(): void
    {
        $helper = $this->helper();
        $helper->getView()->setRequest($helper->getView()
            ->getRequest()
            ->withQueryParams(['comments' => [4 => ['id' => 4, 'body' => 'Query']]]));
        $form = $helper->bind(null, ['valueSources' => ['query']]);
        $form->open();
        static::assertStringContainsString('Query', $form->embedMany('comments', CommentForm::class));
        $form->close();
        $other = $this->helper()->bind(new ArrayContext(['defaults' => ['comments' => new ArrayIterator(['new' => [
            'body' => 'Iterable',
        ]])]]));
        $other->open();
        static::assertStringContainsString('comments[new][body]', $other->embedMany('comments', CommentForm::class));
        $other->close();
    }

    public function testCustomDefinitionViewsAreUsedForRepeatedRows(): void
    {
        $helper = new CustomCompositionHelper($this->helper(['comments' => [0 => ['body' => 'Value']]])->getView());
        $form = $helper->bind();
        $form->open();
        static::assertStringContainsString('Application layout field', $form->embedMany(
            'comments',
            CommentForm::class,
        ));
        $form->close();
    }

    public function testConfiguredOccurrenceTemplateCanBeOverriddenWithoutChangingChildLayout(): void
    {
        $child = new FormDefinition([])->add(new FieldDefinition('body'));
        $definition = new FormDefinition([])->embed('author', $child, ['template' => 'composition/layout']);
        $form = $this->bindCompiled($definition, helper: $this->helper());
        static::assertNotNull($form->binding->definition);
        $view = new DefinitionView($form, $form->binding->definition);
        $form->open();
        $html = $view->embedded('author', ['templates' => ['control' => 'repeat/post-wrapper']]);
        static::assertStringContainsString('post-wrapper', $html);
        static::assertNotNull($form->binding->definition);
        static::assertArrayNotHasKey('template', $form->binding->definition->embedded('author')->options);
        $form->close();
    }

    public function testUnknownRepeatedMountIsRejected(): void
    {
        $form = $this->helper()->bind();
        $view = new DefinitionView($form, new FormDefinition([]));
        $this->expectException(InvalidArgumentException::class);
        $view->embeddedMany('missing');
    }

    public function testAnExplicitlyRemovedRepeatProducesNoMarkup(): void
    {
        $form = $this->helper()->bind();
        $definition = new FormDefinition([])->add(new RepeatedDefinition('comments', new FormDefinition([])))->remove(
            'comments',
        );
        static::assertSame('', new DefinitionView($form, $definition)->embeddedMany('comments'));
    }

    public function testInferredInvalidKeysFailBeforeInputRegistration(): void
    {
        $form = $this->helper(['comments' => ['bad.key' => []]])->bind();
        $form->open();
        try {
            $form->embedMany('comments', CommentForm::class);
            static::fail('Expected invalid inferred keys to fail.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('valid field-path segments', $invalidArgumentException->getMessage());
        }

        static::assertStringContainsString('name="comments"', $form->control('comments', ['value' => 'Retry']));
        $form->close();
    }
}
