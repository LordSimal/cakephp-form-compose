<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Container\Container;
use Cake\Core\BasePlugin;
use Cake\Core\Plugin;
use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\FormComposePlugin;
use FormCompose\Test\BlueprintTestCase;
use FormCompose\Test\Fixture\Blueprint\BlueprintFormHelper;
use FormCompose\Test\Fixture\Blueprint\Plugin\src\Form\Field\PluginRangeType;
use FormCompose\Test\Fixture\Blueprint\Valid\ConditionalForm;
use FormCompose\Test\Fixture\Blueprint\Valid\DiscoveredRangeType;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileChoices;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileForm;
use FormCompose\Test\Fixture\RangeType;
use InvalidArgumentException;

/**
 * Uses CakePHP's real scanner, cache, container and plugin collection for discovery.
 */
class BlueprintDiscoveryTest extends BlueprintTestCase
{
    public function testAliasesAndClassesResolveEquivalentConfiguration(): void
    {
        $factory = new BlueprintFactory(collection: 'testBlueprints');
        static::assertEquals(
            $factory->compile(ProfileForm::class, $this->context()),
            $factory->compile('profile', $this->context()),
        );
    }

    public function testContainerInjectionAndHooksAreFreshEvenForSharedBlueprints(): void
    {
        $container = new Container();
        $container->addShared(ConditionalForm::class)->addArgument(ProfileChoices::class);
        new FormComposePlugin()->services($container);
        /** @var mixed $service */
        $service = $container->get(BlueprintFactory::class);
        static::assertInstanceOf(BlueprintFactory::class, $service);
        $factory = new BlueprintFactory($container, 'testBlueprints');
        $helper = $this->helper(attributes: ['canManageAccounts' => true]);
        $helper->setConfig('blueprintFactory', $factory);

        $first = $helper->form(new ArrayContext(['data' => ['label' => 'First email']]))
            ->blueprint('conditional')
            ->bind();
        $second = $helper->form(new ArrayContext(['data' => [
            'label' => 'Second email',
        ]]))
            ->blueprint(ConditionalForm::class)
            ->bind();
        static::assertSame('First email', $first->field('email')->resolve()->state->config->label);
        static::assertSame('Second email', $second->field('email')->resolve()->state->config->label);
        static::assertSame(
            ['calls' => 1],
            $first->field('email')->resolve()->state->config->presentation->templateData,
        );
        static::assertSame(
            ['calls' => 1],
            $second->field('email')->resolve()->state->config->presentation->templateData,
        );
        static::assertSame(
            ['email', 'id', 'kind', 'active'],
            array_column($first->binding->definition->fields ?? [], 'path'),
        );
        $html = $first->open() . $first->blueprint()->render() . $first->close();
        static::assertStringContainsString('Work &lt;address&gt;', $html);
        static::assertStringNotContainsString('name="internal_note"', $html);
        $unprivileged = $this->helper();
        $unprivileged->setConfig('blueprintFactory', $factory);

        $third = $unprivileged->form(new ArrayContext(['data' => [
            'label' => 'Third email',
        ]]))
            ->blueprint('conditional')
            ->bind();
        static::assertFalse($third->binding->definition?->has('active'));
        static::assertFalse($third->binding->definition?->has('internal_note'));
        static::assertTrue($first->binding->definition?->has('active'));
        $third->open();
        static::assertStringContainsString('name="internal_note"', $third->control('internal_note')->render());
        $third->close();
    }

    public function testObjectRegistrationsDoNotRetainPerBindingHookState(): void
    {
        $container = new Container();
        $seed = new ConditionalForm(new ProfileChoices());
        $container->addShared(ConditionalForm::class, $seed);
        $factory = new BlueprintFactory($container, 'testBlueprints');
        $first = $factory->compile('conditional', $this->context());
        $second = $factory->compile('conditional', $this->context());
        static::assertSame(['calls' => 1], $first->field('email')->config->presentation->templateData);
        static::assertSame(['calls' => 1], $second->field('email')->config->presentation->templateData);
    }

    public function testPluginServiceUsesTheApplicationContainer(): void
    {
        $container = new Container();
        $container->addShared(ProfileChoices::class, new ProfileChoices());
        new FormComposePlugin()->services($container);
        /** @var mixed $factory */
        $factory = $container->get(BlueprintFactory::class);
        static::assertInstanceOf(BlueprintFactory::class, $factory);
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', $factory);

        $form = $helper->form()->blueprint(ConditionalForm::class)->bind();
        static::assertSame(
            ['work' => 'Work <address>', 'home' => 'Home'],
            $form->field('kind')->resolve()->state->config->choices,
        );
    }

    public function testDiscoveredCustomTypesRenderAndExplicitTypesWin(): void
    {
        $factory = new BlueprintFactory(collection: 'testBlueprints');
        static::assertInstanceOf(DiscoveredRangeType::class, $factory->types()['date-range']);
        $override = new RangeType();
        static::assertSame($override, $factory->types(['date-range' => $override])['date-range']);
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', $factory);

        $form = $helper->form()->bind();
        $html =
            $form->open()
            . $form->control('period')->type('date-range')->render()
            . $form->close();
        static::assertStringContainsString('name="period[start]"', $html);
        static::assertStringContainsString('name="period[end]"', $html);
    }

    public function testCacheClearingRefreshesAliasesForAnExistingFactory(): void
    {
        $factory = new BlueprintFactory(collection: 'testBlueprints');
        static::assertTrue($factory->compile('profile', $this->context())->has('email'));
        AttributeResolver::drop('testBlueprints');
        AttributeResolver::setConfig('testBlueprints', ['paths' => [], 'cache' => '_cake_attributes_']);
        static::assertTrue($factory->compile('profile', $this->context())->has('email'));
        AttributeResolver::clear('testBlueprints');
        $this->expectException(InvalidArgumentException::class);
        $factory->compile('profile', $this->context());
    }

    public function testDiscoveryExpandsPathsAcrossLoadedPlugins(): void
    {
        Plugin::getCollection()->add(new BasePlugin([
            'name' => 'BlueprintFixture',
            'path' => dirname(__DIR__, 2) . '/Fixture/Blueprint/Plugin/',
        ]));
        AttributeResolver::drop('testBlueprints');
        AttributeResolver::setConfig('testBlueprints', [
            'paths' => ['src/Form/*.php', 'src/Form/**/*.php'],
            'cache' => false,
        ]);
        $factory = new BlueprintFactory(collection: 'testBlueprints');
        $definition = $factory->compile('BlueprintFixture.profile', $this->context());
        static::assertSame('email', $definition->field('plugin_email')->config->type);
        static::assertTrue($factory->compile('BlueprintFixture.admin', $this->context())->has('admin_email'));
        static::assertInstanceOf(PluginRangeType::class, $factory->types()['plugin-range']);
    }

    public function testSubclassFactoriesAreUsedByPublicBinding(): void
    {
        $helper = new BlueprintFormHelper($this->helper()->getView());
        $form = $helper->form()->blueprint(ProfileForm::class)->bind();
        static::assertSame('Custom builder', $form->field('custom')->resolve()->state->config->label);
    }
}
