<?php
declare(strict_types=1);

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
use function Cake\Core\deprecationWarning;

/**
 * A Proxy class used to remove any extra arguments when the user intended to call
 * a method in another class that is not aware of validation providers signature
 *
 * @method bool extension(mixed $check, array $extensions, array $context = [])
 * @deprecated 5.2.0 This class is no longer used. Cake\Validation\Validation
 *  is now directly used as a provider in Cake\Validation\Validator.
 */
class RulesProvider
{
    /**
     * The class/object to proxy.
     *
     * @var object|string
     */
    protected object|string $_class;

    /**
     * The proxied class' reflection
     *
     * @var \ReflectionClass<object>
     */
    protected ReflectionClass $_reflection;

    /**
     * Constructor, sets the default class to use for calling methods
     *
     * @param object|string $class the default class to proxy
     * @throws \ReflectionException
     * @phpstan-param object|class-string $class
     */
    public function __construct(object|string $class = Validation::class)
    {
        deprecationWarning(
            '5.2.0',
            sprintf(
                'The class Cake\Validation\RulesProvider is deprecated. '
                . 'Directly set %s as a validation provider.',
                (is_string($class) ? $class : get_class($class)),
            ),
        );

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
     * @return bool Whether the validation rule passed
     */
    public function __call(string $method, array $arguments): bool
    {
        $method = $this->_reflection->getMethod($method);
        $argumentList = $method->getParameters();
        /** @var \ReflectionParameter $argument */
        $argument = array_pop($argumentList);
        if ($argument->getName() !== 'context') {
            $arguments = array_slice($arguments, 0, -1);
        }
        $object = is_string($this->_class) ? null : $this->_class;

        return $method->invokeArgs($object, $arguments);
    }
}
