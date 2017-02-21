<?php
/**
 * Created by IntelliJ IDEA.
 * User: marlinc
 * Date: 4-1-17
 * Time: 10:12
 */
namespace Cake\Datasource;

use Muffin\Webservice\Query;
use Muffin\Webservice\WebserviceResultSetInterface;


/**
 * Exposes the methods for storing the associations that should be eager loaded
 * for a endpoint once a query is provided and delegates the job of creating the
 * required joins and decorating the results so that those associations can be
 * part of the result set.
 */
interface EagerLoaderInterface
{
    /**
     * Sets the list of associations that should be eagerly loaded along for a
     * specific endpoint using when a query is provided. The list of associated endpoints
     * passed to this method must have been previously set as associations using the
     * Endpoint API.
     *
     * Associations can be arbitrarily nested using dot notation or nested arrays,
     * this allows this object to calculate joins or any additional queries that
     * must be executed to bring the required associated data.
     *
     * Accepted options per passed association:
     *
     * - foreignKey: Used to set a different field to match both endpoints, if set to false
     *   no join conditions will be generated automatically
     * - fields: An array with the fields that should be fetched from the association
     * - queryBuilder: Equivalent to passing a callable instead of an options array
     * - matching: Whether to inform the association class that it should filter the
     *  main query by the results fetched by that class.
     * - joinType: For joinable associations, the SQL join type to use.
     * - strategy: The loading strategy to use (join, select, subquery)
     *
     * @param array|string $associations list of endpoint aliases to be queried.
     * When this method is called multiple times it will merge previous list with
     * the new one.
     * @return array Containments.
     */
    public function contain($associations = []);

    /**
     * Remove any existing non-matching based containments.
     *
     * This will reset/clear out any contained associations that were not
     * added via matching().
     *
     * @return void
     */
    public function clearContain();

    /**
     * Set whether or not contained associations will load fields automatically.
     *
     * @param bool|null $value The value to set.
     * @return bool The current value.
     */
    public function autoFields($value = null);

    /**
     * Adds a new association to the list that will be used to filter the results of
     * any given query based on the results of finding records for that association.
     * You can pass a dot separated path of associations to this method as its first
     * parameter, this will translate in setting all those associations with the
     * `matching` option.
     *
     * If called with no arguments it will return the current tree of associations to
     * be matched.
     *
     * @param string|null $assoc A single association or a dot separated path of associations.
     * @param callable|null $builder the callback function to be used for setting extra
     * options to the filtering query
     * @param array $options Extra options for the association matching, such as 'joinType'
     * and 'fields'
     * @return array The resulting containments array
     */
    public function matching($assoc = null, callable $builder = null, $options = []);

    /**
     * Returns the fully normalized array of associations that should be eagerly
     * loaded for a endpoint. The normalized array will restructure the original array
     * by sorting all associations under one key and special options under another.
     *
     * Each of the levels of the associations tree will converted to a \Cake\Datasource\EagerLoadable
     * object, that contains all the information required for the association objects
     * to load the information from the webservice.
     *
     * Additionally it will set an 'instance' key per association containing the
     * association instance from the corresponding source endpoint
     *
     * @param \Cake\datasource\RepositoryInterface $repository The endpoint containing the association that
     * will be normalized
     * @return array
     */
    public function normalized(RepositoryInterface $repository);

    /**
     * Modifies the passed query to apply joins or any other transformation required
     * in order to eager load the associations described in the `contain` array.
     * This method will not modify the query for loading external associations, i.e.
     * those that cannot be loaded without executing a separate query.
     *
     * @param \Muffin]Webservice\Query $query The query to be modified
     * @param \Cake\Datasource\RepositoryInterface $repository The repository containing the associations
     * @param bool $includeFields whether to append all fields from the associations
     * to the passed query. This can be overridden according to the settings defined
     * per association in the containments array
     * @return void
     */
    public function attachAssociations(Query $query, RepositoryInterface $repository, $includeFields);

    /**
     * Returns an array with the associations that can be fetched using a single query,
     * the array keys are the association aliases and the values will contain an array
     * with \Cake\Datasource\EagerLoadable objects.
     *
     * @param \Cake\Datasource\RepositoryInterface $repository The endpoint containing the associations to be
     * attached
     * @return array
     */
    public function attachableAssociations(RepositoryInterface $repository);

    /**
     * Returns an array with the associations that need to be fetched using a
     * separate query, each array value will contain a \Cake\Datasource\EagerLoadable object.
     *
     * @param \Cake\Datasource\RepositoryInterface $repository The endpoint containing the associations
     * to be loaded
     * @return \Cake\Datasource\EagerLoadable[]
     */
    public function externalAssociations(RepositoryInterface $repository);

    /**
     * Decorates the passed statement object in order to inject data from associations
     * that cannot be joined directly.
     *
     * @param \Cake\Datasource\QueryInterface $query The query for which to eager load external
     * associations
     * @param \Muffin\Webservice\WebserviceResultSetInterface $webserviceResultSet The statement created after executing the $query
     * @return \Muffin\Webservice\WebserviceResultSetInterface statement modified statement with extra loaders
     */
    public function loadExternal($query, WebserviceResultSetInterface $webserviceResultSet);

    /**
     * Returns an array having as keys a dotted path of associations that participate
     * in this eager loader. The values of the array will contain the following keys
     *
     * - alias: The association alias
     * - instance: The association instance
     * - canBeJoined: Whether or not the association will be loaded using a JOIN
     * - resourceClass: The entity that should be used for hydrating the results
     * - nestKey: A dotted path that can be used to correctly insert the data into the results.
     * - matching: Whether or not it is an association loaded through `matching()`.
     *
     * @param \Cake\Datasource\RepositoryInterface $repository The endpoint containing the association that
     * will be normalized
     * @return array
     */
    public function associationsMap(RepositoryInterface $repository);
}
