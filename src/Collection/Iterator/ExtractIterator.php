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

use ArrayIterator;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Iterator;

/**
 * Creates an iterator from another iterator that extract the requested column
 * or property based on a path
 *
 * @template TKey
 * @extends \Cake\Collection\Collection<TKey, mixed>
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
     *     ['comment' => ['body' => 'cool', 'user' => ['name' => 'Mark']]],
     *     ['comment' => ['body' => 'very cool', 'user' => ['name' => 'Renan']]],
     * ];
     * $extractor = new ExtractIterator($items, 'comment.user.name');
     * ```
     *
     * @param iterable<TKey, mixed> $items The list of values to iterate
     * @param callable|string $path A dot separated path of column to follow
     * so that the final one can be returned or a callable that will take care
     * of doing that.
     */
    public function __construct(iterable $items, callable|string $path)
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
    public function current(): mixed
    {
        $extractor = $this->_extractor;

        return $extractor(parent::current());
    }

    /**
     * @inheritDoc
     */
    public function unwrap(): Iterator
    {
        $iterator = $this->getInnerIterator();

        if ($iterator instanceof CollectionInterface) {
            $iterator = $iterator->unwrap();
        }

        if ($iterator::class !== ArrayIterator::class) {
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
