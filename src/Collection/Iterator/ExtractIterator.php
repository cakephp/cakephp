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

use Cake\Collection\Collection;

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
}
