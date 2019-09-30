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

use Cake\Collection\CollectionTrait;
use RecursiveIteratorIterator;

/**
 * Iterator for flattening elements in a tree structure while adding some
 * visual markers for their relative position in the tree
 */
class TreePrinter extends RecursiveIteratorIterator
{
    use CollectionTrait;

    /**
     * A callable to generate the iteration key
     *
     * @var callable
     */
    protected $_key;

    /**
     * A callable to extract the display value
     *
     * @var callable
     */
    protected $_value;

    /**
     * Cached value for the current iteration element
     *
     * @var mixed
     */
    protected $_current;

    /**
     * The string to use for prefixing the values according to their depth in the tree.
     *
     * @var string
     */
    protected $_spacer;

    /**
     * Constructor
     *
     * @param \RecursiveIterator $items The iterator to flatten.
     * @param string|callable $valuePath The property to extract or a callable to return
     * the display value.
     * @param string|callable $keyPath The property to use as iteration key or a
     * callable returning the key value.
     * @param string $spacer The string to use for prefixing the values according to
     * their depth in the tree.
     * @param int $mode Iterator mode.
     */
    public function __construct($items, $valuePath, $keyPath, $spacer, $mode = RecursiveIteratorIterator::SELF_FIRST)
    {
        parent::__construct($items, $mode);
        $this->_value = $this->_propertyExtractor($valuePath);
        $this->_key = $this->_propertyExtractor($keyPath);
        $this->_spacer = $spacer;
    }

    /**
     * Returns the current iteration key
     *
     * @return mixed
     */
    public function key()
    {
        $extractor = $this->_key;

        return $extractor($this->_fetchCurrent(), parent::key(), $this);
    }

    /**
     * Returns the current iteration value
     *
     * @return string
     */
    public function current()
    {
        $extractor = $this->_value;
        $current = $this->_fetchCurrent();
        $spacer = str_repeat($this->_spacer, $this->getDepth());

        return $spacer . $extractor($current, parent::key(), $this);
    }

    /**
     * Advances the cursor one position
     *
     * @return void
     */
    public function next()
    {
        parent::next();
        $this->_current = null;
    }

    /**
     * Returns the current iteration element and caches its value
     *
     * @return mixed
     */
    protected function _fetchCurrent()
    {
        if ($this->_current !== null) {
            return $this->_current;
        }

        return $this->_current = parent::current();
    }
}
