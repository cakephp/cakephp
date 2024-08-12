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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Form;

use ArrayAccess;
use Cake\Collection\Collection;
use Cake\Core\Exception\CakeException;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\InvalidPropertyInterface;
use Cake\ORM\Entity;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use InvalidArgumentException;
use Traversable;
use function Cake\Core\namespaceSplit;

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
    use LocatorAwareTrait;

    /**
     * Context data for this object.
     *
     * @var array<string, mixed>
     */
    protected array $_context;

    /**
     * The name of the top level entity/table object.
     *
     * @var string
     */
    protected string $_rootName;

    /**
     * Boolean to track whether the entity is a
     * collection.
     *
     * @var bool
     */
    protected bool $_isCollection = false;

    /**
     * A dictionary of tables
     *
     * @var array<\Cake\ORM\Table>
     */
    protected array $_tables = [];

    /**
     * Dictionary of validators.
     *
     * @var array<\Cake\Validation\Validator>
     */
    protected array $_validator = [];

    /**
     * Constructor.
     *
     * @param array<string, mixed> $context Context info.
     */
    public function __construct(array $context)
    {
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
     * was a string, TableLocator will be used to get the correct table instance.
     *
     * If an object is provided as the table option, it will be used as is.
     *
     * If no table option is provided, the table name will be derived based on
     * naming conventions. This inference will work with a number of common objects
     * like arrays, Collection objects and ResultSets.
     *
     * @return void
     * @throws \Cake\Core\Exception\CakeException When a table object cannot be located/inferred.
     */
    protected function _prepare(): void
    {
        $table = $this->_context['table'];

        /** @var \Cake\Datasource\EntityInterface|iterable<\Cake\Datasource\EntityInterface|array> $entity */
        $entity = $this->_context['entity'];
        $this->_isCollection = is_iterable($entity);

        if (!$table) {
            if ($this->_isCollection) {
                /** @var iterable<\Cake\Datasource\EntityInterface|array> $entity */
                foreach ($entity as $e) {
                    $entity = $e;
                    break;
                }
            }

            if ($entity instanceof EntityInterface) {
                $table = $entity->getSource();
            }
            if (!$table && $entity instanceof EntityInterface && $entity::class !== Entity::class) {
                [, $entityClass] = namespaceSplit($entity::class);
                $table = Inflector::pluralize($entityClass);
            }
        }
        if (is_string($table) && $table !== '') {
            $table = $this->getTableLocator()->get($table);
        }

        if (!($table instanceof Table)) {
            throw new CakeException('Unable to find table class for current entity.');
        }
        $alias = $table->getAlias();
        $this->_rootName = $alias;
        $this->_tables[$alias] = $table;
    }

    /**
     * Get the primary key data for the context.
     *
     * Gets the primary key columns from the root entity's schema.
     *
     * @return list<string>
     */
    public function getPrimaryKey(): array
    {
        return (array)$this->_tables[$this->_rootName]->getPrimaryKey();
    }

    /**
     * @inheritDoc
     */
    public function isPrimaryKey(string $field): bool
    {
        $parts = explode('.', $field);
        $table = $this->_getTable($parts);
        if (!$table) {
            return false;
        }
        $primaryKey = (array)$table->getPrimaryKey();

        return in_array(array_pop($parts), $primaryKey, true);
    }

    /**
     * Check whether this form is a create or update.
     *
     * If the context is for a single entity, the entity's isNew() method will
     * be used. If isNew() returns null, a create operation will be assumed.
     *
     * If the context is for a collection or array the first object in the
     * collection will be used.
     *
     * @return bool
     */
    public function isCreate(): bool
    {
        $entity = $this->_context['entity'];
        if (is_iterable($entity)) {
            foreach ($entity as $e) {
                $entity = $e;
                break;
            }
        }
        if ($entity instanceof EntityInterface) {
            return $entity->isNew();
        }

        return true;
    }

    /**
     * Get the value for a given path.
     *
     * Traverses the entity data and finds the value for $path.
     *
     * @param string $field The dot separated path to the value.
     * @param array<string, mixed> $options Options:
     *
     *   - `default`: Default value to return if no value found in data or
     *     entity.
     *   - `schemaDefault`: Boolean indicating whether default value from table
     *     schema should be used if it's not explicitly provided.
     * @return mixed The value of the field or null on a miss.
     */
    public function val(string $field, array $options = []): mixed
    {
        $options += [
            'default' => null,
            'schemaDefault' => true,
        ];

        if (empty($this->_context['entity'])) {
            return $options['default'];
        }
        $parts = explode('.', $field);
        $entity = $this->entity($parts);

        if ($entity && end($parts) === '_ids') {
            return $this->_extractMultiple($entity, $parts);
        }

        if ($entity instanceof EntityInterface) {
            $part = end($parts);

            if ($entity instanceof InvalidPropertyInterface) {
                $val = $entity->getInvalidField($part);
                if ($val !== null) {
                    return $val;
                }
            }

            $val = $entity->get($part);
            if ($val !== null) {
                return $val;
            }
            if (
                $options['default'] !== null
                || !$options['schemaDefault']
                || !$entity->isNew()
            ) {
                return $options['default'];
            }

            return $this->_schemaDefault($parts);
        }
        if (is_array($entity) || $entity instanceof ArrayAccess) {
            $key = array_pop($parts);

            return $entity[$key] ?? $options['default'];
        }

        return null;
    }

    /**
     * Get default value from table schema for given entity field.
     *
     * @param list<string> $parts Each one of the parts in a path for a field name
     * @return mixed
     */
    protected function _schemaDefault(array $parts): mixed
    {
        $table = $this->_getTable($parts);
        if ($table === null) {
            return null;
        }
        $field = end($parts);
        $defaults = $table->getSchema()->defaultValues();
        if ($field === false || !array_key_exists($field, $defaults)) {
            return null;
        }

        return $defaults[$field];
    }

    /**
     * Helper method used to extract all the primary key values out of an array, The
     * primary key column is guessed out of the provided $path array
     *
     * @param mixed $values The list from which to extract primary keys from
     * @param list<string> $path Each one of the parts in a path for a field name
     * @return array|null
     */
    protected function _extractMultiple(mixed $values, array $path): ?array
    {
        if (!is_iterable($values)) {
            return null;
        }
        $table = $this->_getTable($path, false);
        $primary = $table ? (array)$table->getPrimaryKey() : ['id'];

        return (new Collection($values))->extract($primary[0])->toArray();
    }

    /**
     * Fetch the entity or data value for a given path
     *
     * This method will traverse the given path and find the entity
     * or array value for a given path.
     *
     * If you only want the terminal Entity for a path use `leafEntity` instead.
     *
     * @param list<string>|null $path Each one of the parts in a path for a field name
     *  or null to get the entity passed in constructor context.
     * @return \Cake\Datasource\EntityInterface|iterable|null
     * @throws \Cake\Core\Exception\CakeException When properties cannot be read.
     */
    public function entity(?array $path = null): EntityInterface|iterable|null
    {
        if ($path === null) {
            return $this->_context['entity'];
        }

        $oneElement = count($path) === 1;
        if ($oneElement && $this->_isCollection) {
            return null;
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
                if ($table) {
                    return $table->newEmptyEntity();
                }
            }

            $isTraversable = (
                is_iterable($next) ||
                $next instanceof EntityInterface
            );
            if ($isLast || !$isTraversable) {
                return $entity;
            }
            $entity = $next;
        }
        throw new CakeException(sprintf(
            'Unable to fetch property `%s`.',
            implode('.', $path)
        ));
    }

    /**
     * Fetch the terminal or leaf entity for the given path.
     *
     * Traverse the path until an entity cannot be found. Lists containing
     * entities will be traversed if the first element contains an entity.
     * Otherwise, the containing Entity will be assumed to be the terminal one.
     *
     * @param array|null $path Each one of the parts in a path for a field name
     *  or null to get the entity passed in constructor context.
     * @return array Containing the found entity, and remaining un-matched path.
     * @throws \Cake\Core\Exception\CakeException When properties cannot be read.
     */
    protected function leafEntity(?array $path = null): array
    {
        if ($path === null) {
            return $this->_context['entity'];
        }

        $oneElement = count($path) === 1;
        if ($oneElement && $this->_isCollection) {
            throw new CakeException(sprintf(
                'Unable to fetch property `%s`.',
                implode('.', $path)
            ));
        }
        $entity = $this->_context['entity'];
        if ($oneElement) {
            return [$entity, $path];
        }

        if ($path[0] === $this->_rootName) {
            $path = array_slice($path, 1);
        }

        $len = count($path);
        $leafEntity = $entity;
        for ($i = 0; $i < $len; $i++) {
            $prop = $path[$i];
            $next = $this->_getProp($entity, $prop);

            // Did not dig into an entity, return the current one.
            if (is_array($entity) && (!$next instanceof EntityInterface && !$next instanceof Traversable)) {
                return [$leafEntity, array_slice($path, $i - 1)];
            }

            if ($next instanceof EntityInterface) {
                $leafEntity = $next;
            }

            // If we are at the end of traversable elements
            // return the last entity found.
            $isTraversable = (
                is_iterable($next) ||
                $next instanceof EntityInterface
            );
            if (!$isTraversable) {
                return [$leafEntity, array_slice($path, $i)];
            }
            $entity = $next;
        }
        throw new CakeException(sprintf(
            'Unable to fetch property `%s`.',
            implode('.', $path)
        ));
    }

    /**
     * Read property values or traverse arrays/iterators.
     *
     * @param mixed $target The entity/array/collection to fetch $field from.
     * @param string $field The next field to fetch.
     * @return mixed
     */
    protected function _getProp(mixed $target, string $field): mixed
    {
        if (is_array($target) && isset($target[$field])) {
            return $target[$field];
        }
        if ($target instanceof EntityInterface) {
            return $target->get($field);
        }
        if ($target instanceof Traversable) {
            foreach ($target as $i => $val) {
                if ((string)$i === $field) {
                    return $val;
                }
            }

            return false;
        }

        return null;
    }

    /**
     * Check if a field should be marked as required.
     *
     * @param string $field The dot separated path to the field you want to check.
     * @return bool|null
     */
    public function isRequired(string $field): ?bool
    {
        $parts = explode('.', $field);
        $entity = $this->entity($parts);

        $isNew = true;
        if ($entity instanceof EntityInterface) {
            $isNew = $entity->isNew();
        }

        $validator = $this->_getValidator($parts);
        $fieldName = array_pop($parts);
        if (!$validator->hasField($fieldName)) {
            return null;
        }
        if ($this->type($field) !== 'boolean') {
            return !$validator->isEmptyAllowed($fieldName, $isNew);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredMessage(string $field): ?string
    {
        $parts = explode('.', $field);

        $validator = $this->_getValidator($parts);
        $fieldName = array_pop($parts);
        if (!$validator->hasField($fieldName)) {
            return null;
        }

        $ruleset = $validator->field($fieldName);
        if ($ruleset->isEmptyAllowed()) {
            return null;
        }

        return $validator->getNotEmptyMessage($fieldName);
    }

    /**
     * Get field length from validation
     *
     * @param string $field The dot separated path to the field you want to check.
     * @return int|null
     */
    public function getMaxLength(string $field): ?int
    {
        $parts = explode('.', $field);
        $validator = $this->_getValidator($parts);
        $fieldName = array_pop($parts);

        if ($validator->hasField($fieldName)) {
            foreach ($validator->field($fieldName)->rules() as $rule) {
                if ($rule->get('rule') === 'maxLength') {
                    return $rule->get('pass')[0];
                }
            }
        }

        $attributes = $this->attributes($field);
        if (empty($attributes['length'])) {
            return null;
        }

        return (int)$attributes['length'];
    }

    /**
     * Get the field names from the top level entity.
     *
     * If the context is for an array of entities, the 0th index will be used.
     *
     * @return list<string> Array of field names in the table/entity.
     */
    public function fieldNames(): array
    {
        $table = $this->_getTable('0');
        if (!$table) {
            return [];
        }

        return $table->getSchema()->columns();
    }

    /**
     * Get the validator associated to an entity based on naming
     * conventions.
     *
     * @param array $parts Each one of the parts in a path for a field name
     * @return \Cake\Validation\Validator
     * @throws \Cake\Core\Exception\CakeException If validator cannot be retrieved based on the parts.
     */
    protected function _getValidator(array $parts): Validator
    {
        $keyParts = array_filter(array_slice($parts, 0, -1), function ($part) {
            return !is_numeric($part);
        });
        $key = implode('.', $keyParts);
        $entity = $this->entity($parts);

        if (isset($this->_validator[$key])) {
            if (is_object($entity)) {
                $this->_validator[$key]->setProvider('entity', $entity);
            }

            return $this->_validator[$key];
        }

        $table = $this->_getTable($parts);
        if (!$table) {
            throw new InvalidArgumentException(sprintf('Validator not found: `%s`.', $key));
        }
        $alias = $table->getAlias();

        $method = 'default';
        if (is_string($this->_context['validator'])) {
            $method = $this->_context['validator'];
        } elseif (isset($this->_context['validator'][$alias])) {
            $method = $this->_context['validator'][$alias];
        }

        $validator = $table->getValidator($method);

        if (is_object($entity)) {
            $validator->setProvider('entity', $entity);
        }

        return $this->_validator[$key] = $validator;
    }

    /**
     * Get the table instance from a property path
     *
     * @param \Cake\Datasource\EntityInterface|list<string>|string $parts Each one of the parts in a path for a field name
     * @param bool $fallback Whether to fallback to the last found table
     *  when a nonexistent field/property is being encountered.
     * @return \Cake\ORM\Table|null Table instance or null
     */
    protected function _getTable(EntityInterface|array|string $parts, bool $fallback = true): ?Table
    {
        if (!is_array($parts) || count($parts) === 1) {
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
        $assoc = null;
        foreach ($normalized as $part) {
            if ($part === '_joinData') {
                if ($assoc !== null) {
                    /** @var \Cake\ORM\Association\BelongsToMany $assoc */
                    $table = $assoc->junction();
                    $assoc = null;
                    continue;
                }
            } else {
                $associationCollection = $table->associations();
                $assoc = $associationCollection->getByProperty($part);
            }

            if ($assoc === null) {
                if ($fallback) {
                    break;
                }

                return null;
            }

            $table = $assoc->getTarget();
        }

        return $this->_tables[$path] = $table;
    }

    /**
     * Get the abstract field type for a given field name.
     *
     * @param string $field A dot separated path to get a schema type for.
     * @return string|null An abstract data type or null.
     * @see \Cake\Database\TypeFactory
     */
    public function type(string $field): ?string
    {
        $parts = explode('.', $field);
        $table = $this->_getTable($parts);

        return $table?->getSchema()->baseColumnType(array_pop($parts));
    }

    /**
     * Get an associative array of other attributes for a field name.
     *
     * @param string $field A dot separated path to get additional data on.
     * @return array An array of data describing the additional attributes on a field.
     */
    public function attributes(string $field): array
    {
        $parts = explode('.', $field);
        $table = $this->_getTable($parts);
        if (!$table) {
            return [];
        }

        return array_intersect_key(
            (array)$table->getSchema()->getColumn(array_pop($parts)),
            array_flip(static::VALID_ATTRIBUTES)
        );
    }

    /**
     * Check whether a field has an error attached to it
     *
     * @param string $field A dot separated path to check errors on.
     * @return bool Returns true if the errors for the field are not empty.
     */
    public function hasError(string $field): bool
    {
        return $this->error($field) !== [];
    }

    /**
     * Get the errors for a given field
     *
     * @param string $field A dot separated path to check errors on.
     * @return array An array of errors.
     */
    public function error(string $field): array
    {
        $parts = explode('.', $field);
        try {
            /**
             * @var \Cake\Datasource\EntityInterface|null $entity
             * @var list<string> $remainingParts
             */
            [$entity, $remainingParts] = $this->leafEntity($parts);
        } catch (CakeException) {
            return [];
        }
        if ($entity instanceof EntityInterface && count($remainingParts) === 0) {
            return $entity->getErrors();
        }

        if ($entity instanceof EntityInterface) {
            $error = $entity->getError(implode('.', $remainingParts));
            if ($error) {
                return $error;
            }

            return $entity->getError(array_pop($parts));
        }

        return [];
    }
}
