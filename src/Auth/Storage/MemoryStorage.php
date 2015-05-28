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
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Auth\Storage;

use Cake\Core\InstanceConfigTrait;
use Cake\Network\Request;

class MemoryStorage implements StorageInterface
{
    protected $_user;

    public function get()
    {
        return $this->_user;
    }

    public function set(array $user)
    {
        $this->_user = $user;
    }

    public function remove()
    {
        $this->_user = null;
    }
}
