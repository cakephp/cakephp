<?php
declare(strict_types=1);

namespace TestApp\Model\Behavior;

use Cake\ORM\Behavior;

class Test3Behavior extends Behavior
{
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

    /**
     * Test method to ensure it is ignored as a callable method.
     */
    public function verifyConfig(): void
    {
        parent::verifyConfig();
    }

    /**
     * implementedEvents
     *
     * This class does pretend to implement beforeFind
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return ['Model.beforeFind' => 'beforeFind'];
    }

    /**
     * implementedFinders
     */
    public function implementedFinders(): array
    {
    }

    /**
     * implementedMethods
     */
    public function implementedMethods(): array
    {
    }

    /**
     * Expose protected method for testing
     *
     * Since this is public - it'll show up as callable which is a side-effect
     *
     * @return array
     */
    public function testReflectionCache()
    {
        return $this->_reflectionCache();
    }
}
