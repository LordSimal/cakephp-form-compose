<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Container\Container;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Test\BlueprintTestCase;
use FormCompose\Test\Fixture\Blueprint\Lazy\DependencyType;
use FormCompose\Test\Fixture\Blueprint\Lazy\TypeProbe;
use FormCompose\Test\Fixture\RangeType;
use LogicException;
use RuntimeException;

/**
 * Exercises real discovery while keeping unused custom-type constructors out of binding.
 */
class LazyTypesTest extends BlueprintTestCase
{
    /**
     * Discover a type whose constructor requires a real application service.
     */
    protected function setUp(): void
    {
        parent::setUp();
        AttributeResolver::drop('testBlueprints');
        AttributeResolver::setConfig('testBlueprints', [
            'paths' => ['tests/Fixture/Blueprint/Lazy/*.php'],
            'cache' => false,
        ]);
    }

    public function testUnusedDependencyTypesDoNotPreventBindingOrOrdinaryRendering(): void
    {
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory(collection: 'testBlueprints'));

        $form = $helper->form()->bind();
        $form->field('custom')
            ->type('dependency-type')
            ->resolve()
            ->attributes();
        static::assertStringContainsString(
            'name="title"',
            $form->open() . $form->control('title')->render() . $form->close(),
        );
    }

    public function testMissingDependenciesFailWhenTheTypeIsActuallyRendered(): void
    {
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory(collection: 'testBlueprints'));

        $form = $helper->form()->bind();
        $form->open();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Configure blueprintFactory');
        $form->control('custom')->type('dependency-type')->render();
    }

    public function testRequestedTypesAreReusedWithinOneBindingAndFreshAcrossBindings(): void
    {
        $probe = new TypeProbe();
        $container = new Container();
        $container->addShared(TypeProbe::class, $probe);

        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory($container, 'testBlueprints'));

        $first = $helper->form()->bind();
        $second = $helper->form()->bind();
        static::assertSame(0, $probe->count());
        $first->open();
        $first->control('first')->type('dependency-type')->render();
        $first->control('second')->type('dependency-type')->render();
        $first->close();
        static::assertSame(1, $probe->count());
        $second->open();
        $second->control('third')->type('dependency-type')->render();
        $second->close();
        static::assertSame(2, $probe->count());
    }

    public function testExplicitTypesWinAndDoNotConstructDiscoveredOverrides(): void
    {
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory(collection: 'testBlueprints'));

        $form = $helper->form()
            ->types(['dependency-type' => new RangeType()])
            ->bind();
        static::assertStringContainsString(
            'name="period[start]"',
            $form->open()
                . $form->control('period')->type('dependency-type')->render()
                . $form->close(),
        );
    }

    public function testLazyTypesForwardCompositeIdentityMetadata(): void
    {
        $container = new Container();
        $container->addShared(TypeProbe::class, new TypeProbe());

        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory($container, 'testBlueprints'));

        $form = $helper->form()->bind();
        $form->open();
        $form->control('custom')->type('dependency-type')->render();
        $this->expectException(LogicException::class);
        $form->control('custom-extra')->id('form-compose-1-custom-extra')
            ->render();
    }

    public function testFailedConstructionCanBeRetried(): void
    {
        $probe = new TypeProbe();
        $attempts = 0;
        $container = new Container();
        $container->add(DependencyType::class, static function () use (&$attempts, $probe): DependencyType {
            $attempts++;
            if ($attempts === 1) {
                throw new RuntimeException('Dependency unavailable.');
            }

            return new DependencyType($probe);
        });
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory($container, 'testBlueprints'));

        $form = $helper->form()->bind();
        $form->open();
        try {
            $form->control('custom')->type('dependency-type')->render();
            static::fail('Expected the dependency failure to prevent construction.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Dependency unavailable.', $runtimeException->getMessage());
        }

        static::assertStringContainsString(
            'name="custom"',
            $form->control('custom')->type('dependency-type')->render(),
        );
        static::assertSame(1, $probe->count());
        $form->close();
    }
}
