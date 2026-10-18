<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Cache\Cache;
use Cake\Cache\Engine\PhpEngine;
use Cake\Container\Container;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\EventManager;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\FormComposePlugin;
use FormCompose\Test\Fixture\Blueprint\CustomBlueprintFactory;
use FormCompose\Test\Fixture\BlueprintApplication;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies discovery setup belongs to plugin bootstrap and respects application configuration.
 */
class BlueprintPluginTest extends FormTestCase
{
    protected EventManager $originalEvents;

    /** Isolate plugin event registrations from other application instances. */
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEvents = EventManager::instance();
        EventManager::instance(new EventManager());
    }

    /** Restore the application's global event manager after each test. */
    protected function tearDown(): void
    {
        EventManager::instance($this->originalEvents);
        parent::tearDown();
    }

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
    public function testBootstrapSuppliesDiscoveryDefaults(array $mode): void
    {
        $plugins = Plugin::getCollection();
        $app = new BlueprintApplication(dirname(__DIR__, 2) . '/test_app/config/');
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
        $app = new BlueprintApplication(dirname(__DIR__, 2) . '/test_app/config/');
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
        $app = new BlueprintApplication(dirname(__DIR__, 2) . '/test_app/config/');
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
                'path' => dirname(__DIR__, 2) . '/Fixture/Blueprint/Plugin/',
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

    public function testEventsWithoutApplicationBootstrapPreserveTheManager(): void
    {
        $events = new EventManager();
        static::assertSame($events, new FormComposePlugin()->events($events));
        static::assertSame([], $events->listeners('Controller.beforeRender'));
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
