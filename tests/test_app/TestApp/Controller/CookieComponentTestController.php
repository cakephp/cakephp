<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.1.6
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Controller;

use Cake\Controller\Controller;

/**
 * CookieComponentTestController class
 */
class CookieComponentTestController extends Controller
{
    /**
     * @var array
     */
    public $components = [
        'Cookie',
    ];

    public $autoRender = false;

    /**
     * view
     *
     * @param string|null $key Encryption key used. By defaults,
     *   CookieComponent::_config['key'].
     */
    public function view($key = null)
    {
        if (isset($key)) {
            $this->Cookie->setConfig('key', $key);
        }
        $this->set('ValueFromRequest', $this->request->getCookie('NameOfCookie'));
        $this->set('ValueFromCookieComponent', $this->Cookie->read('NameOfCookie'));
    }

    /**
     * action to set a cookie
     *
     * @param string|null $key Encryption key used. By defaults,
     *   CookieComponent::_config['key'].
     */
    public function set_cookie($key = null)
    {
        if (isset($key)) {
            $this->Cookie->setConfig('key', $key);
        }
        $this->Cookie->write('NameOfCookie', 'abc');
    }

    public function remove_cookie($key)
    {
        $this->Cookie->delete($key);
    }
}
