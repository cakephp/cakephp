<?php
declare(strict_types=1);

namespace TestApp\Error;

use Cake\Error\ExceptionRenderer;

class MyCustomExceptionRenderer extends ExceptionRenderer
{
    /**
     * @param \Cake\Controller\Controller $controller
     * @return void
     */
    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * custom error message type.
     *
     * @return string
     */
    public function missingWidgetThing()
    {
        return 'widget thing is missing';
    }
}
