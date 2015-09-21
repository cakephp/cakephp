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
namespace Cake\Datasource;

use Cake\Collection\Collection;

/**
 * Generic ResultSet decorator. This will make any traversable object appear to
 * be a database result
 *
 * @return void
 */
class ResultSetDecorator extends Collection implements ResultSetInterface
{

    /**
     * Make this object countable.
     *
     * Part of the Countable interface. Calling this method
     * will convert the underlying traversable object into an array and
     * get the count of the underlying data.
     *
     * @return int
     */
    public function count()
    {
        if ($this->getInnerIterator() instanceof \Countable) {
            return $this->getInnerIterator()->count();
        }

        return count($this->toArray());
    }
}
