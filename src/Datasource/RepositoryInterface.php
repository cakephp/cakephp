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
namespace Cake\Datasource;

use Cake\Datasource\EntityInterface;

/**
 * Describes the methods that any class representing a data storage should
 * comply with.
 */
interface RepositoryInterface {

/**
 * Creates a new Query for this repository and applies some defaults based on the
 * type of search that was selected.
 *
 * ### Model.beforeFind event
 *
 * Each find() will trigger a `Model.beforeFind` event for all attached
 * listeners. Any listener can set a valid result set using $query
 *
 * @param string $type the type of query to perform
 * @param array|\ArrayAccess $options An array that will be passed to Query::applyOptions()
 * @return \Cake\ORM\Query
 */
	public function find($type = 'all', $options = []);

/**
 * Returns a single record after finding it by its primary key, if no record is
 * found this method throws an exception.
 *
 * ###Example:
 *
 * {{{
 * $id = 10;
 * $article = $articles->get($id);
 *
 * $article = $articles->get($id, ['contain' => ['Comments]]);
 * }}}
 *
 * @param mixed $primaryKey primary key value to find
 * @param array|\ArrayAccess $options options accepted by `Table::find()`
 * @throws \Cake\ORM\Error\RecordNotFoundException if the record with such id
 * could not be found
 * @return \Cake\Datasource\EntityInterface
 * @see RepositoryInterface::find()
 */
	public function get($primaryKey, $options = []);

/**
 * Creates a new Query instance for this repository
 *
 * @return \Cake\ORM\Query
 */
	public function query();

/**
 * Update all matching records.
 *
 * Sets the $fields to the provided values based on $conditions.
 * This method will *not* trigger beforeSave/afterSave events. If you need those
 * first load a collection of records and update them.
 *
 * @param array $fields A hash of field => new value.
 * @param mixed $conditions Conditions to be used, accepts anything Query::where()
 * can take.
 * @return bool Success Returns true if one or more rows are affected.
 */
	public function updateAll($fields, $conditions);

/**
 * Delete all matching records.
 *
 * Deletes all records matching the provided conditions.
 *
 * This method will *not* trigger beforeDelete/afterDelete events. If you
 * need those first load a collection of records and delete them.
 *
 * This method will *not* execute on associations' `cascade` attribute. You should
 * use database foreign keys + ON CASCADE rules if you need cascading deletes combined
 * with this method.
 *
 * @param mixed $conditions Conditions to be used, accepts anything Query::where()
 * can take.
 * @return bool Success Returns true if one or more rows are affected.
 * @see RepositoryInterface::delete()
 */
	public function deleteAll($conditions);

/**
 * Returns true if there is any record in this repository matching the specified
 * conditions.
 *
 * @param array|\ArrayAccess $conditions list of conditions to pass to the query
 * @return bool
 */
	public function exists($conditions);

/**
 * Persists an entity based on the fields that are marked as dirty and
 * returns the same entity after a successful save or false in case
 * of any error.
 *
 * @param \Cake\Datasource\EntityInterface $entity the entity to be saved
 * @param array|\ArrayAccess $options The options to use when saving.
 * @return \Cake\Datasource\EntityInterface|bool
 */
	public function save(EntityInterface $entity, $options = []);

/**
 * Delete a single entity.
 *
 * Deletes an entity and possibly related associations from the database
 * based on the 'dependent' option used when defining the association.
 *
 * @param \Cake\Datasource\EntityInterface $entity The entity to remove.
 * @param array|\ArrayAccess $options The options fo the delete.
 * @return bool success
 */
	public function delete(EntityInterface $entity, $options = []);

/**
 * Create a new entity + associated entities from an array.
 *
 * This is most useful when hydrating request data back into entities.
 * For example, in your controller code:
 *
 * {{{
 * $article = $this->Articles->newEntity($this->request->data());
 * }}}
 *
 * The hydrated entity will correctly do an insert/update based
 * on the primary key data existing in the database when the entity
 * is saved. Until the entity is saved, it will be a detached record.
 *
 * @param array $data The data to build an entity with.
 * @param array $options A list of options for the object hydration.
 * @return \Cake\Datasource\EntityInterface
 */
	public function newEntity(array $data = [], array $options = []);

/**
 * Create a list of entities + associated entities from an array.
 *
 * This is most useful when hydrating request data back into entities.
 * For example, in your controller code:
 *
 * {{{
 * $articles = $this->Articles->newEntities($this->request->data());
 * }}}
 *
 * The hydrated entities can then be iterated and saved.
 *
 * @param array $data The data to build an entity with.
 * @param array $options A list of options for the objects hydration.
 * @return array An array of hydrated records.
 */
	public function newEntities(array $data, array $options = []);

/**
 * Merges the passed `$data` into `$entity` respecting the accessible
 * fields configured on the entity. Returns the same entity after being
 * altered.
 *
 * This is most useful when editing an existing entity using request data:
 *
 * {{{
 * $article = $this->Articles->patchEntity($article, $this->request->data());
 * }}}
 *
 * @param \Cake\Datasource\EntityInterface $entity the entity that will get the
 * data merged in
 * @param array $data key value list of fields to be merged into the entity
 * @param array $options A list of options for the object hydration.
 * @return \Cake\Datasource\EntityInterface
 */
	public function patchEntity(EntityInterface $entity, array $data, array $options = []);

/**
 * Merges each of the elements passed in `$data` into the entities
 * found in `$entities` respecting the accessible fields configured on the entities.
 * Merging is done by matching the primary key in each of the elements in `$data`
 * and `$entities`.
 *
 * This is most useful when editing a list of existing entities using request data:
 *
 * {{{
 * $article = $this->Articles->patchEntities($articles, $this->request->data());
 * }}}
 *
 * @param array|\Traversable $entities the entities that will get the
 * data merged in
 * @param array $data list of arrays to be merged into the entities
 * @param array $options A list of options for the objects hydration.
 * @return array
 */
	public function patchEntities($entities, array $data, array $options = []);

}
