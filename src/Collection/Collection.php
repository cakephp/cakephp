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
namespace Cake\Collection;

use ArrayIterator;
use InvalidArgumentException;
use IteratorIterator;
use LogicException;
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
     * Throws an exception.
     *
     * Issuing a count on a Collection can have many side effects, some making the
     * Collection unusable after the count operation.
     *
     * @return void
     * @throws \LogicException
     */
    public function count()
    {
        throw new LogicException('You cannot issue a count on a Collection.');
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
