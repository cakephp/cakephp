<?php
declare(strict_types=1);

namespace TestApp\Controller\Component;

use Cake\Controller\Component\SecurityComponent;
use Cake\Controller\Controller;

class TestSecurityComponent extends SecurityComponent
{
    /**
     * validatePost method
     *
     * @return void
     */
    public function validatePost(Controller $controller): void
    {
        $this->_validatePost($controller);
    }
}
