<?php
declare(strict_types=1);

namespace Cake\Container\Inflector;

use Cake\Container\ContainerAwareInterface;
use IteratorAggregate;

/**
 * @template-extends \IteratorAggregate<string, \Cake\Container\Inflector\Inflector>
 */
interface InflectorAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    /**
     * @param string $type
     * @param callable|null $callback
     * @return \Cake\Container\Inflector\Inflector
     */
    public function add(string $type, ?callable $callback = null): Inflector;

    /**
     * @param object $object
     * @return mixed
     */
    public function inflect(object $object): mixed;
}
