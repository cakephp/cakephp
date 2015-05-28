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

/**
 * Session based persistent storage for authenticated user record.
 */
class SessionStorage implements StorageInterface
{

    use InstanceConfigTrait;

    /**
     * User record.
     *
     * @var array
     */
    protected $_user;

    /**
     * Session object.
     *
     * @var \Cake\Network\Session
     */
    protected $_session;

    /**
     * Default configuration for this class
     *
     * @var array
     */
    protected $_defaultConfig = [
        'key' => 'Auth.User'
    ];

    /**
     * Constructor.
     *
     * @param \Cake\Network\Request $request Request instance.
     * @param array $config Configuration list.
     */
    public function __construct(Request $request, array $config = [])
    {
        $this->_session = $request->session();
        $this->config($config);
    }

    /**
     * Get user record from session.
     *
     * @return array|null User record if available else null.
     */
    public function get()
    {
        if ($this->_user) {
            return $this->_user;
        }

        $this->_user = $this->_session->read($this->_config['key']);
        return $this->_user;
    }

    /**
     * Set user record to session.
     *
     * The session id is also renewed to help mitigate issues with session replays.
     *
     * @param array $user User record.
     * @return void
     */
    public function set(array $user)
    {
        $this->_user = $user;

        $this->_session->renew();
        $this->_session->write($this->_config['key'], $user);
    }

    /**
     * Remove user record from session.
     *
     * The session id is also renewed to help mitigate issues with session replays.
     *
     * @return void
     */
    public function remove()
    {
        unset($this->_user);

        $this->_session->delete($this->_config['key']);
        $this->_session->renew();
    }
}
