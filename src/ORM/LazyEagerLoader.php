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
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM;

use Cake\Collection\Collection;
use Cake\Database\Expression\TupleComparison;
use Cake\Datasource\EntityInterface;

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
     * @param \Cake\Datasource\EntityInterface|array $entities a single entity or list of entities
     * @param array $contain A `contain()` compatible array.
     * @see \Cake\ORM\Query\contain()
     * @param \Cake\ORM\Table $source The table to use for fetching the top level entities
     * @return \Cake\Datasource\EntityInterface|array
     */
    public function loadInto($entities, array $contain, Table $source)
    {
        $returnSingle = false;

        if ($entities instanceof EntityInterface) {
            $entities = [$entities];
            $returnSingle = true;
        }

        $entities = new Collection($entities);
        $query = $this->_getQuery($entities, $contain, $source);
        $associations = array_keys($query->contain());

        $entities = $this->_injectResults($entities, $query, $associations, $source);

        return $returnSingle ? array_shift($entities) : $entities;
    }

    /**
     * Builds a query for loading the passed list of entity objects along with the
     * associations specified in $contain.
     *
     * @param \Cake\Collection\CollectionInterface $objects The original entities
     * @param array $contain The associations to be loaded
     * @param \Cake\ORM\Table $source The table to use for fetching the top level entities
     * @return \Cake\ORM\Query
     */
    protected function _getQuery($objects, $contain, $source)
    {
        $primaryKey = $source->primaryKey();
        $method = is_string($primaryKey) ? 'get' : 'extract';

        $keys = $objects->map(function ($entity) use ($primaryKey, $method) {
            return $entity->{$method}($primaryKey);
        });

        $query = $source
            ->find()
            ->select((array)$primaryKey)
            ->where(function ($exp, $q) use ($primaryKey, $keys, $source) {
                if (is_array($primaryKey) && count($primaryKey) === 1) {
                    $primaryKey = current($primaryKey);
                }

                if (is_string($primaryKey)) {
                    return $exp->in($source->aliasField($primaryKey), $keys->toList());
                }

                $types = array_intersect_key($q->defaultTypes(), array_flip($primaryKey));
                $primaryKey = array_map([$source, 'aliasField'], $primaryKey);

                return new TupleComparison($primaryKey, $keys->toList(), $types, 'IN');
            })
            ->contain($contain);

        foreach ($query->eagerLoader()->attachableAssociations($source) as $loadable) {
            $config = $loadable->config();
            $config['includeFields'] = true;
            $loadable->config($config);
        }

        return $query;
    }

    /**
     * Returns a map of property names where the association results should be injected
     * in the top level entities.
     *
     * @param \Cake\ORM\Table $source The table having the top level associations
     * @param array $associations The name of the top level associations
     * @return array
     */
    protected function _getPropertyMap($source, $associations)
    {
        $map = [];
        $container = $source->associations();
        foreach ($associations as $assoc) {
            $map[$assoc] = $container->get($assoc)->property();
        }

        return $map;
    }

    /**
     * Injects the results of the eager loader query into the original list of
     * entities.
     *
     * @param array|\Traversable $objects The original list of entities
     * @param \Cake\Collection\CollectionInterface|\Cake\Database\Query $results The loaded results
     * @param array $associations The top level associations that were loaded
     * @param \Cake\ORM\Table $source The table where the entities came from
     * @return array
     */
    protected function _injectResults($objects, $results, $associations, $source)
    {
        $injected = [];
        $properties = $this->_getPropertyMap($source, $associations);
        $primaryKey = (array)$source->primaryKey();
        $results = $results
            ->indexBy(function ($e) use ($primaryKey) {
                return implode(';', $e->extract($primaryKey));
            })
            ->toArray();

        foreach ($objects as $k => $object) {
            $key = implode(';', $object->extract($primaryKey));
            if (!isset($results[$key])) {
                $injected[$k] = $object;
                continue;
            }

            $loaded = $results[$key];
            foreach ($associations as $assoc) {
                $property = $properties[$assoc];
                $object->set($property, $loaded->get($property), ['useSetters' => false]);
                $object->dirty($property, false);
            }
            $injected[$k] = $object;
        }

        return $injected;
    }
}
