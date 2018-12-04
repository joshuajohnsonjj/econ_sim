<?php
use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Autoloader;
use PHPSocketIO\SocketIO;

// composer autoload
require_once  __DIR__ . '/../../vendor/autoload.php';

// set up webserver for socket.io
$web = new WebServer('http://127.0.0.1:2022');
$web->addRoot('localhost', __DIR__ . '/public');

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}
