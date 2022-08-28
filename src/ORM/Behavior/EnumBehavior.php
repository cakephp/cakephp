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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Behavior;

use ArrayObject;
use BackedEnum;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;

/**
 * Enum behavior
 *
 * Allows you to map a specific field in your model to a backed enum
 *
 *  * Add behavior + config to connect a field with a backed enum
 * ```
 * $this->addBehavior('Enum', [
 *     'fieldEnums' => [
 *         'published' => ArticleStatus::class
 *     ]
 * ]);
 * ```
 */
class EnumBehavior extends Behavior
{
    /**
     * Default config
     *
     * These are merged with user-provided configuration when the behavior is used.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'fieldEnums' => [],
    ];

    /**
     * Initialize hook
     *
     * @param array $config The config for this behavior.
     * @return void
     */
    public function initialize(array $config): void
    {
        $schema = $this->_table->getSchema();
        foreach (array_keys($this->getConfig('fieldEnums')) as $field) {
            $schema->setColumnType($field, 'enum');
        }
        $this->_table->setSchema($schema);
    }

    /**
     * Transform scalar values to enum instances
     *
     * @param \Cake\Event\EventInterface $event The beforeMarshal event that was fired
     * @param \Cake\Datasource\EntityInterface $entity Entity created after marshaling
     * @param \ArrayObject $data The original request data
     * @param \ArrayObject $options Options passed to the event
     * @return void
     */
    public function afterMarshal(
        EventInterface $event,
        EntityInterface $entity,
        ArrayObject $data,
        ArrayObject $options
    ): void {
        $fieldEnums = $this->getConfig('fieldEnums');
        /** @var \BackedEnum $enumType */
        foreach ($fieldEnums as $field => $enumType) {
            if ($data->offsetExists($field)) {
                $value = $data->offsetGet($field);
                if (is_int($value) || is_string($value)) {
                    $enumValue = $enumType::tryFrom($value);
                    if ($enumValue !== null) {
                        $entity->set($field, $enumValue);
                    } else {
                        $entity->setError($field, __d('cake', 'Given value is not valid'));
                    }
                }
            }
        }
    }

    /**
     * Transform entity fields into enum instances if they are present
     *
     * @param \Cake\Event\EventInterface $event The beforeFind event that was fired
     * @param \Cake\ORM\Query $query Query
     * @param \ArrayObject $options The options for the query
     * @return void
     */
    public function beforeFind(EventInterface $event, Query $query, ArrayObject $options): void
    {
        $query->formatResults(function ($results) {
            return $results->map(function (Entity $entity) {
                return $this->setEnumFields($entity);
            });
        });
    }

    /**
     * Set enum instances on mapped fields after an entity has been saved
     *
     * @param \Cake\Event\EventInterface $event The afterSave event that was fired
     * @param \Cake\Datasource\EntityInterface $entity The entity being updated
     * @param \ArrayObject $options The options for the query
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $this->setEnumFields($entity);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity The entity to transform data on
     * @return \Cake\Datasource\EntityInterface
     */
    protected function setEnumFields(EntityInterface $entity): EntityInterface
    {
        $fieldEnums = $this->getConfig('fieldEnums');
        /** @var \BackedEnum $enumType */
        foreach ($fieldEnums as $field => $enumType) {
            if ($entity->has($field)) {
                $dbValue = $entity->get($field);
                if (!$dbValue instanceof BackedEnum) {
                    $entity->set($field, $enumType::from($dbValue));
                }
            }
        }

        return $entity;
    }
}
