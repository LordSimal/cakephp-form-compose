<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use ArrayObject;
use Cake\View\Form\ArrayContext;
use FormCompose\Test\Fixture\Repeat\CycleForm;
use FormCompose\Test\Fixture\Repeat\NestedForm;
use FormCompose\Test\Fixture\Repeat\PostForm;
use FormCompose\Test\Fixture\Repeat\RuntimeForm;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;

/**
 * Exercises attributed and runtime repeated composition through real helper bindings.
 */
class RepeatedBlueprintTest extends FormTestCase
{
    public function testRowsCompileBeforeTheParentAndRetainSparseKeys(): void
    {
        $calls = new ArrayObject();
        $context = new ArrayContext([
            'defaults' => ['comments' => [0 => ['id' => 10, 'body' => 'First'], 2 => ['id' => 12, 'body' => 'Third']]],
        ]);
        $form = $this->helper(attributes: ['repeatCalls' => $calls, 'overrideBody' => true])->bind(
            $context,
            definition: PostForm::class,
        );
        static::assertSame(['comments.0', 'comments.2'], $calls->getArrayCopy());
        static::assertNotNull($form->binding->definition);
        static::assertSame('Parent override', $form->binding->definition->field('comments.0.body')->options['label']);
        $html = $form->render();
        static::assertStringContainsString('name="comments[2][body]"', $html);
        static::assertStringNotContainsString('comments[1]', $html);
        static::assertSame(['comments.0', 'comments.2'], $calls->getArrayCopy());
    }

    public function testSubmittedRowsReplaceLoadedKeysAndChildrenReadSubmittedValues(): void
    {
        $calls = new ArrayObject();
        $context = new ArrayContext(['defaults' => ['comments' => [0 => ['id' => 10]]]]);
        $form = $this->helper(['comments' => ['new-key' => ['id' => 99, 'body' => 'Submitted']]], [
            'repeatCalls' => $calls,
        ])->bind($context, definition: PostForm::class);
        $html = $form->render();
        static::assertSame(['comments.new-key'], $calls->getArrayCopy());
        static::assertStringContainsString('Comment 99', $html);
        static::assertStringContainsString('Submitted', $html);
        static::assertStringNotContainsString('comments[0]', $html);
    }

    public function testNestedOccurrenceWrapperRendersTheOriginalChildLayout(): void
    {
        $calls = new ArrayObject();
        $form = $this->helper(['post' => ['comments' => [2 => ['id' => 4, 'body' => 'Nested']]]], [
            'repeatCalls' => $calls,
        ])->bind(definition: NestedForm::class);
        $html = $form->render();
        static::assertSame(['post.comments.2'], $calls->getArrayCopy());
        static::assertStringContainsString('class="post-wrapper"', $html);
        static::assertStringContainsString('class="comment-content"', $html);
        static::assertStringContainsString('name="post[comments][2][body]"', $html);
    }

    public function testRuntimeRowsAllowBlankKeysAndImmediateParentEdits(): void
    {
        $calls = new ArrayObject();
        $form = $this->helper(['comments' => [0 => ['id' => 5, 'body' => 'Existing']]], [
            'repeatCalls' => $calls,
        ])->bind(
            definition: RuntimeForm::class,
        );
        static::assertSame(['comments.0', 'comments.3'], $calls->getArrayCopy());
        $html = $form->render();
        static::assertStringContainsString('name="comments[3][body]"', $html);
        static::assertStringContainsString('New comment', $html);
        static::assertStringContainsString('Nested blank', $html);
        static::assertStringNotContainsString('name="comments[0][id]"', $html);
        static::assertSame(['comments.0', 'comments.3'], $calls->getArrayCopy());
    }

    public function testParentRemovalRemovesTheWholeRow(): void
    {
        $form = $this->helper(['comments' => [0 => ['body' => 'Kept'], 2 => ['body' => 'Removed']]], [
            'removeRow' => true,
        ])->bind(definition: PostForm::class);
        $html = $form->render();
        static::assertStringContainsString('Kept', $html);
        static::assertStringNotContainsString('Removed', $html);
        static::assertFalse($form->binding->definition?->has('comments.2.body'));
    }

    public function testRequestedCyclesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular blueprint embedding');
        $this->helper()->bind(definition: CycleForm::class);
    }

    public function testSourceOrderingAlsoControlsTheChildHooks(): void
    {
        $context = new ArrayContext(['defaults' => ['comments' => [2 => ['id' => 12, 'body' => 'Loaded']]]]);
        $form = $this->helper(['comments' => [0 => ['id' => 99]]])->bind(
            $context,
            ['valueSources' => ['context', 'data']],
            definition: PostForm::class,
        );
        $html = $form->render();
        static::assertStringContainsString('Comment 12', $html);
        static::assertStringContainsString('Loaded', $html);
        static::assertStringNotContainsString('comments[0]', $html);
    }
}
