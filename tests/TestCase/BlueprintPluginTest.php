<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Container\Container;
use Cake\Core\Plugin;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\FormComposePlugin;
use FormCompose\Test\Fixture\Blueprint\CustomBlueprintFactory;
use FormCompose\Test\Fixture\Blueprint\Valid\ConditionalForm;
use FormCompose\Test\Fixture\BlueprintApplication;
use FormCompose\Test\Fixture\BlueprintView;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;
use InvalidArgumentException;

/**
 * Verifies discovery setup belongs to plugin bootstrap and respects application configuration.
 */
class BlueprintPluginTest extends FormTestCase
{
    public function testBootstrapSuppliesDefaultsWithoutFactoryConstruction(): void
    {
        $plugins = Plugin::getCollection();
        $app = new BlueprintApplication(dirname(__DIR__) . '/test_app/config/');
        Plugin::setCollection($plugins);
        AttributeResolver::drop('formCompose');
        new FormComposePlugin()->bootstrap($app);
        /** @var mixed $config */
        $config = AttributeResolver::getConfig('formCompose');
        static::assertIsArray($config);
        static::assertSame(
            ['src/Form/*.php', 'src/Form/**/*.php', 'src/Field/*.php', 'src/Field/**/*.php'],
            $config['paths'],
        );
        static::assertSame('_cake_core_', $config['cache']);
        static::assertTrue($config['validateFiles']);
    }

    public function testBootstrapPreservesApplicationConfiguration(): void
    {
        $plugins = Plugin::getCollection();
        $app = new BlueprintApplication(dirname(__DIR__) . '/test_app/config/');
        Plugin::setCollection($plugins);
        /** @var array<string, mixed> $original */
        $original = AttributeResolver::getConfig('formCompose');
        $custom = ['paths' => ['src/CustomForms/*.php'], 'cache' => false];
        try {
            AttributeResolver::drop('formCompose');
            AttributeResolver::setConfig('formCompose', $custom);
            new FormComposePlugin()->bootstrap($app);
            static::assertSame($custom, AttributeResolver::getConfig('formCompose'));
        } finally {
            AttributeResolver::drop('formCompose');
            AttributeResolver::setConfig('formCompose', $original);
        }
    }

    public function testPluginPreservesTheApplicationFactoryRegistration(): void
    {
        $container = new Container();
        $factory = new CustomBlueprintFactory();
        $container->addShared(BlueprintFactory::class, $factory);
        new FormComposePlugin()->services($container);
        static::assertSame($factory, $container->get(BlueprintFactory::class));
    }

    public function testApplicationServicesReachTheHelperThroughViewConfiguration(): void
    {
        $plugins = Plugin::getCollection();
        $app = new BlueprintApplication(dirname(__DIR__) . '/test_app/config/');
        try {
            $app->addPlugin(new FormComposePlugin(['path' => dirname(__DIR__, 2) . '/']));
            /** @var mixed $factory */
            $factory = $app->getContainer()->get(BlueprintFactory::class);
            static::assertInstanceOf(BlueprintFactory::class, $factory);
            $view = new BlueprintView($this->helper()->getView()->getRequest(), viewOptions: ['viewVars' => [
                'blueprints' => $factory,
            ]]);
            $helper = $view->helpers()->get('Form');
            static::assertInstanceOf(FormHelper::class, $helper);
            $form = $helper->bind(definition: ConditionalForm::class);
            static::assertSame(
                ['work' => 'Work <address>', 'home' => 'Home'],
                $form->field('kind')->state->options['options'],
            );
        } finally {
            Plugin::setCollection($plugins);
        }
    }

    public function testFactoryDoesNotConfigureMissingCollections(): void
    {
        $factory = new BlueprintFactory(collection: 'unconfiguredBlueprints');
        static::assertNull(AttributeResolver::getConfig('unconfiguredBlueprints'));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unconfiguredBlueprints');
        $factory->types();
    }
}
