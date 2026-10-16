<?php
declare(strict_types=1);

use Cake\Http\Exception\HttpException;
use Cake\Http\Response;
use Cake\Http\Server;
use Cake\Http\ServerRequestFactory;
use FormCompose\Test\Fixture\Http\FormsApplication;

require dirname(__DIR__, 2) . '/bootstrap.php';

$server = new Server(new FormsApplication(CONFIG));
$environment = $_SERVER;
// The built-in server otherwise treats /forms/save as a script under /forms.
$environment['SCRIPT_NAME'] = '/index.php';
$environment['PHP_SELF'] = '/index.php';
try {
    $response = $server->run(ServerRequestFactory::fromGlobals($environment));
} catch (HttpException $httpException) {
    $response = new Response(['status' => $httpException->getCode(), 'body' => $httpException->getMessage()]);
}

$server->emit($response);
