<?php
declare(strict_types=1);

namespace TestApp\Stub;

use Cake\Datasource\ModelAwareTrait;

/**
 * Testing stub.
 */
class Stub
{
    use ModelAwareTrait;

    /**
     * @return void
     */
    public function setProps(string $name): void
    {
        $this->_setModelClass($name);
    }

    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }
}
