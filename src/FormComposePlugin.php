<?php
declare(strict_types=1);

namespace FormCompose;

use Cake\Core\BasePlugin;

/**
 * Registers FormCompose with CakePHP's plugin loader.
 *
 * Provides the plugin name used to locate its helper and bundled PHP elements.
 * Add FormComposePlugin::class in the application's bootstrap() method to make
 * those resources available.
 */
class FormComposePlugin extends BasePlugin
{
    protected ?string $name = 'FormCompose';
}
