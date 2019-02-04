<?php

namespace TestApp\Stub;

use Cake\Datasource\ModelAwareTrait;

/**
 * Testing stub.
 */
class Stub
{
    use ModelAwareTrait;

    public function setProps($name)
    {
        $this->_setModelClass($name);
    }
}
