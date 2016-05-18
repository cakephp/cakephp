<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event\Decorator;

class BaseDecorator
{

    /**
     * Callable
     *
     * @var callable
     */
    protected $_callable;

    /**
     * Decorator options
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Constructor.
     *
     * @param $callable $callable Callable.
     * @param array $options Decorator options.
     */
    public function __construct(callable $callable, array $options = [])
    {
        $this->_callable = $callable;
        $this->_options = $options;
    }

    /**
     * Invoke
     *
     * @link http://php.net/manual/en/language.oop5.magic.php#object.invoke
     * @return mixed
     */
    public function __invoke()
    {
        return $this->call($this->_callable, func_get_args());
    }

    /**
     * Calls a callable with the passed arguments.
     *
     * @param $callable The callable.
     * @param array $args Arguments for the callable.
     * @return mixed
     */
    public function call(callable $callable, $args)
    {
        switch (count($args)) {
            case 0:
                return $callable();
            case 1:
                return $callable($args[0]);
            case 2:
                return $callable($args[0], $args[1]);
            case 3:
                return $callable($args[0], $args[1], $args[2]);
            default:
                return call_user_func_array($callable, $args);
        }
    }
}
