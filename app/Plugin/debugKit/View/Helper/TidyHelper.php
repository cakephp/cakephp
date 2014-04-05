<?php
/**
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         v 1.0 (22-Jun-2009)
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('File', 'Utility');

/**
 * TidyHelper class
 *
 * Passes html through tidy on the command line, and reports markup errors
 *
 * @uses          AppHelper
 * @since         v 1.0 (22-Jun-2009)
 */
class TidyHelper extends AppHelper {

/**
 * helpers property
 *
 * @var array
 */
	public $helpers = array('DebugKit.Toolbar');

/**
 * results property
 *
 * @var mixed null
 */
	public $results = null;

/**
 * Return a nested array of errors for the passed html string
 * Fudge the markup slightly so that the tag which is invalid is highlighted
 *
 * @param string $html ''
 * @param string $out ''
 * @return array
 */
	public function process($html = '', &$out = '') {
		$errors = $this->tidyErrors($html, $out);

		if (!$errors) {
			return array();
		}
		$result = array('Error' => array(), 'Warning' => array(), 'Misc' => array());
		$errors = explode("\n", $errors);
		$markup = explode("\n", $out);
		foreach ($errors as $error) {
			preg_match('@line (\d+) column (\d+) - (\w+): (.*)@', $error, $matches);
			if ($matches) {
				list($original, $line, $column, $type, $message) = $matches;
				$line = $line - 1;

				$string = '</strong>';
				if (isset($markup[$line - 1])) {
					$string .= h($markup[$line - 1]);
				}
				$string .= '<strong>' . h(@$markup[$line]) . '</strong>';
				if (isset($markup[$line + 1])) {
					$string .= h($markup[$line + 1]);
				}
				$string .= '</strong>';

				$result[$type][$string][] = h($message);
			} elseif ($error) {
				$message = $error;
				$result['Misc'][h($message)][] = h($message);
			}
		}
		$this->results = $result;
		return $result;
	}

/**
 * report method
 *
 * Call process if a string is passed, or no prior results exist - and return the results using
 * the toolbar helper to generate a nested navigatable array
 *
 * @param mixed $html null
 * @return string
 */
	public function report($html = null) {
		if ($html) {
			$this->process($html);
		} elseif ($this->results === null) {
			$this->process($this->_View->output);
		}
		if (!$this->results) {
			return '<p>' . __d('debug_kit', 'No markup errors found') . '</p>';
		}
		foreach ($this->results as &$results) {
			foreach ($results as $type => &$messages) {
				foreach ($messages as &$message) {
					$message = html_entity_decode($message, ENT_COMPAT, Configure::read('App.encoding'));
				}
			}
		}
		return $this->Toolbar->makeNeatArray(array_filter($this->results), 0, 0, false);
	}

/**
 * Run the html string through tidy, and return the (raw) errors. pass back a reference to the
 * normalized string so that the error messages can be linked to the line that caused them.
 *
 * @param string $in ''
 * @param string $out ''
 * @return string
 */
	public function tidyErrors($in = '', &$out = '') {
		$out = preg_replace('@>\s*<@s', ">\n<", $in);

		// direct access? windows etc
		if (function_exists('tidy_parse_string')) {
			$tidy = tidy_parse_string($out, array(), 'UTF8');
			$tidy->cleanRepair();
			$errors = $tidy->errorBuffer . "\n";
			return $errors;
		}

		// cli
		$File = new File(rtrim(TMP, DS) . DS . rand() . '.html', true);
		$File->write($out);
		$path = $File->pwd();
		$errors = $path . '.err';
		$this->_exec("tidy -eq -utf8 -f $errors $path");
		$File->delete();

		if (!file_exists($errors)) {
			return '';
		}
		$Error = new File($errors);
		$errors = $Error->read();
		$Error->delete();
		return $errors;
	}

/**
 * exec method
 *
 * @param mixed $cmd
 * @param mixed $out null
 * @return boolean True if successful
 */
	protected function _exec($cmd, &$out = null) {
		if (DS === '/') {
			$_out = exec($cmd . ' 2>&1', $out, $return);
		} else {
			$_out = exec($cmd, $out, $return);
		}

		if (Configure::read('debug')) {
			$source = Debugger::trace(array('depth' => 1, 'start' => 2)) . "\n";
			//CakeLog::write('system_calls_' . date('Y-m-d'), "\n" . $source . Debugger::exportVar(compact('cmd','out','return')));
			//CakeLog::write('system_calls', "\n" . $source . Debugger::exportVar(compact('cmd','out','return')));
		}
		if ($return) {
			return false;
		}
		return $_out ? $_out : true;
	}
}
