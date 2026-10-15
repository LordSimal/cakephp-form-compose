<?php
declare(strict_types=1);

namespace FormCompose;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Container\ContainerInterface;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use FormCompose\Blueprint\BlueprintFactory;

/**
 * Registers FormCompose with CakePHP's plugin loader.
 *
 * Provides the name used to locate its helper and PHP elements, discovery defaults
 * during bootstrap, and a container-backed blueprint factory through services().
 * Add FormComposePlugin::class in the application's bootstrap() method.
 */
class FormComposePlugin extends BasePlugin
{
    protected ?string $name = 'FormCompose';

    /**
     * Supply discovery defaults once during plugin bootstrap, preserving application overrides.
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
        if (AttributeResolver::getConfig('formCompose') === null) {
            AttributeResolver::setConfig('formCompose', [
                'paths' => ['src/Form/*.php', 'src/Form/**/*.php', 'src/Field/*.php', 'src/Field/**/*.php'],
                'cache' => '_cake_core_',
                'validateFiles' => true,
            ]);
        }
    }

    /**
     * Register the construction boundary using the application's existing container.
     *
     * Inject this service into a controller action and pass it to the view helper's
     * blueprintFactory option; blueprint classes never receive the container itself.
     */
    public function services(ContainerInterface $container): void
    {
        if ($container->hasDefinition(BlueprintFactory::class)) {
            return;
        }
        $container->addShared(
            BlueprintFactory::class,
            static fn(): BlueprintFactory => new BlueprintFactory($container),
        );
    }
}
