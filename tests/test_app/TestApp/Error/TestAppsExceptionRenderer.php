<?php

namespace TestApp\Error;

use Cake\Controller\Controller;
use Cake\Error\ExceptionRenderer;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use TestApp\Controller\TestAppsErrorController;

class TestAppsExceptionRenderer extends ExceptionRenderer
{
    /**
     * {@inheritDoc}
     */
    protected function _getController()
    {
        $request = $this->request ?: Router::getRequest(true);
        if ($request === null) {
            $request = new ServerRequest();
        }
        $response = new Response();
        try {
            $controller = new TestAppsErrorController($request, $response);
            $controller->viewBuilder()->setLayout('banana');
        } catch (\Exception $e) {
            $controller = new Controller($request, $response);
            $controller->viewBuilder()->setTemplatePath('Error');
        }

        return $controller;
    }
}
