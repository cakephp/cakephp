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

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
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
     * @param \Cake\Datasource\EntityInterface|\Cake\Datasource\EntityInterface[] $entities a single entity or list of entities
     * @param array $contain A `contain()` compatible array.
     * @see \Cake\ORM\Query::contain()
     * @param \Cake\ORM\Table $source The table to use for fetching the top level entities
     * @return \Cake\Datasource\EntityInterface|\Cake\Datasource\EntityInterface[]
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
        $associations = array_keys($query->getContain());

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
    protected function _getQuery(CollectionInterface $objects, array $contain, Table $source): Query
    {
        $primaryKey = $source->getPrimaryKey();
        $method = is_string($primaryKey) ? 'get' : 'extract';

        $keys = $objects->map(function ($entity) use ($primaryKey, $method) {
            return $entity->{$method}($primaryKey);
        });

        $query = $source
            ->find()
            ->select((array)$primaryKey)
            ->where(function ($exp, $q) use ($primaryKey, $keys, $source) {
                /**
                 * @var \Cake\Database\Expression\QueryExpression $exp
                 * @var \Cake\ORM\Query $q
                 */
                if (is_array($primaryKey) && count($primaryKey) === 1) {
                    $primaryKey = current($primaryKey);
                }

                if (is_string($primaryKey)) {
                    return $exp->in($source->aliasField($primaryKey), $keys->toList());
                }

                $types = array_intersect_key($q->getDefaultTypes(), array_flip($primaryKey));
                $primaryKey = array_map([$source, 'aliasField'], $primaryKey);

                return new TupleComparison($primaryKey, $keys->toList(), $types, 'IN');
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
     * @param string[] $associations The name of the top level associations
     * @return string[]
     */
    protected function _getPropertyMap(Table $source, array $associations): array
    {
        $map = [];
        $container = $source->associations();
        foreach ($associations as $assoc) {
            /** @psalm-suppress PossiblyNullReference */
            $map[$assoc] = $container->get($assoc)->getProperty();
        }

        return $map;
    }

    /**
     * Injects the results of the eager loader query into the original list of
     * entities.
     *
     * @param \Cake\Datasource\EntityInterface[]|\Traversable $objects The original list of entities
     * @param \Cake\Collection\CollectionInterface|\Cake\ORM\Query $results The loaded results
     * @param string[] $associations The top level associations that were loaded
     * @param \Cake\ORM\Table $source The table where the entities came from
     * @return array
     */
    protected function _injectResults(iterable $objects, $results, array $associations, Table $source): array
    {
        $injected = [];
        $properties = $this->_getPropertyMap($source, $associations);
        $primaryKey = (array)$source->getPrimaryKey();
        $results = $results
            ->indexBy(function ($e) use ($primaryKey) {
                /** @var \Cake\Datasource\EntityInterface $e */
                return implode(';', $e->extract($primaryKey));
            })
            ->toArray();

        foreach ($objects as $k => $object) {
            $key = implode(';', $object->extract($primaryKey));
            if (!isset($results[$key])) {
                $injected[$k] = $object;
                continue;
            }

            /** @var \Cake\Datasource\EntityInterface $loaded */
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
