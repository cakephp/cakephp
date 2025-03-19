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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\TupleComparison;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;

/**
 * Contains methods that are capable of injecting eagerly loaded associations into
 * entities or lists of entities by using the same syntax as the EagerLoader.
 *
 * @internal
 */
class LazyEagerLoader
{
    /**
     * Loads the specified associations in the passed entity or list of entities
     * by executing extra queries in the database and merging the results in the
     * appropriate properties.
     *
     * The properties for the associations to be loaded will be overwritten on each entity.
     *
     * @param \Cake\Datasource\EntityInterface|array<\Cake\Datasource\EntityInterface> $entities a single entity or list of entities
     * @param array $contain A `contain()` compatible array.
     * @see \Cake\ORM\Query::contain()
     * @param \Cake\ORM\Table $source The table to use for fetching the top level entities
     * @return \Cake\Datasource\EntityInterface|array<\Cake\Datasource\EntityInterface>
     */
    public function loadInto(EntityInterface|array $entities, array $contain, Table $source): EntityInterface|array
    {
        $returnSingle = false;

        if ($entities instanceof EntityInterface) {
            $entities = [$entities];
            $returnSingle = true;
        }

        $query = $this->_getQuery($entities, $contain, $source);
        $associations = array_keys($query->getContain());

        $entities = $this->_injectResults($entities, $query, $associations, $source);

        /** @var \Cake\Datasource\EntityInterface|array<\Cake\Datasource\EntityInterface> */
        return $returnSingle ? array_shift($entities) : $entities;
    }

    /**
     * Builds a query for loading the passed list of entity objects along with the
     * associations specified in $contain.
     *
     * @param array<\Cake\Datasource\EntityInterface> $entities The original entities
     * @param array $contain The associations to be loaded
     * @param \Cake\ORM\Table $source The table to use for fetching the top level entities
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function _getQuery(array $entities, array $contain, Table $source): SelectQuery
    {
        $primaryKey = $source->getPrimaryKey();
        $method = is_string($primaryKey) ? 'get' : 'extract';

        $keys = Hash::map($entities, '{*}', fn(EntityInterface $entity) => $entity->{$method}($primaryKey));

        $query = $source
            ->find()
            ->select((array)$primaryKey)
            ->where(function (QueryExpression $exp, SelectQuery $q) use ($primaryKey, $keys, $source) {
                if (is_array($primaryKey) && count($primaryKey) === 1) {
                    $primaryKey = current($primaryKey);
                }

                if (is_string($primaryKey)) {
                    return $exp->in($source->aliasField($primaryKey), $keys);
                }

                $types = array_intersect_key($q->getDefaultTypes(), array_flip($primaryKey));
                $primaryKey = array_map($source->aliasField(...), $primaryKey);

                return new TupleComparison($primaryKey, $keys, $types, 'IN');
            })
            ->enableAutoFields()
            ->contain($contain);

        foreach ($query->getEagerLoader()->attachableAssociations($source) as $loadable) {
            $config = $loadable->getConfig();
            $config['includeFields'] = true;
            $loadable->setConfig($config);
        }

        return $query;
    }

    /**
     * Returns a map of property names where the association results should be injected
     * in the top level entities.
     *
     * @param \Cake\ORM\Table $source The table having the top level associations
     * @param array<string> $associations The name of the top level associations
     * @return array<string, string>
     */
    protected function _getPropertyMap(Table $source, array $associations): array
    {
        $map = [];
        $container = $source->associations();
        foreach ($associations as $assoc) {
            /** @var \Cake\ORM\Association $association */
            $association = $container->get($assoc);
            $map[$assoc] = $association->getProperty();
        }

        return $map;
    }

    /**
     * Injects the results of the eager loader query into the original list of
     * entities.
     *
     * @param array<\Cake\Datasource\EntityInterface> $entities The original list of entities
     * @param \Cake\ORM\Query\SelectQuery $query The query to load results
     * @param array<string> $associations The top level associations that were loaded
     * @param \Cake\ORM\Table $source The table where the entities came from
     * @return array<\Cake\Datasource\EntityInterface>
     */
    protected function _injectResults(
        array $entities,
        SelectQuery $query,
        array $associations,
        Table $source,
    ): array {
        $injected = [];
        $properties = $this->_getPropertyMap($source, $associations);
        $primaryKey = (array)$source->getPrimaryKey();
        /** @var array<\Cake\Datasource\EntityInterface> $results */
        $results = $query
            ->all()
            ->indexBy(fn(EntityInterface $e) => implode(';', $e->extract($primaryKey)))
            ->toArray();

        foreach ($entities as $k => $object) {
            $key = implode(';', $object->extract($primaryKey));
            if (!isset($results[$key])) {
                $injected[$k] = $object;
                continue;
            }

            $loaded = $results[$key];
            foreach ($associations as $assoc) {
                $property = $properties[$assoc];
                $object->set($property, $loaded->get($property), ['useSetters' => false]);
                $object->setDirty($property, false);
            }
            $injected[$k] = $object;
        }

        return $injected;
    }
}
