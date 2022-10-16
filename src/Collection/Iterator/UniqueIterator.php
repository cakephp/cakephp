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

class UniqueIterator extends FilterIterator
{
    protected array $_exists = [];

    public function __construct($items, $key)
    {
        return parent::__construct($items, function($item) use ($key) {

            if (is_null($key)) {
                $key = $value = $item;
            } else {
                $value = $item[$key] ?? null;
            }

            if(isset($this->_exists[$value])) {
                return false;
            }

            $this->_exists[$value] = $key;
            return true;
        });
    }
}
