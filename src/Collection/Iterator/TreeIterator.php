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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Collection\Iterator;

use Cake\Collection\CollectionInterface;
use Cake\Collection\CollectionTrait;
use RecursiveIterator;
use RecursiveIteratorIterator;

/**
 * A Recursive iterator used to flatten nested structures and also exposes
 * all Collection methods
 *
 * @template-extends \RecursiveIteratorIterator<\RecursiveIterator>
 */
class TreeIterator extends RecursiveIteratorIterator implements CollectionInterface
{
    use CollectionTrait;

    /**
     * The iteration mode
     *
     * @var int
     */
    protected int $_mode;

    /**
     * Constructor
     *
     * @param \RecursiveIterator<mixed, mixed> $items The iterator to flatten.
     * @param int $mode Iterator mode.
     * @param int $flags Iterator flags.
     */
    public function __construct(
        RecursiveIterator $items,
        int $mode = RecursiveIteratorIterator::SELF_FIRST,
        int $flags = 0
    ) {
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
     * @param callable|string $valuePath The property to extract or a callable to return
     * the display value
     * @param callable|string|null $keyPath The property to use as iteration key or a
     * callable returning the key value.
     * @param string $spacer The string to use for prefixing the values according to
     * their depth in the tree
     * @return \Cake\Collection\Iterator\TreePrinter
     */
    public function printer(
        callable|string $valuePath,
        callable|string|null $keyPath = null,
        string $spacer = '__'
    ): TreePrinter {
        if (!$keyPath) {
            $counter = 0;
            $keyPath = function () use (&$counter): int {
                return $counter++;
            };
        }

        /** @var \RecursiveIterator $iterator */
        $iterator = $this->getInnerIterator();

        return new TreePrinter(
            $iterator,
            $valuePath,
            $keyPath,
            $spacer,
            $this->_mode
        );
    }
}
