<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;

/**
 * Runs real plugin bootstrap and container hooks without host-application configuration.
 */
class BlueprintApplication extends BaseApplication
{
    /**
     * Keep the test application free of unrelated HTTP middleware.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }
}
