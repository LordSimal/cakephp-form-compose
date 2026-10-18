<?php
declare(strict_types=1);

namespace FormCompose;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Container\ContainerInterface;
use Cake\Controller\Controller;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\ContainerApplicationInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventManagerInterface;
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

    protected ?BlueprintFactory $blueprints = null;

    /**
     * Supply discovery defaults once during plugin bootstrap, preserving application overrides.
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        if ($this->isEnabled('events') && $app instanceof ContainerApplicationInterface) {
            $factory = $app->getContainer()->get(BlueprintFactory::class);
            assert($factory instanceof BlueprintFactory, 'The application factory must extend BlueprintFactory.');
            $this->blueprints = $factory;
        }

        parent::bootstrap($app);
        if (AttributeResolver::getConfig('formCompose') === null) {
            AttributeResolver::setConfig('formCompose', [
                'paths' => ['src/Form/*.php', 'src/Form/**/*.php'],
                'cache' => Configure::read('debug') ? false : '_cake_attributes_',
                'validateFiles' => false,
            ]);
        }
    }

    /**
     * Register the construction boundary using the application's existing container.
     *
     * The plugin supplies this service to controller-created views;
     * blueprint classes receive ordinary dependencies rather than the container.
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

    /**
     * Carry the application factory into view options before helpers initialize.
     * Bootstrap supplies the factory before CakePHP calls this hook. View options
     * keep services out of template variables, and explicit configuration wins.
     */
    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        $factory = $this->blueprints;
        if (!$factory instanceof BlueprintFactory) {
            return $eventManager;
        }

        $eventManager->on('Controller.beforeRender', static function (EventInterface $event) use ($factory): void {
            $controller = $event->getSubject();
            if (!$controller instanceof Controller) {
                return;
            }

            $builder = $controller->viewBuilder();
            if ($builder->getOption('formCompose.blueprintFactory') === null) {
                $builder->setOption('formCompose.blueprintFactory', $factory);
            }
        });

        return $eventManager;
    }
}
