<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use ArrayObject;
use Cake\Container\Container;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Authoring\AuthorNameForm;
use FormCompose\Test\Fixture\Authoring\BodyForm;
use FormCompose\Test\Fixture\Authoring\CommentsForm;
use FormCompose\Test\Fixture\Authoring\RuntimeCommentsForm;
use FormCompose\Test\Fixture\Blueprint\CustomBlueprintFactory;
use FormCompose\Test\Fixture\Composition\CustomCompositionHelper;
use FormCompose\Test\Fixture\Composition\DynamicPostForm;
use FormCompose\Test\Fixture\Repeat\CommentForm;
use InvalidArgumentException;

/**
 * Ensures nested compilation and layout behavior remain extensible at public entry points.
 */
class DefinitionExtensionTest extends CompositionTestCase
{
    public function testApplicationDefinitionRendererWorksThroughOrdinaryBinding(): void
    {
        $helper = new CustomCompositionHelper($this->helper()->getView());
        $form = $helper->bind(definition: AuthorNameForm::class);
        $html = $form->open() . $form->definition() . $form->close();
        static::assertStringContainsString('name="author[name]"', $html);
        static::assertStringContainsString('Application layout field', $html);
    }

    public function testCustomCompilersAndBuildersRetainNestedFactoryResolution(): void
    {
        $helper = $this->helper();
        $helper->setConfig(
            'blueprintFactory',
            new CustomBlueprintFactory(new Container(), 'testBlueprints'),
        );
        $form = $helper->bind(definition: DynamicPostForm::class);
        $html = $form->open() . $form->definition() . $form->close();
        static::assertStringContainsString('Runtime &lt;field&gt;', $html);
        static::assertStringContainsString('id="form-compose-1-body-help"', $html);
        static::assertStringContainsString('aria-describedby="form-compose-1-body-help"', $html);
        static::assertStringContainsString('name="custom"', $html);
        static::assertStringContainsString('name="author[first_name]"', $html);
        static::assertNotNull($form->binding->definition);
        static::assertSame(
            'Custom builder',
            $form->binding->definition->field('comments.0.author.custom')->options['label'],
        );
    }

    public function testRepeatedCompilerOverridesApplyToBlueprintsAndSessionOccurrences(): void
    {
        $helper = $this->helper([
            'comments' => [0 => ['body' => 'Skipped'], 2 => ['body' => 'Selected']],
        ], ['rowKeys' => [2]]);
        $helper->setConfig('blueprintFactory', new CustomBlueprintFactory());

        $form = $helper->bind(definition: CommentsForm::class);
        $html = $form->render();
        static::assertStringContainsString('name="comments[2][body]"', $html);
        static::assertStringNotContainsString('comments[0]', $html);

        $runtime = $helper->bind(definition: RuntimeCommentsForm::class);
        $runtimeHtml = $runtime->render();
        static::assertStringContainsString('name="comments[2][body]"', $runtimeHtml);
        static::assertStringNotContainsString('comments[0]', $runtimeHtml);

        $session = $helper->bind();
        $session->open();

        $selected = $session->embedMany('comments', BodyForm::class);
        static::assertStringContainsString('Selected', $selected);
        static::assertStringNotContainsString('Skipped', $selected);
        $session->close();

        $explicit = $helper->bind();
        $explicit->open();
        static::assertStringContainsString('Skipped', $explicit->embedMany(
            'comments',
            BodyForm::class,
            keys: [0],
        ));
        $explicit->close();

        $helper->getView()->setRequest($helper->getView()->getRequest()->withAttribute('rowKeys', null));
        $inferred = $helper->bind();
        $inferred->open();

        $all = $inferred->embedMany('comments', BodyForm::class);
        static::assertStringContainsString('Skipped', $all);
        static::assertStringContainsString('Selected', $all);
        $inferred->close();
    }

    public function testInvalidCustomKeysFailBeforeAnyRowHooksRun(): void
    {
        $calls = new ArrayObject();
        $helper = $this->helper(attributes: ['rowKeys' => ['bad.key'], 'repeatCalls' => $calls]);
        $helper->setConfig('blueprintFactory', new CustomBlueprintFactory());

        $form = $helper->bind();
        $form->open();
        try {
            $form->embedMany('comments', CommentForm::class);
            static::fail('Expected invalid inferred keys to fail before hooks.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('valid field-path segments', $invalidArgumentException->getMessage());
        }

        static::assertSame([], $calls->getArrayCopy());
        static::assertStringContainsString('name="comments"', $form->control('comments'));
        $form->close();
    }
}
