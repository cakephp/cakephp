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
 * @since         DebugKit 0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ToolbarHelper', 'DebugKit.View/Helper');
App::uses('FireCake', 'DebugKit.Lib');

/**
 * FirePHP Toolbar Helper
 *
 * Injects the toolbar elements into non-HTML layouts via FireCake.
 *
 */
class FirePhpToolbarHelper extends ToolbarHelper {

/**
 * settings property
 *
 * @var array
 */
	public $settings = array('format' => 'firePHP', 'forceEnable' => false);

/**
 * send method
 *
 * @return void
 */
	public function send() {
		$view = $this->_View;
		$view->element('debug_toolbar', array('disableTimer' => true), array('plugin' => 'DebugKit'));
	}

/**
 * makeNeatArray.
 *
 * wraps FireCake::dump() allowing panel elements to continue functioning
 *
 * @param string $values
 * @return void
 */
	public function makeNeatArray($values) {
		FireCake::info($values);
	}

/**
 * Create a simple message
 *
 * @param string $label Label of message
 * @param string $message Message content
 * @return void
 */
	public function message($label, $message) {
		FireCake::log($message, $label);
	}

/**
 * Generate a table with FireCake
 *
 * @param array $rows Rows to print
 * @param array $headers Headers for table
 * @param array $options Additional options and params
 * @return void
 */
	public function table($rows, $headers, $options = array()) {
		$title = $headers[0];
		if (isset($options['title'])) {
			$title = $options['title'];
		}
		foreach ($rows as $i => $row) {
			$rows[$i] = array_values($row);
		}
		array_unshift($rows, $headers);
		FireCake::table($title, $rows);
	}

/**
 * Start a panel which is a 'Group' in FirePHP
 *
 * @param $title
 * @param $anchor
 * @return void
 */
	public function panelStart($title, $anchor) {
		FireCake::group($title);
	}

/**
 * End a panel (Group)
 *
 * @return void
 */
	public function panelEnd() {
		FireCake::groupEnd();
	}

}
