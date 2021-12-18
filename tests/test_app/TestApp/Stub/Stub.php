<?php
declare(strict_types=1);

namespace TestApp\Stub;

use Cake\Datasource\ModelAwareTrait;

/**
 * Testing stub.
 */
#[\AllowDynamicProperties]
class Stub
{
    use ModelAwareTrait;

    public function setProps(string $name): void
    {
        $this->_setModelClass($name);
    }

    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }
}
