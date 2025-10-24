<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\ErrorController;
use Cake\Http\Response;
use TestApp\Error\Exception\MissingWidgetThingException;

class TestAppsErrorController extends ErrorController
{
    protected function missingWidgetThing(MissingWidgetThingException $exception)
    {
        assert($this->viewBuilder()->getVar('error') === $exception);

        $this->viewBuilder()->setLayout('default');
    }

    protected function xml()
    {
        return new Response(['body' => '<xml>rendered xml exception</xml>']);
    }
}
