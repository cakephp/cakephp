<?php
declare(strict_types=1);

namespace TestApp\Error\Renderer;

use Cake\Controller\Controller;
use Cake\Error\Renderer\WebExceptionRenderer;

class MyCustomExceptionRenderer extends WebExceptionRenderer
{
    public function setController(Controller $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * custom error message type.
     */
    public function missingWidgetThing(): string
    {
        return 'widget thing is missing';
    }
}
