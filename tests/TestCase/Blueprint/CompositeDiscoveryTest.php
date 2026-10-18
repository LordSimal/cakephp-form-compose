<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Container\Container;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Form\Field\SingleInputType;
use FormCompose\Test\BlueprintTestCase;
use FormCompose\Test\Fixture\Blueprint\Lazy\TypeProbe;
use FormCompose\Test\Fixture\Widget\DateRangeType;
use FormCompose\Test\Fixture\Widget\DependencyRangeType;
use LogicException;

/**
 * Exercises attribute discovery and dependency injection for modern custom field bases.
 */
class CompositeDiscoveryTest extends BlueprintTestCase
{
    /**
     * Discover only the custom widget fixtures for this test collection.
     */
    protected function setUp(): void
    {
        parent::setUp();
        AttributeResolver::drop('testBlueprints');
        AttributeResolver::setConfig('testBlueprints', ['paths' => ['tests/Fixture/Widget/*.php'], 'cache' => false]);
    }

    public function testCompositeMetadataDoesNotConstructUnusedTypesAndChildrenResolveOnce(): void
    {
        $probe = new TypeProbe();
        $container = new Container();
        $container->addShared(TypeProbe::class, $probe);

        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory($container, 'testBlueprints'));

        $form = $helper->form()->bind();
        $field = $form->field('period')->type('dependency-range')->resolve();
        static::assertTrue($field->state->grouped());
        static::assertSame([], $field->attributes('label')->values);
        static::assertSame(0, $probe->count());
        static::assertSame('period[start]', $field->inputs['start']->state->name);
        static::assertSame(1, $probe->count());
        $html = $form->open() . $field->control() . $form->close();
        static::assertStringContainsString('type="date"', $html);
        static::assertSame(1, $probe->count());
        static::assertSame($field->inputs['start']->state->id, $field->focusTarget);
    }

    public function testExplicitInstancesOverrideDiscoveredCompositeServices(): void
    {
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory(collection: 'testBlueprints'));

        $form = $helper->form()
            ->types(['dependency-range' => new DateRangeType()])
            ->bind();
        $html =
            $form->open()
            . $form->control('period')->type('dependency-range')->render()
            . $form->close();
        static::assertStringContainsString('name="period[end]"', $html);
    }

    public function testMissingCompositeServicesAreOnlyRequiredWhenChildrenAreRequested(): void
    {
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory(collection: 'testBlueprints'));

        $field = $helper->form()
            ->bind()
            ->field('period')
            ->type('dependency-range')
            ->resolve();
        static::assertTrue($field->state->grouped());
        $this->expectException(LogicException::class);
        static::assertNotEmpty($field->inputs);
    }

    public function testDiscoveredScalarWidgetUsesItsOwnElement(): void
    {
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory(collection: 'testBlueprints'));

        $form = $helper->form()->bind();
        $html =
            $form->open()
            . $form->control('body')->type('markdown')->value('Hello')->render()
            . $form->close();
        static::assertStringContainsString('<textarea', $html);
        static::assertStringContainsString('Hello</textarea>', $html);
    }

    public function testContainerSubstitutionsMustPreserveDeclaredCompositeSemantics(): void
    {
        $container = new Container();
        $container->add(DependencyRangeType::class, static fn(): SingleInputType => new SingleInputType());

        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory($container, 'testBlueprints'));

        $form = $helper->form()->bind();
        $form->open();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('preserve its declared composite contract');
        $form->control('period')->type('dependency-range')->render();
    }
}
