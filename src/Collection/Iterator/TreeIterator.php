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
namespace Cake\Collection\Iterator;

use Cake\Collection\CollectionTrait;
use Cake\Collection\Iterator\TreePrinter;
use RecursiveIterator;
use RecursiveIteratorIterator;

/**
 * A Recursive iterator used to flatten nested structures and also exposes
 * all Collection methods
 *
 */
class TreeIterator extends RecursiveIteratorIterator
{

    use CollectionTrait;

    /**
     * The iteration mode
     *
     * @var int
     */
    protected $_mode;

    /**
     * Constructor
     *
     * @param RecursiveIterator $items The iterator to flatten.
     * @param int $mode Iterator mode.
     * @param int $flags Iterator flags.
     */
    public function __construct(RecursiveIterator $items, $mode = RecursiveIteratorIterator::SELF_FIRST, $flags = 0)
    {
        parent::__construct($items, $mode, $flags);
        $this->_mode = $mode;
    }

    /**
     * Returns another iterator which will return the values ready to be displayed
     * to a user. It does so by extracting one property from each of the elements
     * and prefixing it with a spacer so that the relative position in the tree
     * can be visualized.
     *
     * Both $valuePath and $keyPath can be a string with a property name to extract
     * or a dot separated path of properties that should be followed to get the last
     * one in the path.
     *
     * Alternatively, $valuePath and $keyPath can be callable functions. They will get
     * the current element as first parameter, the current iteration key as second
     * parameter, and the iterator instance as third argument.
     *
     * ### Example
     *
     * ```
     *  $printer = (new Collection($treeStructure))->listNested()->printer('name');
     * ```
     *
     * Using a closure:
     *
     * ```
     *  $printer = (new Collection($treeStructure))
     *      ->listNested()
     *      ->printer(function ($item, $key, $iterator) {
     *          return $item->name;
     *      });
     * ```
     *
     * @param string|callable $valuePath The property to extract or a callable to return
     * the display value
     * @param string|callable $keyPath The property to use as iteration key or a
     * callable returning the key value.
     * @param string $spacer The string to use for prefixing the values according to
     * their depth in the tree
     * @return \Cake\Collection\Iterator\TreePrinter
     */
    public function printer($valuePath, $keyPath = null, $spacer = '__')
    {
        if (!$keyPath) {
            $counter = 0;
            $keyPath = function () use (&$counter) {
                return $counter++;
            };
        }
        return new TreePrinter(
            $this->getInnerIterator(),
            $valuePath,
            $keyPath,
            $spacer,
            $this->_mode
        );
    }
}
