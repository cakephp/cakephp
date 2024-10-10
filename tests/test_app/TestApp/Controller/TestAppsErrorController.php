<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\ErrorController;
use Cake\Http\Response;

class TestAppsErrorController extends ErrorController
{
    protected function missingWidgetThing()
    {
        $this->viewBuilder()->setLayout('default');
    }

    protected function xml()
    {
        return new Response(['body' => '<xml>rendered xml exception</xml>']);
    }
}
