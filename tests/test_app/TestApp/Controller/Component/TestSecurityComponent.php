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
     * @param Controller $controller
     * @return bool
     */
    public function validatePost(Controller $controller): bool
    {
        return $this->_validatePost($controller);
    }
}
