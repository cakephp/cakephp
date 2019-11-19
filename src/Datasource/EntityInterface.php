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
namespace Cake\Datasource;

use ArrayAccess;
use JsonSerializable;

/**
 * Describes the methods that any class representing a data storage should
 * comply with.
 *
 * @property mixed $id Alias for commonly used primary key.
 */
interface EntityInterface extends ArrayAccess, JsonSerializable
{
    /**
     * Sets hidden fields.
     *
     * @param string[] $fields An array of fields to hide from array exports.
     * @param bool $merge Merge the new fields with the existing. By default false.
     * @return $this
     */
    public function setHidden(array $fields, bool $merge = false);

    /**
     * Gets the hidden fields.
     *
     * @return string[]
     */
    public function getHidden(): array;

    /**
     * Sets the virtual fields on this entity.
     *
     * @param string[] $fields An array of fields to treat as virtual.
     * @param bool $merge Merge the new fields with the existing. By default false.
     * @return $this
     */
    public function setVirtual(array $fields, bool $merge = false);

    /**
     * Gets the virtual fields on this entity.
     *
     * @return string[]
     */
    public function getVirtual(): array;

    /**
     * Sets the dirty status of a single field.
     *
     * @param string $field the field to set or check status for
     * @param bool $isDirty true means the field was changed, false means
     * it was not changed. Default true.
     * @return $this
     */
    public function setDirty(string $field, bool $isDirty = true);

    /**
     * Checks if the entity is dirty or if a single field of it is dirty.
     *
     * @param string|null $field The field to check the status for. Null for the whole entity.
     * @return bool Whether the field was changed or not
     */
    public function isDirty(?string $field = null): bool;

    /**
     * Gets the dirty fields.
     *
     * @return string[]
     */
    public function getDirty(): array;

    /**
     * Returns whether this entity has errors.
     *
     * @param bool $includeNested true will check nested entities for hasErrors()
     * @return bool
     */
    public function hasErrors(bool $includeNested = true): bool;

    /**
     * Returns all validation errors.
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Returns validation errors of a field
     *
     * @param string $field Field name to get the errors from
     * @return array
     */
    public function getError(string $field): array;

    /**
     * Sets error messages to the entity
     *
     * @param array $errors The array of errors to set.
     * @param bool $overwrite Whether or not to overwrite pre-existing errors for $fields
     * @return $this
     */
    public function setErrors(array $errors, bool $overwrite = false);

    /**
     * Sets errors for a single field
     *
     * @param string $field The field to get errors for, or the array of errors to set.
     * @param string|array $errors The errors to be set for $field
     * @param bool $overwrite Whether or not to overwrite pre-existing errors for $field
     * @return $this
     */
    public function setError(string $field, $errors, bool $overwrite = false);

    /**
     * Stores whether or not a field value can be changed or set in this entity.
     *
     * @param string|array $field single or list of fields to change its accessibility
     * @param bool $set true marks the field as accessible, false will
     * mark it as protected.
     * @return $this
     */
    public function setAccess($field, bool $set);

    /**
     * Checks if a field is accessible
     *
     * @param string $field Field name to check
     * @return bool
     */
    public function isAccessible(string $field): bool;

    /**
     * Sets the source alias
     *
     * @param string $alias the alias of the repository
     * @return $this
     */
    public function setSource(string $alias);

    /**
     * Returns the alias of the repository from which this entity came from.
     *
     * @return string
     */
    public function getSource(): string;

    /**
     * Returns an array with the requested original fields
     * stored in this entity, indexed by field name.
     *
     * @param string[] $fields List of fields to be returned
     * @return array
     */
    public function extractOriginal(array $fields): array;

    /**
     * Returns an array with only the original fields
     * stored in this entity, indexed by field name.
     *
     * @param string[] $fields List of fields to be returned
     * @return array
     */
    public function extractOriginalChanged(array $fields): array;

    /**
     * Sets one or multiple fields to the specified value
     *
     * @param string|array $field the name of field to set or a list of
     * fields with their respective values
     * @param mixed $value The value to set to the field or an array if the
     * first argument is also an array, in which case will be treated as $options
     * @param array $options options to be used for setting the field. Allowed option
     * keys are `setter` and `guard`
     * @return $this
     */
    public function set($field, $value = null, array $options = []);

    /**
     * Returns the value of a field by name
     *
     * @param string $field the name of the field to retrieve
     * @return mixed
     */
    public function &get(string $field);

    /**
     * Returns the original value of a field.
     *
     * @param string $field The name of the field.
     * @return mixed
     */
    public function getOriginal(string $field);

    /**
     * Gets all original values of the entity.
     *
     * @return array
     */
    public function getOriginalValues(): array;

    /**
     * Returns whether this entity contains a field named $field
     * regardless of if it is empty.
     *
     * @param string|string[] $field The field to check.
     * @return bool
     */
    public function has($field): bool;

    /**
     * Removes a field or list of fields from this entity
     *
     * @param string|string[] $field The field to unset.
     * @return $this
     */
    public function unset($field);

    /**
     * Get the list of visible fields.
     *
     * @return string[] A list of fields that are 'visible' in all representations.
     */
    public function getVisible(): array;

    /**
     * Returns an array with all the visible fields set in this entity.
     *
     * *Note* hidden fields are not visible, and will not be output
     * by toArray().
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Returns an array with the requested fields
     * stored in this entity, indexed by field name
     *
     * @param string[] $fields list of fields to be returned
     * @param bool $onlyDirty Return the requested field only if it is dirty
     * @return array
     */
    public function extract(array $fields, bool $onlyDirty = false): array;

    /**
     * Sets the entire entity as clean, which means that it will appear as
     * no fields being modified or added at all. This is an useful call
     * for an initial object hydration
     *
     * @return void
     */
    public function clean(): void;

    /**
     * Set the status of this entity.
     *
     * Using `true` means that the entity has not been persisted in the database,
     * `false` indicates that the entity has been persisted.
     *
     * @param bool $new Indicate whether or not this entity has been persisted.
     * @return $this
     */
    public function setNew(bool $new);

    /**
     * Returns whether or not this entity has already been persisted.
     *
     * @return bool Whether or not the entity has been persisted.
     */
    public function isNew(): bool;
}
