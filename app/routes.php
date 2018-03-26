<?php
/** @var $app \Silex\Application */
$app->get('/', 'App\\Controller\\BaseController::index');
$app->get('/login', 'App\\Controller\\AuthController::login');

$app->mount('/cabinet', new \App\Provider\Rater());
$app->mount('/api', new \App\Provider\API());

$app->error(
    function (\Exception $e, $code) use ($app) {
        $jsonResponse = false;

        switch ($code) {
            case 404:
                $message = 'Запрашиваемая вами страница не найдена.';

                break;
            case 500:
                $message = $e->getMessage();
                $jsonResponse = 429 === $e->getCode();

                break;
            default:
                $message = 'Невозможно обработать запрос.';
        }

        return $jsonResponse
            ? $app->json(['message' => $message], $e->getCode())
            : $app['twig']->render('error-page.html.twig', ['message' => $message]);
    }
);
