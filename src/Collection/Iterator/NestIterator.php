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
namespace Cake\Collection\Iterator;

use Cake\Collection\Collection;
use RecursiveIterator;
use Traversable;

/**
 * A type of collection that is aware of nested items and exposes methods to
 * check or retrieve them
 */
class NestIterator extends Collection implements RecursiveIterator
{

    /**
     * The name of the property that contains the nested items for each element
     *
     * @var string|callable
     */
    protected $_nestKey;

    /**
     * Constructor
     *
     * @param array|\Traversable $items Collection items.
     * @param string|callable $nestKey the property that contains the nested items
     * If a callable is passed, it should return the childrens for the passed item
     */
    public function __construct($items, $nestKey)
    {
        parent::__construct($items);
        $this->_nestKey = $nestKey;
    }

    /**
     * Returns a traversable containing the children for the current item
     *
     * @return \Traversable
     */
    public function getChildren()
    {
        $property = $this->_propertyExtractor($this->_nestKey);

        return new static($property($this->current()), $this->_nestKey);
    }

    /**
     * Returns true if there is an array or a traversable object stored under the
     * configured nestKey for the current item
     *
     * @return bool
     */
    public function hasChildren()
    {
        $property = $this->_propertyExtractor($this->_nestKey);
        $children = $property($this->current());

        if (is_array($children)) {
            return !empty($children);
        }

        return $children instanceof Traversable;
    }
}
