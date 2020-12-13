<?php
declare(strict_types=1);

namespace TestApp\Error;

use Cake\Error\ExceptionRenderer;
use TestApp\Error\Exception\NonHttpMissingException;
use Throwable;

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

    /**
     * @inheritDoc
     */
    protected function getHttpCode(Throwable $exception): int
    {
        if ($exception instanceof NonHttpMissingException) {
            return 404;
        }

        return parent::getHttpCode($exception);
    }
}
