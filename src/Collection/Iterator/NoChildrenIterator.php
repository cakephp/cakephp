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

/**
 * An iterator that can be used as an argument for other iterators that require
 * a RecursiveIterator but do not want children. This iterator will
 * always behave as having no nested items.
 */
class NoChildrenIterator extends Collection implements RecursiveIterator
{

    /**
     * Returns false as there are no children iterators in this collection
     *
     * @return bool
     */
    public function hasChildren()
    {
        return false;
    }

    /**
     * Returns null as there are no children for this iteration level
     *
     * @return null
     */
    public function getChildren()
    {
        return null;
    }
}
