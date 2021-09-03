<?php
declare(strict_types=1);

/**
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event\Decorator;

/**
 * Common base class for event decorator subclasses.
 */
abstract class AbstractDecorator
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
     * @param callable $callable Callable.
     * @param array<string, mixed> $options Decorator options.
     */
    public function __construct(callable $callable, array $options = [])
    {
        $this->_callable = $callable;
        $this->_options = $options;
    }

    /**
     * Invoke
     *
     * @link https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @return mixed
     */
    public function __invoke()
    {
        return $this->_call(func_get_args());
    }

    /**
     * Calls the decorated callable with the passed arguments.
     *
     * @param array $args Arguments for the callable.
     * @return mixed
     */
    protected function _call(array $args)
    {
        $callable = $this->_callable;

        return $callable(...$args);
    }
}
