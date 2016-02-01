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
	public function __construct($callable, $options)
	{
		$this->callable = $callable;
		$this->options = $options;
	}

	/**
	 * Invocation method.
	 */
	public function __invoke()
	{
		if (!$this->isIfTrue() || $this->isUnlessTrue()) {
			return null;
		}

		$callable = $this->callable;

		switch (func_num_args()) {
			case 0:
				return $callable();
			case 1:
				return $callable(func_get_arg(0));
			case 2:
				return $callable(func_get_arg(0), func_get_arg(1));
			case 3:
				return $callable(func_get_arg(0), func_get_arg(1), func_get_arg(2));
			default:
				return call_user_func_array($callable, func_get_args());
		}
	}

	/**
	 * Evaluates if conditions.
	 *
	 * @return bool
	 */
	private function isIfTrue()
	{
		if (!isset($this->options['if'])) {
			return true;
		} else {
			return $this->getConditions('if')->every(function ($callable) {
				return $callable();
			});
		}
	}

	/**
	 * Evaluates unless conditions.
	 *
	 * @return bool
	 */
	private function isUnlessTrue()
	{
		if (!isset($this->options['unless'])) {
			return false;
		} else {
			return $this->getConditions('unless')->every(function ($callable) {
				return $callable();
			});
		}
	}

	/**
	 * Returns a collection of conditions ready to be evaluated.
	 *
	 * @param string $type Type of conditions
	 * @return Collection
	 */
	private function getConditions($type)
	{
		$conditions = (array) $this->options[$type];

		// in case that callable is given in form [$this, 'method']
		if (count($conditions) == 2 && is_object($conditions[0]) && is_string($conditions[1])) {
			$conditions = [$conditions];
		}

		return collection($conditions);
	}
}