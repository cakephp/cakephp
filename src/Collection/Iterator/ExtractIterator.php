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

use ArrayIterator;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;

/**
 * Creates an iterator from another iterator that extract the requested column
 * or property based on a path
 */
class ExtractIterator extends Collection
{

    /**
     * A callable responsible for extracting a single value for each
     * item in the collection.
     *
     * @var callable
     */
    protected $_extractor;

    /**
     * Creates the iterator that will return the requested property for each value
     * in the collection expressed in $path
     *
     * ### Example:
     *
     * Extract the user name for all comments in the array:
     *
     * ```
     * $items = [
     *  ['comment' => ['body' => 'cool', 'user' => ['name' => 'Mark']],
     *  ['comment' => ['body' => 'very cool', 'user' => ['name' => 'Renan']]
     * ];
     * $extractor = new ExtractIterator($items, 'comment.user.name'');
     * ```
     *
     * @param array|\Traversable $items The list of values to iterate
     * @param string $path a dot separated string symbolizing the path to follow
     * inside the hierarchy of each value so that the column can be extracted.
     */
    public function __construct($items, $path)
    {
        $this->_extractor = $this->_propertyExtractor($path);
        parent::__construct($items);
    }

    /**
     * Returns the column value defined in $path or null if the path could not be
     * followed
     *
     * @return mixed
     */
    public function current()
    {
        $extractor = $this->_extractor;

        return $extractor(parent::current());
    }

    /**
     * {@inheritDoc}
     *
     * We perform here some strictness analysis so that the
     * iterator logic is bypassed entirely.
     *
     * @return \Iterator
     */
    public function unwrap()
    {
        $iterator = $this->getInnerIterator();

        if ($iterator instanceof CollectionInterface) {
            $iterator = $iterator->unwrap();
        }

        if (get_class($iterator) !== ArrayIterator::class) {
            return $this;
        }

        // ArrayIterator can be traversed strictly.
        // Let's do that for performance gains

        $callback = $this->_extractor;
        $res = [];

        foreach ($iterator->getArrayCopy() as $k => $v) {
            $res[$k] = $callback($v);
        }

        return new ArrayIterator($res);
    }
}
