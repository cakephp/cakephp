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
use Cake\Core\InstanceConfigTrait;
use Cake\Network\Request;

/**
 * Abstract base authorization adapter for AuthComponent.
 *
 * @see \Cake\Controller\Component\AuthComponent::$authenticate
 */
abstract class BaseAuthorize
{

    use InstanceConfigTrait;

    /**
     * ComponentRegistry instance for getting more components.
     *
     * @var \Cake\Controller\ComponentRegistry
     */
    protected $_registry;

    /**
     * Default config for authorize objects.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * Constructor
     *
     * @param \Cake\Controller\ComponentRegistry $registry The controller for this request.
     * @param array $config An array of config. This class does not use any config.
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->_registry = $registry;
        $this->config($config);
    }

    /**
     * Checks user authorization.
     *
     * @param array|\ArrayAccess $user Active user data
     * @param \Cake\Network\Request $request Request instance.
     * @return bool
     */
    abstract public function authorize($user, Request $request);
}
