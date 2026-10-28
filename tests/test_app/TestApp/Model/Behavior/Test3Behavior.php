<?php
declare(strict_types=1);

namespace TestApp\Model\Behavior;

use Cake\ORM\Behavior;

class Test3Behavior extends Behavior
{
    /**
     * Test for event bindings.
     */
    public function beforeFind(): void
    {
    }

    /**
     * Test finder
     */
    public function findFoo(): void
    {
    }

    /**
     * Test method
     */
    public function doSomething(): void
    {
    }

    /**
     * implementedEvents
     *
     * This class does pretend to implement beforeFind
     *
     * @return array<string, mixed>
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
    public function testReflectionCache(): array
    {
        return $this->_reflectionCache();
    }
}
