<?php
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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Collection;

use ArrayIterator;
use BadMethodCallException;
use InvalidArgumentException;
use IteratorIterator;
use Serializable;
use Traversable;

/**
 * A collection is an immutable list of elements with a handful of functions to
 * iterate, group, transform and extract information from it.
 */
class Collection extends IteratorIterator implements CollectionInterface, Serializable
{

    use CollectionTrait;

    /**
     * Constructor. You can provide an array or any traversable object
     *
     * @param array|\Traversable $items Items.
     * @throws \InvalidArgumentException If passed incorrect type for items.
     */
    public function __construct($items)
    {
        if (is_array($items)) {
            $items = new ArrayIterator($items);
        }

        if (!($items instanceof Traversable)) {
            $msg = 'Only an array or \Traversable is allowed for Collection';
            throw new InvalidArgumentException($msg);
        }

        parent::__construct($items);
    }

    /**
     * Returns a string representation of this object that can be used
     * to reconstruct it
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->buffered());
    }

    /**
     * Unserializes the passed string and rebuilds the Collection instance
     *
     * @param string $collection The serialized collection
     * @return void
     */
    public function unserialize($collection)
    {
        $this->__construct(unserialize($collection));
    }

    /**
     * Dynamic method handler
     *
     * Collections do not allow access to methods of the inner iterator,
     * if that iterator is one of the PHP base classes as many of
     * these methods allow in-place mutation which breaks the immutability
     * Collection tries to provide.
     *
     * @param string $name Method name.
     * @param array $args Method arguments.
     * @return void
     * @throws \BadMethodCallException
     */
    public function __call($name, $args)
    {
        if (!method_exists(ArrayIterator::class, $name)) {
            $inner = $this->getInnerIterator();

            return call_user_func_array([$inner, $name], $args);
        }
        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', get_class($this), $name));
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'count' => iterator_count($this),
        ];
    }
}
