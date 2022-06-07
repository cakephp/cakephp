<?php
declare(strict_types=1);

namespace TestApp\Error\Renderer;

use Cake\Controller\Controller;
use Cake\Error\Renderer\WebExceptionRenderer;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Exception;
use TestApp\Controller\TestAppsErrorController;

class TestAppsExceptionRenderer extends WebExceptionRenderer
{
    /**
     * @inheritDoc
     */
    protected function _getController(): Controller
    {
        $request = $this->request ?: Router::getRequest();
        if ($request === null) {
            $request = new ServerRequest();
        }
        try {
            $controller = new TestAppsErrorController($request);
            $controller->viewBuilder()->setLayout('banana');
        } catch (Exception $e) {
            $controller = new Controller($request);
            $controller->viewBuilder()->setTemplatePath('Error');
        }

        return $controller;
    }
}
