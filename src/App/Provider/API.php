<?php

namespace App\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;

class API implements ControllerProviderInterface
{
    public function connect (Application $app) {
        /** @var $API \Silex\Application */
        $API = $app['controllers_factory'];

        $API->get('/get-search-form-data', 'App\\Controller\\APIController::getSearchFormData');
        $API->get('/get-raters', 'App\\Controller\\APIController::getRaters');
        $API->post('/create-request', 'App\\Controller\\APIController::createRequest');

        $API->get('/get-rater-info', 'App\\Controller\\APIController::getRaterInfo');
        $API->get('/get-rater-contacts', 'App\\Controller\\APIController::getRaterContacts');
        $API->get('/get-rater-prices', 'App\\Controller\\APIController::getRaterPrices');
        $API->get('/get-rater-work-time', 'App\\Controller\\APIController::getRaterWorkTime');
        $API->get('/get-rater-requests', 'App\\Controller\\APIController::getRaterRequests');

        $API->get('/update-rater-info', 'App\\Controller\\APIController::updateRaterInfo');
        $API->get('/update-rater-contacts', 'App\\Controller\\APIController::updateRaterContacts');
        $API->get('/update-rater-prices', 'App\\Controller\\APIController::updateRaterPrices');
        $API->get('/update-rater-work-time', 'App\\Controller\\APIController::updateRaterWorkTime');
        $API->get('/update-rater-request-status', 'App\\Controller\\APIController::updateRaterRequestStatus');

        return $API;
    }
}
