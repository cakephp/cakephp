<?php
declare(strict_types=1);

namespace TestApp\Model\Behavior;

use Cake\ORM\Behavior;

class Test2Behavior extends Behavior
{
    protected $_defaultConfig = [
        'implementedFinders' => [
            'foo' => 'findFoo',
        ],
        'implementedMethods' => [
            'doSomething' => 'doSomething',
        ],
    ];

    /**
     * Test for event bindings.
     */
    public function beforeFind()
    {
    }

    /**
     * Test finder
     */
    public function findFoo()
    {
    }

    /**
     * Test method
     */
    public function doSomething()
    {
    }
}
