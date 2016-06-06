<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.1.6
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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

    /**
     * view
     *
     * @param string|null $key Encryption key used. By defaults,
     *   CookieComponent::_config['key'].
     */
    public function view($key = null)
    {
        if (isset($key)) {
            $this->Cookie->config('key', $key);
        }
        $this->set('ValueFromRequest', $this->request->cookie('NameOfCookie'));
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
        $this->autoRender = false;
        if (isset($key)) {
            $this->Cookie->config('key', $key);
        }
        $this->Cookie->write('NameOfCookie', 'abc');
    }
}
