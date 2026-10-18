<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use ArrayIterator;
use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\BlueprintView;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Blueprint\RepeatedDefinition;
use FormCompose\Test\Fixture\Authoring\BodyOccurrenceForm;
use FormCompose\Test\Fixture\Authoring\EmptyForm;
use FormCompose\Test\Fixture\Authoring\NestedPostsForm;
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
        $form = $this->helper([
            'posts' => ['draft' => ['comments' => [3 => ['body' => 'Nested']]]],
        ])
            ->form()
            ->blueprint(NestedPostsForm::class)
            ->bind();
        static::assertTrue($form->binding->definition?->has('posts.draft.comments.3.body'));
        static::assertStringContainsString('name="posts[draft][comments][3][body]"', $form->render());
    }

    public function testQuerySourcesAndContextIterablesPreserveKeys(): void
    {
        $helper = $this->helper();
        $helper->getView()->setRequest($helper->getView()
            ->getRequest()
            ->withQueryParams(['comments' => [4 => ['id' => 4, 'body' => 'Query']]]));
        $form = $helper->form()
            ->valueSources(['query'])
            ->bind();
        $form->open();
        static::assertStringContainsString(
            'Query',
            $form->embedMany(path: 'comments', blueprint: CommentForm::class)->render(),
        );
        $form->close();
        $other = $this->helper()
            ->form(new ArrayContext(['defaults' => ['comments' => new ArrayIterator(['new' => [
                'body' => 'Iterable',
            ]])]]))
            ->bind();
        $other->open();
        static::assertStringContainsString(
            'comments[new][body]',
            $other->embedMany(path: 'comments', blueprint: CommentForm::class)->render(),
        );
        $other->close();
    }

    public function testCustomBlueprintViewsAreUsedForRepeatedRows(): void
    {
        $helper = new CustomCompositionHelper($this->helper(['comments' => [0 => ['body' => 'Value']]])->getView());
        $form = $helper->form()->bind();
        $form->open();
        static::assertStringContainsString(
            'Application layout field',
            $form->embedMany(path: 'comments', blueprint: CommentForm::class)->render(),
        );
        $form->close();
    }

    public function testConfiguredOccurrenceTemplateCanBeOverriddenWithoutChangingChildLayout(): void
    {
        $form = $this->helper()->form()->blueprint(BodyOccurrenceForm::class)->bind();
        static::assertNotNull($form->binding->definition);
        $view = new BlueprintView($form, $form->binding->definition);
        $form->open();
        $html = $view->embedded(path: 'author')
            ->partTemplate('control', 'repeat/post-wrapper')
            ->render();
        static::assertStringContainsString('post-wrapper', $html);
        static::assertNotNull($form->binding->definition);
        static::assertArrayNotHasKey(
            'control',
            $form->binding->definition->embedded(path: 'author')->presentation->templates,
        );
        $form->close();
    }

    public function testUnknownRepeatedMountIsRejected(): void
    {
        $form = $this->helper()->form()->bind();
        $view = new BlueprintView($form, new FormDefinition([]));
        $this->expectException(InvalidArgumentException::class);
        $view->embeddedMany(path: 'missing')->render();
    }

    public function testAnExplicitlyRemovedRepeatProducesNoMarkup(): void
    {
        $form = $this->helper()->form()->bind();
        $definition = new FormDefinition([])->add(new RepeatedDefinition('comments', EmptyForm::class))->remove(
            'comments',
        );
        static::assertSame(
            '',
            new BlueprintView($form, $definition)->embeddedMany(path: 'comments')
                ->render(),
        );
    }

    public function testInferredInvalidKeysFailBeforeInputRegistration(): void
    {
        $form = $this->helper(['comments' => ['bad.key' => []]])
            ->form()
            ->bind();
        $form->open();
        try {
            $form->embedMany(path: 'comments', blueprint: CommentForm::class)->render();
            static::fail('Expected invalid inferred keys to fail.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('valid field-path segments', $invalidArgumentException->getMessage());
        }

        static::assertStringContainsString(
            'name="comments"',
            $form->control('comments')->value('Retry')->render(),
        );
        $form->close();
    }
}
