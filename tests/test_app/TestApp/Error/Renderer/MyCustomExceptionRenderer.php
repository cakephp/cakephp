<?php
declare(strict_types=1);

namespace TestApp\Error\Renderer;

use Cake\Error\Renderer\WebExceptionRenderer;

class MyCustomExceptionRenderer extends WebExceptionRenderer
{
    /**
     * @param \Cake\Controller\Controller $controller
     */
    public function setController($controller): void
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
