<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\TestSuite;

use \PHPUnit_Extensions_TestDecorator;
use \PHPUnit_Framework_Test;
use \PHPUnit_Framework_TestResult;

/**
 * A decorator class used to run a test case once for each permutation defined
 * in the decorated test suite.
 *
 */
class TestPermutationDecorator extends PHPUnit_Extensions_TestDecorator {

/**
 * An array containing callable methods that will be executed before the test
 * suite is run
 *
 * @var array
 */
	protected $_permutations = [];

/**
 * Constructor
 *
 * @param PHPUnit_Framework_Test $test The test or suite to decorate
 * @param array $permutations An array containing callable methods that will
 * be executed before the test suite is run
 */
	public function __construct(PHPUnit_Framework_Test $test, array $permutations) {
		parent::__construct($test);
		$this->_permutations = $permutations;
	}

/**
 * Returns the count of single test methods that this decorator contains, taking in
 * consideration the possible permutations
 *
 * @return void
 */
	public function count() {
		return count($this->_permutations) * $this->test->count();
	}

/**
 * Runs each of the test methods for this test suite for each of the provided
 * permutations.
 *
 * @param PHPUnit_Framework_TestResult $result
 * @param  mixed $filter
 * @param  array $groups
 * @param  array $excludeGroups
 * @param  boolean $processIsolation
 * @return PHPUnit_Framework_TestResult
 */
	public function run(PHPUnit_Framework_TestResult $result = null, $filter = false, array $groups = [], array $excludeGroups = [], $processIsolation = false) {
		if ($result === null) {
			$result = $this->createResult();
		}

		foreach ($this->_permutations as $permutation) {
			if ($result->shouldStop()) {
				break;
			}
			if (is_callable($permutation)) {
				$permutation($this->test);
			}
			$this->test->run($result, $filter, $groups, $excludeGroups, $processIsolation);
		}

		return $result;
	}

}
