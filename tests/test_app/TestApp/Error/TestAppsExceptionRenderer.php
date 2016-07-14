<?php

namespace TestApp\Error;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Error\ExceptionRenderer;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Router;
use TestApp\Controller\TestAppsErrorController;

class TestAppsExceptionRenderer extends ExceptionRenderer
{

    /**
     * {@inheritDoc}
     */
    protected function _getController()
    {
        if (!$request = Router::getRequest(true)) {
            $request = new Request();
        }
        $response = new Response();
        try {
            $controller = new TestAppsErrorController($request, $response);
            $controller->viewBuilder()->layout('banana');
        } catch (\Exception $e) {
            $controller = new Controller($request, $response);
            $controller->viewBuilder()->templatePath('Error');
        }

        return $controller;
    }
}
