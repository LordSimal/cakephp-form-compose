<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Cache\Cache;
use Cake\Cache\Engine\PhpEngine;
use Cake\Container\Container;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\FormComposePlugin;
use FormCompose\Test\Fixture\Blueprint\CustomBlueprintFactory;
use FormCompose\Test\Fixture\Blueprint\Valid\ConditionalForm;
use FormCompose\Test\Fixture\BlueprintApplication;
use FormCompose\Test\Fixture\BlueprintAppView;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies discovery setup belongs to plugin bootstrap and respects application configuration.
 */
class BlueprintPluginTest extends FormTestCase
{
    /**
     * @return array<string, array{array{debug: bool, cache: string|false}}>
     */
    public static function debugModes(): array
    {
        return [
            'production' => [['debug' => false, 'cache' => '_cake_attributes_']],
            'debug' => [['debug' => true, 'cache' => false]],
        ];
    }

    /**
     * @param array{debug: bool, cache: string|false} $mode Runtime mode and expected default cache.
     */
    #[DataProvider('debugModes')]
    public function testBootstrapSuppliesDefaultsWithoutFactoryConstruction(array $mode): void
    {
        $plugins = Plugin::getCollection();
        $app = new BlueprintApplication(dirname(__DIR__) . '/test_app/config/');
        Plugin::setCollection($plugins);
        $originalDebug = Configure::read('debug') === true;
        /** @var array<string, mixed> $original */
        $original = AttributeResolver::getConfig('formCompose');
        try {
            Configure::write('debug', $mode['debug']);
            AttributeResolver::drop('formCompose');
            new FormComposePlugin()->bootstrap($app);
            /** @var mixed $config */
            $config = AttributeResolver::getConfig('formCompose');
            static::assertIsArray($config);
            static::assertSame(
                ['src/Form/*.php', 'src/Form/**/*.php'],
                $config['paths'],
            );
            static::assertSame($mode['cache'], $config['cache']);
            static::assertFalse($config['validateFiles']);
        } finally {
            Configure::write('debug', $originalDebug);
            AttributeResolver::drop('formCompose');
            AttributeResolver::setConfig('formCompose', $original);
        }
    }

    /**
     * @param array{debug: bool, cache: string|false} $mode Runtime mode and expected default cache.
     */
    #[DataProvider('debugModes')]
    public function testBootstrapPreservesApplicationConfiguration(array $mode): void
    {
        $plugins = Plugin::getCollection();
        $app = new BlueprintApplication(dirname(__DIR__) . '/test_app/config/');
        Plugin::setCollection($plugins);
        $originalDebug = Configure::read('debug') === true;
        /** @var array<string, mixed> $original */
        $original = AttributeResolver::getConfig('formCompose');
        $custom = ['paths' => ['src/CustomForms/*.php'], 'cache' => 'application_metadata', 'validateFiles' => true];
        try {
            Configure::write('debug', $mode['debug']);
            AttributeResolver::drop('formCompose');
            AttributeResolver::setConfig('formCompose', $custom);
            new FormComposePlugin()->bootstrap($app);
            static::assertSame($custom, AttributeResolver::getConfig('formCompose'));
        } finally {
            Configure::write('debug', $originalDebug);
            AttributeResolver::drop('formCompose');
            AttributeResolver::setConfig('formCompose', $original);
        }
    }

    public function testProductionDiscoveryUsesTheApplicationPhpEngine(): void
    {
        $plugins = Plugin::getCollection();
        $app = new BlueprintApplication(dirname(__DIR__) . '/test_app/config/');
        Plugin::setCollection($plugins);
        $originalDebug = Configure::read('debug') === true;
        /** @var array<string, mixed> $original */
        $original = AttributeResolver::getConfig('formCompose');
        /** @var array<string, mixed> $originalCache */
        $originalCache = Cache::getConfig('_cake_attributes_');
        $path = CACHE . 'attributes/';
        try {
            Configure::write('debug', false);
            Cache::drop('_cake_attributes_');
            Cache::setConfig('_cake_attributes_', [
                'className' => PhpEngine::class,
                'prefix' => 'form_compose_test_',
                'path' => $path,
                'duration' => 0,
            ]);
            $engine = Cache::pool('_cake_attributes_');
            Plugin::getCollection()->add(new BasePlugin([
                'name' => 'BlueprintFixture',
                'path' => dirname(__DIR__) . '/Fixture/Blueprint/Plugin/',
            ]));
            AttributeResolver::drop('formCompose');
            new FormComposePlugin()->bootstrap($app);
            AttributeResolver::clear('formCompose');
            $collection = AttributeResolver::collection('formCompose');
            static::assertGreaterThan(0, $collection->count());
            static::assertNotEmpty(glob($path . '*.php'));
            static::assertSame($engine, Cache::pool('_cake_attributes_'));

            AttributeResolver::drop('formCompose');
            new FormComposePlugin()->bootstrap($app);
            static::assertEquals($collection->toArray(), AttributeResolver::collection('formCompose')->toArray());
            AttributeResolver::clear('formCompose');
            static::assertSame([], glob($path . '*.php'));
        } finally {
            Cache::pool('_cake_attributes_')->clear();
            Cache::drop('_cake_attributes_');
            Cache::setConfig('_cake_attributes_', $originalCache);
            Configure::write('debug', $originalDebug);
            AttributeResolver::drop('formCompose');
            AttributeResolver::setConfig('formCompose', $original);
            Plugin::getCollection()->remove('BlueprintFixture');
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
            $view = new BlueprintAppView($this->helper()->getView()->getRequest(), viewOptions: ['viewVars' => [
                'blueprints' => $factory,
            ]]);
            $helper = $view->helpers()->get('Form');
            static::assertInstanceOf(FormHelper::class, $helper);
            $form = $helper->bind(blueprint: ConditionalForm::class);
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
