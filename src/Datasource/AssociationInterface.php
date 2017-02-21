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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

/**
 * An Association is a relationship established between two repositories and is used
 * to configure and customize the way interconnected records are retrieved.
 */
interface AssociationInterface
{
    /**
     * Association type for one to one associations.
     *
     * @var string
     */
    const ONE_TO_ONE = 'oneToOne';

    /**
     * Association type for one to many associations.
     *
     * @var string
     */
    const ONE_TO_MANY = 'oneToMany';

    /**
     * Association type for many to many associations.
     *
     * @var string
     */
    const MANY_TO_MANY = 'manyToMany';

    /**
     * Association type for many to one associations.
     *
     * @var string
     */
    const MANY_TO_ONE = 'manyToOne';

    /**
     * Sets the name for this association. If no argument is passed then the current
     * configured name will be returned
     *
     * @param string|null $name Name to be assigned
     * @return string
     */
    public function name($name = null);

    /**
     * Sets whether or not cascaded deletes should also fire callbacks. If no
     * arguments are passed, the current configured value is returned
     *
     * @param bool|null $cascadeCallbacks cascade callbacks switch value
     * @return bool
     */
    public function cascadeCallbacks($cascadeCallbacks = null);

    /**
     * The class name of the target repository object
     *
     * @return string
     */
    public function className();

    /**
     * Sets the repository instance for the source side of the association. If no arguments
     * are passed, the current configured repository instance is returned
     *
     * @param \Cake\Datasource\RepositoryInterface|null $repository the instance to be assigned as source side
     * @return \Cake\Datasource\RepositoryInterface
     */
    public function source(RepositoryInterface $repository = null);

    /**
     * Sets the repository instance for the target side of the association. If no arguments
     * are passed, the current configured repository instance is returned
     *
     * @param \Cake\Datasource\RepositoryInterface|null $repository the instance to be assigned as target side
     * @return \Cake\Datasource\RepositoryInterface
     */
    public function target(RepositoryInterface $repository = null);

    /**
     * Sets a list of conditions to be always included when fetching records from
     * the target association. If no parameters are passed the current list is returned
     *
     * @param array|null $conditions list of conditions to be used
     * @see \Cake\Datasource\QueryInterface::where() for examples on the format of the array
     * @return array
     */
    public function conditions($conditions = null);

    /**
     * Sets the name of the field representing the binding field with the target repository.
     * When not manually specified the primary key of the owning side repository is used.
     *
     * If no parameters are passed the current field is returned
     *
     * @param string|null $key the repository field to be used to link both repositories together
     * @return string|array
     */
    public function bindingKey($key = null);

    /**
     * Sets the name of the field representing the foreign key to the target repository.
     * If no parameters are passed the current field is returned
     *
     * @param string|null $key the key to be used to link both repositories together
     * @return string|array
     */
    public function foreignKey($key = null);

    /**
     * Sets whether the records on the target repository are dependent on the source repository.
     *
     * This is primarily used to indicate that records should be removed if the owning record in
     * the source repository is deleted.
     *
     * If no parameters are passed the current setting is returned.
     *
     * @param bool|null $dependent Set the dependent mode. Use null to read the current state.
     * @return bool
     */
    public function dependent($dependent = null);

    /**
     * Whether this association can be expressed directly in a query join
     *
     * @param array $options custom options key that could alter the return value
     * @return bool
     */
    public function canBeJoined(array $options = []);

    /**
     * Sets the property name that should be filled with data from the target repository
     * in the source repository record.
     * If no arguments are passed, the currently configured type is returned.
     *
     * @param string|null $name The name of the association property. Use null to read the current value.
     * @return string
     */
    public function property($name = null);

    /**
     * Sets the strategy name to be used to fetch associated records. Keep in mind
     * that some association types might not implement but a default strategy,
     * rendering any changes to this setting void.
     * If no arguments are passed, the currently configured strategy is returned.
     *
     * @param string|null $name The strategy type. Use null to read the current value.
     * @return string
     * @throws \InvalidArgumentException When an invalid strategy is provided.
     */
    public function strategy($name = null);

    /**
     * Sets the default finder to use for fetching rows from the target repository.
     * If no parameters are passed, it will return the currently configured
     * finder name.
     *
     * @param string|null $finder the finder name to use
     * @return string
     */
    public function finder($finder = null);

    /**
     * Returns a modified row after appending a property for this association
     * with the default empty value according to whether the association was
     * joined or fetched externally.
     *
     * @param array $row The row to set a default on.
     * @param bool $joined Whether or not the row is a result of a direct join
     *   with this association
     * @return array
     */
    public function defaultRowValue($row, $joined);

    /**
     * Proxies the finding operation to the target repository's find method
     * and modifies the query accordingly based of this association
     * configuration
     *
     * @param string|array|null $type the type of query to perform, if an array is passed,
     *   it will be interpreted as the `$options` parameter
     * @param array $options The options to for the find
     * @see \Cake\Datasource\RepositoryInterface::find()
     * @return \Cake\Datasource\QueryInterface
     */
    public function find($type = null, array $options = []);

    /**
     * Proxies the update operation to the target repository's updateAll method
     *
     * @param array $fields A hash of field => new value.
     * @param mixed $conditions Conditions to be used, accepts anything Query::where()
     * can take.
     * @see \Cake\Datasource\RepositoryInterface::updateAll()
     * @return bool Success Returns true if one or more rows are affected.
     */
    public function updateAll($fields, $conditions);

    /**
     * Proxies the delete operation to the target repository's deleteAll method
     *
     * @param mixed $conditions Conditions to be used, accepts anything Query::where()
     * can take.
     * @return bool Success Returns true if one or more rows are affected.
     * @see \Cake\Datasource\RepositoryInterface::deleteAll()
     */
    public function deleteAll($conditions);

    /**
     * Proxies property retrieval to the target repository. This is handy for getting this
     * association's associations
     *
     * @param string $property the property name
     * @return \Cake\Datasource\AssociationInterface
     * @throws \RuntimeException if no association with such name exists
     */
    public function __get($property);

    /**
     * Proxies the isset call to the target repository. This is handy to check if the
     * target repository has another association with the passed name
     *
     * @param string $property the property name
     * @return bool true if the property exists
     */
    public function __isset($property);

    /**
     * Proxies method calls to the target repository.
     *
     * @param string $method name of the method to be invoked
     * @param array $argument List of arguments passed to the function
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $argument);

    /**
     * Get the relationship type.
     *
     * @return string Constant of either ONE_TO_ONE, MANY_TO_ONE, ONE_TO_MANY or MANY_TO_MANY.
     */
    public function type();

    /**
     * Handles cascading a delete from an associated model.
     *
     * Each implementing class should handle the cascaded delete as
     * required.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity that started the cascaded delete.
     * @param array $options The options for the original delete.
     * @return bool Success
     */
    public function cascadeDelete(EntityInterface $entity, array $options = []);

    /**
     * Returns whether or not the passed repository is the owning side for this
     * association. This means that rows in the 'target' repository would miss important
     * or required information if the row in 'source' did not exist.
     *
     * @param \Cake\Datasource\RepositoryInterface $side The potential RepositoryInterface with ownership
     * @return bool
     */
    public function isOwningSide(RepositoryInterface $side);

    /**
     * Extract the target's association data our from the passed entity and proxies
     * the saving operation to the target repository.
     *
     * @param \Cake\Datasource\EntityInterface $entity the data to be saved
     * @param array|\ArrayObject $options The options for saving associated data.
     * @return bool|\Cake\Datasource\EntityInterface false if $entity could not be saved, otherwise it returns
     * the saved entity
     * @see \Cake\Datasource\RepositoryInterface::save()
     */
    public function saveAssociated(EntityInterface $entity, array $options = []);
}
