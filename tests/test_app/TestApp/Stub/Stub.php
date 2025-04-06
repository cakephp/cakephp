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

    public $Articles;
    public $Comments;
    public $Foo;
    public $Magic;
    public $PaginatorPosts;

    public function setProps(string $name): void
    {
        $this->setModelClass($name);
    }

    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }
}
