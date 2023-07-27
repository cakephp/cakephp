<?php
declare(strict_types=1);

namespace TestApp\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query;

class Test4Behavior extends Behavior
{
    /**
     * Test for event bindings.
     */
    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options, $primary): void
    {
        if (!empty($options['skipCreatedCondition'])) {
            return;
        }

        $query->where([
            'created <' => '2010-05-10 01:20:23',
        ]);
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
        return [];
    }

    /**
     * implementedMethods
     */
    public function implementedMethods(): array
    {
        return [];
    }
}
