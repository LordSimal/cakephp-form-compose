<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Cache\Engine\ArrayEngine;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\Routing\Router;
use Cake\Utility\Security;
use FormCompose\FormComposePlugin;
use FormCompose\Test\Fixture\BlueprintApplication;

require getenv('FORM_COMPOSE_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
$reflection = new ReflectionClass(BasePlugin::class);
$corePath = dirname((string)$reflection->getFileName(), 3) . '/';

define('ROOT', dirname(__DIR__));
define('CORE_PATH', $corePath);
define('CAKE', $corePath . 'src/');

define('APP', dirname(__DIR__) . '/tests/test_app/');
define('CONFIG', APP . 'config/');
define('TMP', dirname(__DIR__) . '/build/tmp/');
define('CACHE', TMP . 'cache/');
define('LOGS', TMP . 'logs/');

Configure::write('App', [
    'encoding' => 'UTF-8',
    'namespace' => 'FormCompose\\Test',
    'paths' => ['templates' => [APP . 'templates/']],
]);
Security::setSalt('form-compose-test-only-salt-never-use-in-production');
Configure::write('debug', true);
$application = new BlueprintApplication(CONFIG);
$application->addPlugin(new FormComposePlugin(['path' => dirname(__DIR__) . '/']));
$application->pluginBootstrap();
Router::reload();
Router::fullBaseUrl('https://example.test');
Router::createRouteBuilder('/')->connect('/{controller}/{action}/*');

ConnectionManager::setConfig('default', [
    'className' => Connection::class,
    'driver' => Sqlite::class,
    'database' => ':memory:',
]);

Cache::setConfig('_cake_core_', ['className' => ArrayEngine::class]);
Cache::setConfig('_cake_model_', ['className' => ArrayEngine::class]);

Cache::setConfig('_cake_translations_', ['className' => ArrayEngine::class]);
