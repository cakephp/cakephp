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

class SessionStorage implements StorageInterface
{

    use InstanceConfigTrait;

    protected $_session;

    protected $_defaultConfig = [
        'key' => 'Auth.User'
    ];

    public function __construct(Request $request, array $config = [])
    {
        $this->_session = $request->session();
        $this->config($config);
    }

    public function get()
    {
        if (!$this->_session->check($this->_config['key'])) {
            return;
        }

        return $this->_session->read($this->_config['key']);
    }

    public function set(array $user)
    {
        $this->_session->renew();
        $this->_session->write($this->_config['key'], $user);
    }

    public function remove()
    {
        $this->_session->delete($this->_config['key']);
        $this->_session->renew();
    }
}
