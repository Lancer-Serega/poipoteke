<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

require_once __DIR__ . '/../app/routes.php';
require_once __DIR__ . '/../app/providers.php';

$app['debug'] = true;
$app->run();