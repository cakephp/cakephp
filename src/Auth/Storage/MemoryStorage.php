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

/**
 * Memory based non-persistent storage for authenticated user record.
 */
class MemoryStorage implements StorageInterface
{

    /**
     * User record.
     *
     * @var array|null
     */
    protected $_user;

    /**
     * Redirect url.
     *
     * @var string
     */
    protected $_redirectUrl;

    /**
     * {@inheritDoc}
     */
    public function read()
    {
        return $this->_user;
    }

    /**
     * {@inheritDoc}
     */
    public function write(array $user)
    {
        $this->_user = $user;
    }

    /**
     * {@inheritDoc}
     */
    public function delete()
    {
        $this->_user = null;
    }

    /**
     * {@inheritDoc}
     */
    public function redirectUrl($url = null)
    {
        if ($url === null) {
            return $this->_redirectUrl;
        }

        if ($url === false) {
            $this->_redirectUrl = null;

            return null;
        }

        $this->_redirectUrl = $url;
    }
}
