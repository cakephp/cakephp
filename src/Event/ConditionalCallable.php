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
 * @since         2.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Event;

use Cake\Collection\Collection;

/**
 * Conditional callable can be used to wrap regular callable in a set of conditions.
 * Conditions are then evaluated at the time of invocation and wrapped callable
 * will be executed only if they all pass.
 */
class ConditionalCallable
{
    /**
     * @var callable Wrapped callable
     */
    private $callable;

    /**
     * @var array Conditions that will be evaluated before invocation of the callable
     */
    private $options;

    /**
     * @param callable $callable Wrapped callable
     * @param array $options Conditions that will be evaluated before invocation of the callable
     */
    public function __construct(callable $callable, $options)
    {
        $this->callable = $callable;
        $this->options = $options;
    }

    /**
     * Invocation method.
     * @return mixed
     */
    public function __invoke()
    {
        $args = func_get_args();

        if (!$this->isIfTrue($args) || $this->isUnlessTrue($args)) {
            return null;
        }

        return $this->call($this->callable, $args);
    }

    /**
     * Evaluates if conditions.
     *
     * @param array $args Arguments to be passed to conditions
     * @return bool
     */
    public function isIfTrue($args)
    {
        if (!isset($this->options['if'])) {
            return true;
        }

        return $this->getConditions('if')->every(function ($callable) use ($args) {
            return $this->call($callable, $args);
        });
    }

    /**
     * Evaluates unless conditions.
     *
     * @param array $args Arguments to be passed to conditions
     * @return bool
     */
    public function isUnlessTrue($args)
    {
        if (!isset($this->options['unless'])) {
            return false;
        }

        return $this->getConditions('unless')->every(function ($callable) use ($args) {
            return $this->call($callable, $args);
        });
    }

    /**
     * Returns a collection of conditions ready to be evaluated.
     *
     * @param string $type Type of conditions
     * @return Collection
     */
    public function getConditions($type)
    {
        $conditions = (array)$this->options[$type];

        // in case that callable is given in form [$this, 'method']
        if (is_callable($conditions)) {
            $conditions = [$conditions];
        }

        return collection($conditions);
    }

    /**
     * @param callable $callable Callable to be executed
     * @param array $args Arguments to be passed to the callable
     * @return mixed Return value from the callable
     */
    public function call($callable, $args)
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
