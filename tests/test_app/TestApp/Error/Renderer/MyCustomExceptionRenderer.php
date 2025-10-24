<?php
declare(strict_types=1);

namespace TestApp\Error\Renderer;

use Cake\Controller\Controller;
use Cake\Error\Renderer\WebExceptionRenderer;
use TestApp\Error\Exception\MissingWidgetThing;

class MyCustomExceptionRenderer extends WebExceptionRenderer
{
    protected array $exceptionHttpCodes = [
        MissingWidgetThing::class => 404,
    ];

    /**
     * @param \Cake\Controller\Controller $controller
     */
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
