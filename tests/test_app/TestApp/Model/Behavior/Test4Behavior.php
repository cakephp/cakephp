<?php
declare(strict_types=1);

namespace TestApp\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;

class Test4Behavior extends Behavior
{
    /**
     * Add a where-clause to the query unless a truthy `skipCreatedCondition` option was provided
     */
    public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, $primary): void
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
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return ['Model.beforeFind' => 'beforeFind'];
    }
}
