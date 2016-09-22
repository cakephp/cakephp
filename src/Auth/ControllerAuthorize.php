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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Exception\Exception;
use Cake\Network\Request;

/**
 * An authorization adapter for AuthComponent. Provides the ability to authorize
 * using a controller callback. Your controller's isAuthorized() method should
 * return a boolean to indicate whether or not the user is authorized.
 *
 * ```
 *  public function isAuthorized($user)
 *  {
 *      if ($this->request->param('admin')) {
 *          return $user['role'] === 'admin';
 *      }
 *      return !empty($user);
 *  }
 * ```
 *
 * The above is simple implementation that would only authorize users of the
 * 'admin' role to access admin routing.
 *
 * @see \Cake\Controller\Component\AuthComponent::$authenticate
 */
class ControllerAuthorize extends BaseAuthorize
{

    /**
     * Controller for the request.
     *
     * @var \Cake\Controller\Controller
     */
    protected $_Controller = null;

    /**
     * {@inheritDoc}
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);
        $this->controller($registry->getController());
    }

    /**
     * Get/set the controller this authorize object will be working with. Also
     * checks that isAuthorized is implemented.
     *
     * @param \Cake\Controller\Controller|null $controller null to get, a controller to set.
     * @return \Cake\Controller\Controller
     * @throws \Cake\Core\Exception\Exception If controller does not have method `isAuthorized()`.
     */
    public function controller(Controller $controller = null)
    {
        if ($controller) {
            if (!method_exists($controller, 'isAuthorized')) {
                throw new Exception(sprintf(
                    '%s does not implement an isAuthorized() method.',
                    get_class($controller)
                ));
            }
            $this->_Controller = $controller;
        }

        return $this->_Controller;
    }

    /**
     * Checks user authorization using a controller callback.
     *
     * @param array|\ArrayAccess $user Active user data
     * @param \Cake\Network\Request $request Request instance.
     * @return bool
     */
    public function authorize($user, Request $request)
    {
        return (bool)$this->_Controller->isAuthorized($user);
    }
}
