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
        $primaryKey = $source->primaryKey();
        $method = is_string($primaryKey) ? 'get' : 'extract';

        $keys = $objects->map(function ($entity) use ($primaryKey, $method) {
            return $entity->{$method}($primaryKey);
        });

        $query = $source
            ->find()
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

        $properties = (new Collection($source->associations()))
            ->indexBy(function ($assoc) {
                return $assoc->name();
            })
            ->map(function ($assoc) {
                return $assoc->property();
            })
            ->toArray();

        $contain = $query->contain();
        $primaryKey = (array)$primaryKey;
        $results = $query
            ->indexBy(function ($e) use ($primaryKey) {
                return implode(';', $e->extract($primaryKey));
            })
            ->toArray();

        $objects = $objects
            ->map(function ($object) use ($results, $contain, $properties, $primaryKey) {
                $key = implode(';', $object->extract($primaryKey));
                if (!isset($results[$key])) {
                    return $object;
                }

                $loaded = $results[$key];
                foreach ($contain as $assoc => $config) {
                    $property = $properties[$assoc];
                    $object->set($property, $loaded->get($property), ['useSetters' => false]);
                    $object->dirty($property, false);
                }
                return $object;
            });

        return $returnSingle ? $objects->first() : $objects->toList();
    }

}
