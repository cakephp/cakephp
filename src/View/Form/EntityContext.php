<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Form;

use Cake\Collection\Collection;
use Cake\Datasource\EntityInterface;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\View\Form\ContextInterface;
use RuntimeException;
use Traversable;

/**
 * Provides a form context around a single entity and its relations.
 * It also can be used as context around an array or iterator of entities.
 *
 * This class lets FormHelper interface with entities or collections
 * of entities.
 *
 * Important Keys:
 *
 * - `entity` The entity this context is operating on.
 * - `table` Either the ORM\Table instance to fetch schema/validators
 *   from, an array of table instances in the case of a form spanning
 *   multiple entities, or the name(s) of the table.
 *   If this is null the table name(s) will be determined using naming
 *   conventions.
 * - `validator` Either the Validation\Validator to use, or the name of the
 *   validation method to call on the table object. For example 'default'.
 *   Defaults to 'default'. Can be an array of table alias=>validators when
 *   dealing with associated forms.
 */
class EntityContext implements ContextInterface
{

    /**
     * The request object.
     *
     * @var \Cake\Network\Request
     */
    protected $_request;

    /**
     * Context data for this object.
     *
     * @var array
     */
    protected $_context;

    /**
     * The name of the top level entity/table object.
     *
     * @var string
     */
    protected $_rootName;

    /**
     * Boolean to track whether or not the entity is a
     * collection.
     *
     * @var bool
     */
    protected $_isCollection = false;

    /**
     * A dictionary of tables
     *
     * @var array
     */
    protected $_tables = [];

    /**
     * Constructor.
     *
     * @param \Cake\Network\Request $request The request object.
     * @param array $context Context info.
     */
    public function __construct(Request $request, array $context)
    {
        $this->_request = $request;
        $context += [
            'entity' => null,
            'table' => null,
            'validator' => [],
        ];
        $this->_context = $context;
        $this->_prepare();
    }

    /**
     * Prepare some additional data from the context.
     *
     * If the table option was provided to the constructor and it
     * was a string, ORM\TableRegistry will be used to get the correct table instance.
     *
     * If an object is provided as the table option, it will be used as is.
     *
     * If no table option is provided, the table name will be derived based on
     * naming conventions. This inference will work with a number of common objects
     * like arrays, Collection objects and ResultSets.
     *
     * @return void
     * @throws \RuntimeException When a table object cannot be located/inferred.
     */
    protected function _prepare()
    {
        $table = $this->_context['table'];
        $entity = $this->_context['entity'];
        if (empty($table)) {
            if (is_array($entity) || $entity instanceof Traversable) {
                $entity = (new Collection($entity))->first();
            }
            $isEntity = $entity instanceof EntityInterface;

            if ($isEntity) {
                $table = $entity->source();
            }
            if (!$table && $isEntity && get_class($entity) !== 'Cake\ORM\Entity') {
                list(, $entityClass) = namespaceSplit(get_class($entity));
                $table = Inflector::pluralize($entityClass);
            }
        }
        if (is_string($table)) {
            $table = TableRegistry::get($table);
        }

        if (!is_object($table)) {
            throw new RuntimeException(
                'Unable to find table class for current entity'
            );
        }
        $this->_isCollection = (
            is_array($entity) ||
            $entity instanceof Traversable
        );
        $alias = $this->_rootName = $table->alias();
        $this->_tables[$alias] = $table;
    }

    /**
     * Get the primary key data for the context.
     *
     * Gets the primary key columns from the root entity's schema.
     *
     * @return bool
     */
    public function primaryKey()
    {
        return (array)$this->_tables[$this->_rootName]->primaryKey();
    }

    /**
     * {@inheritDoc}
     */
    public function isPrimaryKey($field)
    {
        $parts = explode('.', $field);
        $table = $this->_getTable($parts);
        $primaryKey = (array)$table->primaryKey();
        return in_array(array_pop($parts), $primaryKey);
    }

    /**
     * Check whether or not this form is a create or update.
     *
     * If the context is for a single entity, the entity's isNew() method will
     * be used. If isNew() returns null, a create operation will be assumed.
     *
     * If the context is for a collection or array the first object in the
     * collection will be used.
     *
     * @return bool
     */
    public function isCreate()
    {
        $entity = $this->_context['entity'];
        if (is_array($entity) || $entity instanceof Traversable) {
            $entity = (new Collection($entity))->first();
        }
        if ($entity instanceof EntityInterface) {
            return $entity->isNew() !== false;
        }
        return true;
    }

    /**
     * Get the value for a given path.
     *
     * Traverses the entity data and finds the value for $path.
     *
     * @param string $field The dot separated path to the value.
     * @return mixed The value of the field or null on a miss.
     */
    public function val($field)
    {
        $val = $this->_request->data($field);
        if ($val !== null) {
            return $val;
        }
        if (empty($this->_context['entity'])) {
            return null;
        }
        $parts = explode('.', $field);
        $entity = $this->entity($parts);

        if (end($parts) === '_ids' && !empty($entity)) {
            return $this->_extractMultiple($entity, $parts);
        }

        if ($entity instanceof EntityInterface) {
            return $entity->get(array_pop($parts));
        } elseif (is_array($entity)) {
            $key = array_pop($parts);
            return isset($entity[$key]) ? $entity[$key] : null;
        }
        return null;
    }

    /**
     * Helper method used to extract all the primary key values out of an array, The
     * primary key column is guessed out of the provided $path array
     *
     * @param array|\Traversable $values The list from which to extract primary keys from
     * @param array $path Each one of the parts in a path for a field name
     * @return array
     */
    protected function _extractMultiple($values, $path)
    {
        if (!(is_array($values) || $values instanceof \Traversable)) {
            return null;
        }
        $table = $this->_getTable($path, false);
        $primary = $table ? (array)$table->primaryKey() : ['id'];
        return (new Collection($values))->extract($primary[0])->toArray();
    }

    /**
     * Fetch the leaf entity for the given path.
     *
     * This method will traverse the given path and find the leaf
     * entity. If the path does not contain a leaf entity false
     * will be returned.
     *
     * @param array|null $path Each one of the parts in a path for a field name
     *  or null to get the entity passed in contructor context.
     * @return \Cake\DataSource\EntityInterface|\Traversable|array|bool
     * @throws \RuntimeException When properties cannot be read.
     */
    public function entity($path = null)
    {
        if ($path === null) {
            return $this->_context['entity'];
        }

        $oneElement = count($path) === 1;
        if ($oneElement && $this->_isCollection) {
            return false;
        }
        $entity = $this->_context['entity'];
        if ($oneElement) {
            return $entity;
        }

        if ($path[0] === $this->_rootName) {
            $path = array_slice($path, 1);
        }

        $len = count($path);
        $last = $len - 1;
        for ($i = 0; $i < $len; $i++) {
            $prop = $path[$i];
            $next = $this->_getProp($entity, $prop);
            $isLast = ($i === $last);

            if (!$isLast && $next === null && $prop !== '_ids') {
                $table = $this->_getTable($path);
                return $table->newEntity();
            }

            $isTraversable = (
                is_array($next) ||
                $next instanceof Traversable ||
                $next instanceof EntityInterface
            );
            if ($isLast || !$isTraversable) {
                return $entity;
            }
            $entity = $next;
        }
        throw new RuntimeException(sprintf(
            'Unable to fetch property "%s"',
            implode(".", $path)
        ));
    }

    /**
     * Read property values or traverse arrays/iterators.
     *
     * @param mixed $target The entity/array/collection to fetch $field from.
     * @param string $field The next field to fetch.
     * @return mixed
     */
    protected function _getProp($target, $field)
    {
        if (is_array($target) && isset($target[$field])) {
            return $target[$field];
        }
        if ($target instanceof EntityInterface) {
            return $target->get($field);
        }
        if ($target instanceof Traversable) {
            foreach ($target as $i => $val) {
                if ($i == $field) {
                    return $val;
                }
            }
            return false;
        }
    }

    /**
     * Check if a field should be marked as required.
     *
     * @param string $field The dot separated path to the field you want to check.
     * @return bool
     */
    public function isRequired($field)
    {
        $parts = explode('.', $field);
        $entity = $this->entity($parts);

        $isNew = true;
        if ($entity instanceof EntityInterface) {
            $isNew = $entity->isNew();
        }

        $validator = $this->_getValidator($parts);
        $field = array_pop($parts);
        if (!$validator->hasField($field)) {
            return false;
        }
        if ($this->type($field) !== 'boolean') {
            return $validator->isEmptyAllowed($field, $isNew) === false;
        }
        return false;
    }

    /**
     * Get the field names from the top level entity.
     *
     * If the context is for an array of entities, the 0th index will be used.
     *
     * @return array Array of fieldnames in the table/entity.
     */
    public function fieldNames()
    {
        $table = $this->_getTable('0');
        return $table->schema()->columns();
    }

    /**
     * Get the validator associated to an entity based on naming
     * conventions.
     *
     * @param array $parts Each one of the parts in a path for a field name
     * @return \Cake\Validation\Validator
     */
    protected function _getValidator($parts)
    {
        $keyParts = array_filter(array_slice($parts, 0, -1), function ($part) {
            return !is_numeric($part);
        });
        $key = implode('.', $keyParts);
        $entity = $this->entity($parts) ?: null;

        if (isset($this->_validator[$key])) {
            $this->_validator[$key]->provider('entity', $entity);
            return $this->_validator[$key];
        }

        $table = $this->_getTable($parts);
        $alias = $table->alias();

        $method = 'default';
        if (is_string($this->_context['validator'])) {
            $method = $this->_context['validator'];
        } elseif (isset($this->_context['validator'][$alias])) {
            $method = $this->_context['validator'][$alias];
        }

        $validator = $table->validator($method);
        $validator->provider('entity', $entity);
        return $this->_validator[$key] = $validator;
    }

    /**
     * Get the table instance from a property path
     *
     * @param array $parts Each one of the parts in a path for a field name
     * @param bool $rootFallback Whether or not to fallback to the root entity.
     * @return \Cake\ORM\Table|bool Table instance or false
     */
    protected function _getTable($parts, $rootFallback = true)
    {
        if (count($parts) === 1) {
            return $this->_tables[$this->_rootName];
        }

        $normalized = array_slice(array_filter($parts, function ($part) {
            return !is_numeric($part);
        }), 0, -1);

        $path = implode('.', $normalized);
        if (isset($this->_tables[$path])) {
            return $this->_tables[$path];
        }

        if (current($normalized) === $this->_rootName) {
            $normalized = array_slice($normalized, 1);
        }

        $table = $this->_tables[$this->_rootName];
        foreach ($normalized as $part) {
            $assoc = $table->associations()->getByProperty($part);
            if (!$assoc && $rootFallback) {
                break;
            }
            if (!$assoc && !$rootFallback) {
                return false;
            }

            $table = $assoc->target();
        }

        return $this->_tables[$path] = $table;
    }

    /**
     * Get the abstract field type for a given field name.
     *
     * @param string $field A dot separated path to get a schema type for.
     * @return null|string An abstract data type or null.
     * @see \Cake\Database\Type
     */
    public function type($field)
    {
        $parts = explode('.', $field);
        $table = $this->_getTable($parts);
        return $table->schema()->baseColumnType(array_pop($parts));
    }

    /**
     * Get an associative array of other attributes for a field name.
     *
     * @param string $field A dot separated path to get additional data on.
     * @return array An array of data describing the additional attributes on a field.
     */
    public function attributes($field)
    {
        $parts = explode('.', $field);
        $table = $this->_getTable($parts);
        $column = (array)$table->schema()->column(array_pop($parts));
        $whitelist = ['length' => null, 'precision' => null];
        return array_intersect_key($column, $whitelist);
    }

    /**
     * Check whether or not a field has an error attached to it
     *
     * @param string $field A dot separated path to check errors on.
     * @return bool Returns true if the errors for the field are not empty.
     */
    public function hasError($field)
    {
        return $this->error($field) !== [];
    }

    /**
     * Get the errors for a given field
     *
     * @param string $field A dot separated path to check errors on.
     * @return array An array of errors.
     */
    public function error($field)
    {
        $parts = explode('.', $field);
        $entity = $this->entity($parts);

        if ($entity instanceof EntityInterface) {
            return $entity->errors(array_pop($parts));
        }
        return [];
    }
}
