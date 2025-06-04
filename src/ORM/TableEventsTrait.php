<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validator;

/**
 * Provides model callbacks.
 *
 * @phpstan-ignore trait.unused
 */
trait TableEventsTrait
{
    /**
     * The Model.beforeMarshal event is fired before request data is converted into entities.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \ArrayObject<string, mixed> $data Data to be saved.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
    }

    /**
     * The Model.afterMarshal event is fired after request data is converted into entities.
     * Event handlers will get the converted entities, original request data and the options provided
     * to the patchEntity() or newEntity() call.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity The entity to be saved.
     * @param \ArrayObject<string, mixed> $data Data to be saved.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function afterMarshal(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $data,
        ArrayObject $options,
    ): void {
    }

    /**
     * The Model.buildValidator event is fired when $name validator is created.
     * Behaviors, can use this hook to add in validation methods.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \Cake\Validation\Validator $validator Validator.
     * @param string $name Name.
     * @return void
     */
    public function buildValidator(EventInterface $event, Validator $validator, string $name): void
    {
    }

    /**
     * The Model.beforeFind event is fired before each find operation.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \Cake\ORM\Query\SelectQuery $query Query.
     * @param \ArrayObject<string, mixed> $options Options.
     * @param bool $primary `true` if it is the root query, `false` if it is the associated query.
     * @return void
     */
    public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void
    {
    }

    /**
     * The Model.beforeSave event is fired before each entity is saved.
     * Stopping this event will abort the save operation.
     * When the event is stopped the result of the event will be returned.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity The entity to be saved.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    /**
     * The Model.afterSave event is fired after an entity is saved.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity Saved entity.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    /**
     * The Model.afterSaveCommit event is fired after the transaction in which the save operation is wrapped has been
     * committed. It’s also triggered for non atomic saves where database operations are implicitly committed. The event
     * is triggered only for the primary table on which save() is directly called. The event is not triggered if a
     * transaction is started before calling save.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity Saved entity.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function afterSaveCommit(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    /**
     * The Model.beforeDelete event is fired before an entity is deleted.
     * By stopping this event you will abort the delete operation.
     * When the event is stopped the result of the event will be returned.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity Entity to be deleted.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    /**
     * The Model.afterDelete event is fired after an entity has been deleted.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity Deleted entity.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    /**
     * The Model.afterDeleteCommit event is fired after the transaction in which the delete operation is wrapped has
     * been is committed. It’s also triggered for non atomic deletes where database operations are implicitly committed.
     * The event is triggered only for the primary table on which delete() is directly called. The event is not
     * triggered if a transaction is started before calling delete.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity Deleted entity.
     * @param \ArrayObject<string, mixed> $options Options.
     * @return void
     */
    public function afterDeleteCommit(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
    }

    /**
     * The Model.beforeRules event is fired before an entity has had rules applied.
     * By stopping this event, you can halt the rules checking and set the result of applying rules.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity The entity to be saved.
     * @param \ArrayObject<string, mixed> $options Options.
     * @param string $operation Operation.
     * @return void
     */
    public function beforeRules(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
        string $operation,
    ): void {
    }

    /**
     * The Model.afterRules event is fired after an entity has rules applied.
     * By stopping this event, you can return the final value of the rules checking operation.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Model event.
     * @param \Cake\Datasource\EntityInterface $entity The entity to be saved.
     * @param \ArrayObject<string, mixed> $options Options.
     * @param bool $result Result.
     * @param string $operation Operation.
     * @return void
     */
    public function afterRules(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $options,
        bool $result,
        string $operation,
    ): void {
    }
}
