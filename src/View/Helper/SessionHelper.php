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
 * @since         1.1.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\View\Helper;
use Cake\View\View;

/**
 * Session Helper.
 *
 * Session reading from the view.
 *
 * @link https://book.cakephp.org/3.0/en/views/helpers/session.html
 * @deprecated 3.0.2 Use request->session() instead.
 */
class SessionHelper extends Helper
{

    /**
     * Constructor
     *
     * @param \Cake\View\View $View The View this helper is being attached to.
     * @param array $config Configuration settings for the helper.
     */
    public function __construct(View $View, array $config = [])
    {
        deprecationWarning(
            'SessionHelper is deprecated and will be removed in 4.0.0. ' .
            'Use request->session() instead.'
        );

        parent::__construct($View, $config);
    }

    /**
     * Reads a session value for a key or returns values for all keys.
     *
     * In your view:
     * ```
     * $this->Session->read('Controller.sessKey');
     * ```
     * Calling the method without a param will return all session vars
     *
     * @param string|null $name The name of the session key you want to read
     * @return mixed Values from the session vars
     */
    public function read($name = null)
    {
        return $this->_View->getRequest()->getSession()->read($name);
    }

    /**
     * Checks if a session key has been set.
     *
     * In your view:
     * ```
     * $this->Session->check('Controller.sessKey');
     * ```
     *
     * @param string $name Session key to check.
     * @return bool
     */
    public function check($name)
    {
        return $this->_View->getRequest()->getSession()->check($name);
    }

    /**
     * Event listeners.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }
}
