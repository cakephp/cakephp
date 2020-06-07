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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation;

use ReflectionClass;

/**
 * A Proxy class used to remove any extra arguments when the user intended to call
 * a method in another class that is not aware of validation providers signature
 *
 * @method bool extension(mixed $check, array $extensions, array $context = [])
 */
class RulesProvider
{
    /**
     * The class/object to proxy.
     *
     * @var string|object
     */
    protected $_class;

    /**
     * The proxied class' reflection
     *
     * @var \ReflectionClass
     */
    protected $_reflection;

    /**
     * Constructor, sets the default class to use for calling methods
     *
     * @param string|object $class the default class to proxy
     * @throws \ReflectionException
     */
    public function __construct($class = Validation::class)
    {
        $this->_class = $class;
        $this->_reflection = new ReflectionClass($class);
    }

    /**
     * Proxies validation method calls to the Validation class.
     *
     * The last argument (context) will be sliced off, if the validation
     * method's last parameter is not named 'context'. This lets
     * the various wrapped validation methods to not receive the validation
     * context unless they need it.
     *
     * @param string $method the validation method to call
     * @param array $arguments the list of arguments to pass to the method
     * @return bool Whether or not the validation rule passed
     */
    public function __call($method, $arguments)
    {
        $method = $this->_reflection->getMethod($method);
        $argumentList = $method->getParameters();
        if (array_pop($argumentList)->getName() !== 'context') {
            $arguments = array_slice($arguments, 0, -1);
        }
        $object = is_string($this->_class) ? null : $this->_class;

        return $method->invokeArgs($object, $arguments);
    }
}
