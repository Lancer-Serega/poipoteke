<?php

namespace App\Controller;

use Silex\Application;

class BaseController
{
    public function index(Application $app) {

        return $app['twig']->render('home.html.twig', []);
    }
}
