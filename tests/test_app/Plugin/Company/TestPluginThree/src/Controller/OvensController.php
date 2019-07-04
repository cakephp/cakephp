<?php
declare(strict_types=1);

namespace Company\TestPluginThree\Controller;

use Cake\Controller\Controller;

class OvensController extends Controller
{
    public function index()
    {
        $this->autoRender = false;
    }
}
