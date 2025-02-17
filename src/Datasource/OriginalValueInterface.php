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
namespace Cake\Datasource;

/**
 * Describes the methods related to storing and retrieving original values
 * of fields in an entity, before it was modified.
 */
interface OriginalValueInterface
{
    /**
     * Returns whether a field is an original one.
     * Original fields are those that an entity was instantiated with.
     *
     * @param string $name Name
     * @return bool
     */
    public function isOriginalField(string $name): bool;

    /**
     * Returns an array of original fields.
     * Original fields are those that an entity was initialized with.
     *
     * @return array<string>
     */
    public function getOriginalFields(): array;

    /**
     * Returns an array with the requested original fields
     * stored in this entity, indexed by field name.
     *
     * @param array<string> $fields List of fields to be returned
     * @return array<string, mixed>
     */
    public function extractOriginal(array $fields): array;

    /**
     * Returns an array with only the original fields
     * stored in this entity, indexed by field name.
     *
     * @param array<string> $fields List of fields to be returned
     * @return array<string, mixed>
     */
    public function extractOriginalChanged(array $fields): array;

    /**
     * Returns whether a field has an original value
     *
     * @param string $field
     * @return bool
     */
    public function hasOriginal(string $field): bool;

    /**
     * Returns the original value of a field.
     *
     * @param string $field The name of the field.
     * @param bool $allowFallback whether to allow falling back to the current field value if no original exists
     * @return mixed
     */
    public function getOriginal(string $field, bool $allowFallback = true): mixed;

    /**
     * Gets all original values of the entity.
     *
     * @return array
     */
    public function getOriginalValues(): array;
}
