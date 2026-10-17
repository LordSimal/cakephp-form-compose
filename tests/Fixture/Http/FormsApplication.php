<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Http;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Core\Configure;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use FormCompose\FormComposePlugin;

/**
 * Serves the browser fixtures through Cake routing, CSRF, and controller startup.
 */
class FormsApplication extends BaseApplication
{
    /**
     * Reuse the test bootstrap without requiring an application config directory.
     */
    public function bootstrap(): void
    {
        Configure::write('App.namespace', __NAMESPACE__);
        Router::reload();
        AttributeResolver::drop('formComposeHttp');
        AttributeResolver::setConfig('formComposeHttp', [
            'basePath' => __DIR__,
            'paths' => ['Controller/*.php'],
            'cache' => false,
        ]);
        // Discover application controllers before this repository is registered as a plugin.
        AttributeResolver::collection('formComposeHttp');
        $this->addPlugin(new FormComposePlugin(['path' => dirname(__DIR__, 3) . '/']));
    }

    /**
     * Keep genuine HTTP security middleware in the browser regression path.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue->add(new AssetMiddleware())
            ->add(new BodyParserMiddleware())
            ->add(new RoutingMiddleware($this))
            ->add(new CsrfProtectionMiddleware());
    }

    /**
     * Discover the form and action browser fixtures from their controller attributes.
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->connectAttributes('formComposeHttp');
    }
}
