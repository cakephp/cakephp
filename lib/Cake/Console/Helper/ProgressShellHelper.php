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
 * @since         2.8
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses("BaseShellHelper", "Console/Helper");

/**
 * Create a progress bar using a supplied callback.
 */
class ProgressShellHelper extends BaseShellHelper {

/**
 * The current progress.
 *
 * @var int
 */
	protected $_progress = 0;

/**
 * The total number of 'items' to progress through.
 *
 * @var int
 */
	protected $_total = 0;

/**
 * The width of the bar.
 *
 * @var int
 */
	protected $_width = 0;

/**
 * Output a progress bar.
 *
 * Takes a number of options to customize the behavior:
 *
 * - `total` The total number of items in the progress bar. Defaults
 *   to 100.
 * - `width` The width of the progress bar. Defaults to 80.
 * - `callback` The callback that will be called in a loop to advance the progress bar.
 *
 * @param array $args The arguments/options to use when outputing the progress bar.
 * @return void
 * @throws RuntimeException
 */
	public function output($args) {
		$args += array('callback' => null);
		if (isset($args[0])) {
			$args['callback'] = $args[0];
		}
		if (!$args['callback'] || !is_callable($args['callback'])) {
			throw new RuntimeException('Callback option must be a callable.');
		}
		$this->init($args);
		$callback = $args['callback'];
		while ($this->_progress < $this->_total) {
			$callback($this);
			$this->draw();
		}
		$this->_consoleOutput->write('');
	}

/**
 * Initialize the progress bar for use.
 *
 * - `total` The total number of items in the progress bar. Defaults
 *   to 100.
 * - `width` The width of the progress bar. Defaults to 80.
 *
 * @param array $args The initialization data.
 * @return void
 */
	public function init(array $args = array()) {
		$args += array('total' => 100, 'width' => 80);
		$this->_progress = 0;
		$this->_width = $args['width'];
		$this->_total = $args['total'];
	}

/**
 * Increment the progress bar.
 *
 * @param int $num The amount of progress to advance by.
 * @return void
 */
	public function increment($num = 1) {
		$this->_progress = min(max(0, $this->_progress + $num), $this->_total);
	}

/**
 * Render the progress bar based on the current state.
 *
 * @return void
 */
	public function draw() {
		$numberLen = strlen(' 100%');
		$complete = round($this->_progress / $this->_total, 2);
		$barLen = ($this->_width - $numberLen) * ($this->_progress / $this->_total);
		$bar = '';
		if ($barLen > 1) {
			$bar = str_repeat('=', $barLen - 1) . '>';
		}
		$pad = ceil($this->_width - $numberLen - $barLen);
		if ($pad > 0) {
			$bar .= str_repeat(' ', $pad);
		}
		$percent = ($complete * 100) . '%';
		$bar .= str_pad($percent, $numberLen, ' ', STR_PAD_LEFT);
		$this->_consoleOutput->overwrite($bar, 0);
	}
}