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
use Cake\ORM\Table;


class LazyEagerLoader
{

    public function loadInto($objects, array $contain, Table $source)
    {
        $returnSingle = false;

        if ($objects instanceof EntityInterface) {
            $objects = [$objects];
            $returnSingle = true;
        }

        $objects = new Collection($objects);
        $query = $this->_getQuery($objects,  $contain, $source);
        $associations = array_keys($query->contain());

        $objects = $this->_injectResults($objects, $query, $associations, $source);
        return $returnSingle ? array_shift($objects) : $objects;
    }

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
            ->where(function ($exp) use ($primaryKey, $keys, $source) {
                if (is_array($primaryKey) && count($primaryKey) === 1) {
                    $primaryKey = current($primaryKey);
                }

                if (is_string($primaryKey)) {
                    return $exp->in($source->aliasField($primaryKey), $keys->toList());
                }

                $primaryKey = array_map([$source, 'aliasField'], $primaryKey);
                return new TupleComparison($primaryKey, $keys->toList());
            })
            ->contain($contain);

        foreach ($query->eagerLoader()->attachableAssociations($source) as $loadable) {
            $config = $loadable->config();
            $config['includeFields'] = true;
            $loadable->config($config);
        }

        return $query;
    }

    protected function _getPropertyMap($source, $associations)
    {
        $map = [];
        foreach ($associations as $assoc) {
            $map[$assoc] = $source->associations()->get($assoc)->property();
        }
        return $map;
    }

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
