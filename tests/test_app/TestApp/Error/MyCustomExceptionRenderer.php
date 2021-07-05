<?php
declare(strict_types=1);

namespace TestApp\Error;

use Cake\Error\ExceptionRenderer;

class MyCustomExceptionRenderer extends ExceptionRenderer
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
