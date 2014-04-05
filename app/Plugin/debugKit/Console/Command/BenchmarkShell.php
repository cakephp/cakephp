<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('String', 'Utility');

/**
 * Benchmark Shell Class
 *
 * Provides basic benchmarking of application requests
 * functionally similar to Apache AB
 *
 * @since         DebugKit 1.0
 * @todo Print/export time detail information
 * @todo Export/graphing of data to .dot format for graphviz visualization
 * @todo Make calculated results round to leading significant digit position of std dev.
 */
class BenchmarkShell extends Shell {

/**
 * Main execution of shell
 *
 * @return void
 */
	public function main() {
		$url = $this->args[0];
		$defaults = array('t' => 100, 'n' => 10);
		$options = array_merge($defaults, $this->params);
		$times = array();

		$this->out(String::insert(__d('debug_kit', '-> Testing :url'), compact('url')));
		$this->out("");
		for ($i = 0; $i < $options['n']; $i++) {
			if (floor($options['t'] - array_sum($times)) <= 0 || $options['n'] <= 1) {
				break;
			}

			$start = microtime(true);
			file_get_contents($url);
			$stop = microtime(true);

			$times[] = $stop - $start;
		}
		$this->_results($times);
	}

/**
 * Prints calculated results
 *
 * @param array $times Array of time values
 * @return void
 */
	protected function _results($times) {
		$duration = array_sum($times);
		$requests = count($times);

		$this->out(String::insert(__d('debug_kit', 'Total Requests made: :requests'), compact('requests')));
		$this->out(String::insert(__d('debug_kit', 'Total Time elapsed: :duration (seconds)'), compact('duration')));

		$this->out("");

		$this->out(String::insert(__d('debug_kit', 'Requests/Second: :rps req/sec'), array(
				'rps' => round($requests / $duration, 3)
		)));

		$this->out(String::insert(__d('debug_kit', 'Average request time: :average-time seconds'), array(
				'average-time' => round($duration / $requests, 3)
		)));

		$this->out(String::insert(__d('debug_kit', 'Standard deviation of average request time: :std-dev'), array(
				'std-dev' => round($this->_deviation($times, true), 3)
		)));

		$this->out(String::insert(__d('debug_kit', 'Longest/shortest request: :longest sec/:shortest sec'), array(
				'longest' => round(max($times), 3),
				'shortest' => round(min($times), 3)
		)));

		$this->out("");
	}

/**
 * One-pass, numerically stable calculation of population variance.
 *
 * Donald E. Knuth (1998).
 * The Art of Computer Programming, volume 2: Seminumerical Algorithms, 3rd edn.,
 * p. 232. Boston: Addison-Wesley.
 *
 * @param array $times Array of values
 * @param boolean $sample If true, calculates an unbiased estimate of the population
 * 						  variance from a finite sample.
 * @return float Variance
 */
	protected function _variance($times, $sample = true) {
		$n = $mean = $M2 = 0;

		foreach ($times as $time) {
			$n += 1;
			$delta = $time - $mean;
			$mean = $mean + $delta / $n;
			$M2 = $M2 + $delta * ($time - $mean);
		}

		if ($sample) {
			$n -= 1;
		}

		return $M2 / $n;
	}

/**
 * Calculate the standard deviation.
 *
 * @param array $times Array of values
 * @param boolean $sample
 * @return float Standard deviation
 */
	protected function _deviation($times, $sample = true) {
		return sqrt($this->_variance($times, $sample));
	}

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->description(__d('debug_kit',
			'Allows you to obtain some rough benchmarking statistics' .
			'about a fully qualified URL.'
		))
		->addArgument('url', array(
			'help' => __d('debug_kit', 'The URL to request.'),
			'required' => true
		))
		->addOption('n', array(
			'default' => 10,
			'help' => __d('debug_kit', 'Number of iterations to perform.')
		))
		->addOption('t', array(
			'default' => 100,
			'help' => __d('debug_kit', 'Maximum total time for all iterations, in seconds.' .
				'If a single iteration takes more than the timeout, only one request will be made'
			)
		))
		->epilog(__d('debug_kit',
			'Example Use: `cake benchmark --n 10 --t 100 http://localhost/testsite`. ' .
			'<info>Note:</info> this benchmark does not include browser render times.'
		));
		return $parser;
	}
}

