<?php

namespace App\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;


class Rater implements ControllerProviderInterface
{
    public function connect (Application $app) {
        $raterCab = $app['controllers_factory'];

        $raterCab->get('', 'App\\Controller\\RaterController::index');

        return $raterCab;
    }
}