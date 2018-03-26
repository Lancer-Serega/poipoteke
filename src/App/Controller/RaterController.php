<?php

namespace App\Controller;

use Silex\Application;

class RaterController
{
    public function index(Application $app) {
        return $app['twig']->render('rater.html.twig', []);
    }
}