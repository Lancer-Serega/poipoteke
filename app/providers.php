<?php
/** @var $app Silex\Application */
require_once __DIR__ . '/config/config.php';

$app->register(new Silex\Provider\TwigServiceProvider(), $configTwig);
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider(), $configSecurity);
$app->register(new Silex\Provider\DoctrineServiceProvider(), $configDatabase);
$app->register(new Silex\Provider\ServiceControllerServiceProvider(), $configServiceControllerService);
$app->register(new Silex\Provider\SwiftmailerServiceProvider(), $configSwiftmailer);

$app['bank.repository'] = $app->share(function() use ($app) {
    return new App\Repositories\BankRepository($app['db']);
});

$app['contact.repository'] = $app->share(function() use ($app) {
    return new App\Repositories\ContactRepository($app['db']);
});

$app['object_type.repository'] = $app->share(function() use ($app) {
    return new App\Repositories\ObjectTypeRepository($app['db']);
});

$app['rater.repository'] = $app->share(function() use ($app) {
    return new App\Repositories\RaterRepository($app['db']);
});

$app['request_rate.repository'] = $app->share(function() use ($app) {
    return new App\Repositories\RequestRateRepository($app['db']);
});

$app['requisite.repository'] = $app->share(function() use ($app) {
    return new App\Repositories\RequisiteRepository($app['db']);
});

$app['user.repository'] = $app->share(function() use ($app) {
    return new App\Repositories\UserRepository($app['db']);
});

$app['work_time.repository'] = $app->share(function() use ($app) {
    return new App\Repositories\WorkTimeRepository($app['db']);
});

$app['request_date_calculator'] = $app->share(function() use ($app) {
    return new App\Services\RequestDateCalculator($app);
});

$app['frequent_request_protector'] = $app->share(function() use ($app) {
    return new App\Services\FrequentRequestProtect($app['session']);
});

$app['notifier'] = $app->share(function() use ($app) {
    return new App\Services\Notifier($app['swiftmailer.options'], $app['twig']);
});
