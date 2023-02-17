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

    public $Articles = null;
    public $Comments = null;
    public $Foo = null;
    public $Magic = null;
    public $PaginatorPosts = null;

    public function setProps(string $name): void
    {
        $this->_setModelClass($name);
    }

    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }
}
