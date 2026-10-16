<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\Container\Container;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Authoring\AuthorNameForm;
use FormCompose\Test\Fixture\Blueprint\CustomBlueprintFactory;
use FormCompose\Test\Fixture\Composition\CustomCompositionHelper;
use FormCompose\Test\Fixture\Composition\DynamicPostForm;

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
}
