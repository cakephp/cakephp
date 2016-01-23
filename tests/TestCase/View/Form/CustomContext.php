<?php
namespace Cake\Test\TestCase\View\Form;

use Cake\Network\Request;
use Cake\View\Form\AbstractContext;

class CustomContext extends AbstractContext
{
    private $create;

    public function __construct(Request $request, $requestType, $create)
    {
        $this->create = $create;
        parent::__construct($request, $requestType);
    }

    public function getRequestType()
    {
        return $this->_requestType;
    }

    public function isCreate()
    {
        return $this->create;
    }

    public function primaryKey()
    {
    }
    public function isPrimaryKey($field)
    {
    }
    public function isRequired($field)
    {
    }
    public function fieldNames()
    {
    }
    public function type($field)
    {
    }
    public function hasError($field)
    {
    }
    public function attributes($field)
    {
    }
    public function error($field)
    {
    }
}
