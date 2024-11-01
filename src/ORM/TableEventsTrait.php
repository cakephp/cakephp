<?php
declare(strict_types=1);

namespace Cake\ORM;

use ArrayObject;
use Cake\Database\Query;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;

trait TableEventsTrait
{
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
    }

    public function afterMarshal(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $data,
        ArrayObject $options
    ): void {
    }

    public function buildValidator(EventInterface $event, Validator $validator, $name): void
    {
    }

    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options, $primary): void
    {
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    public function afterSaveCommit(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    public function afterDeleteCommit(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    public function beforeRules(EventInterface $event, EntityInterface $entity, ArrayObject $options, $operation): void
    {
    }

    public function afterRules(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
        $result,
        $operation
    ): void {
    }
}
