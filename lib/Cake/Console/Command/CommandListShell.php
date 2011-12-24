<?php
/**
 * Command list Shell
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Console.Command
 * @since         CakePHP v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppShell', 'Console/Command');
App::uses('Inflector', 'Utility');

/**
 * Shows a list of commands available from the console.
 *
 * @package       Cake.Console.Command
 */
class CommandListShell extends AppShell {

/**
 * startup
 *
 * @return void
 */
	public function startup() {
		if (empty($this->params['xml'])) {
			parent::startup();
		}
	}

/**
 * Main function Prints out the list of shells.
 *
 * @return void
 */
	public function main() {
		if (empty($this->params['xml'])) {
			$this->out(__d('cake_console', "<info>Current Paths:</info>"), 2);
			$this->out(" -app: ". APP_DIR);
			$this->out(" -working: " . rtrim(APP, DS));
			$this->out(" -root: " . rtrim(ROOT, DS));
			$this->out(" -core: " . rtrim(CORE_PATH, DS));
			$this->out("");
			$this->out(__d('cake_console', "<info>Changing Paths:</info>"), 2);
			$this->out(__d('cake_console', "Your working path should be the same as your application path to change your path use the '-app' param."));
			$this->out(__d('cake_console', "Example: -app relative/path/to/myapp or -app /absolute/path/to/myapp"), 2);

			$this->out(__d('cake_console', "<info>Available Shells:</info>"), 2);
		}

		$shellList = $this->_getShellList();

		if ($shellList) {
			ksort($shellList);
			if (empty($this->params['xml'])) {
				if (!empty($this->params['sort'])) {
					$this->_asSorted($shellList);
				} else {
					$this->_asText($shellList);
				}
			} else {
				$this->_asXml($shellList);
			}
		}
	}

/**
 * Gets the shell command listing.
 *
 * @return array
 */
	protected function _getShellList() {
		$shellList = array();
		$skipFiles = array('AppShell');

		$corePath = App::core('Console/Command');
		$shells = App::objects('file', $corePath[0]);
		$shells = array_diff($shells, $skipFiles);
		$shellList = $this->_appendShells('CORE', $shells, $shellList);

		$appShells = App::objects('Console/Command', null, false);
		$appShells = array_diff($appShells, $shells, $skipFiles);
		$shellList = $this->_appendShells('app', $appShells, $shellList);

		$plugins = CakePlugin::loaded();
		foreach ($plugins as $plugin) {
			$pluginShells = App::objects($plugin . '.Console/Command');
			$shellList = $this->_appendShells($plugin, $pluginShells, $shellList);
		}

		return $shellList;
	}

/**
 * Scan the provided paths for shells, and append them into $shellList
 *
 * @param string $type
 * @param array $shells
 * @param array $shellList
 * @return array
 */
	protected function _appendShells($type, $shells, $shellList) {
		foreach ($shells as $shell) {
			$shell = Inflector::underscore(str_replace('Shell', '', $shell));
			$shellList[$shell][$type] = $type;
		}
		return $shellList;
	}

/**
 * Output text.
 *
 * @param array $shellList
 * @return void
 */
	protected function _asText($shellList) {
		if (DS === '/') {
			$width = exec('tput cols') - 2;
		}
		if (empty($width)) {
			$width = 80;
		}
		$columns = max(1, floor($width / 30));
		$rows = ceil(count($shellList) / $columns);

		foreach ($shellList as $shell => $types) {
			sort($types);
			$shellList[$shell] = str_pad($shell . ' [' . implode ($types, ', ') . ']', $width / $columns);
		}
		$out = array_chunk($shellList, $rows);
		for ($i = 0; $i < $rows; $i++) {
			$row = '';
			for ($j = 0; $j < $columns; $j++) {
				if (!isset($out[$j][$i])) {
					continue;
				}
				$row .= $out[$j][$i];
			}
			$this->out(" " . $row);
		}
		$this->out();
		$this->out(__d('cake_console', "To run an app or core command, type <info>cake shell_name [args]</info>"));
		$this->out(__d('cake_console', "To run a plugin command, type <info>cake Plugin.shell_name [args]</info>"));
		$this->out(__d('cake_console', "To get help on a specific command, type <info>cake shell_name --help</info>"), 2);
	}

/**
 * Generates the shell list sorted by where the shells are found.
 *
 * @param array $shellList
 * @return void
 */
	protected function _asSorted($shellList) {
		$grouped = array();
		foreach ($shellList as $shell => $types) {
			foreach ($types as $type) {
				$type = Inflector::camelize($type);
				if (empty($grouped[$type])) {
					$grouped[$type] = array();
				}
				$grouped[$type][] = $shell;
			}
		}
		if (!empty($grouped['App'])) {
			sort($grouped['App'], SORT_STRING);
			$this->out('[ App ]');
			$this->out('  ' . implode(', ', $grouped['App']), 2);
			unset($grouped['App']);
		}
		foreach ($grouped as $section => $shells) {
			if ($section == 'CORE') {
				continue;
			}
			sort($shells, SORT_STRING);
			$this->out('[ ' . $section . ' ]');
			$this->out('  ' . implode(', ', $shells), 2);
		}
		if (!empty($grouped['CORE'])) {
			sort($grouped['CORE'], SORT_STRING);
			$this->out('[ Core ]');
			$this->out('  ' . implode(', ', $grouped['CORE']), 2);
		}
		$this->out();
	}

/**
 * Output as XML
 *
 * @param array $shellList
 * @return void
 */
	protected function _asXml($shellList) {
		$plugins = CakePlugin::loaded();
		$shells = new SimpleXmlElement('<shells></shells>');
		foreach ($shellList as $name => $location) {
			$source = current($location);
			$callable = $name;
			if (in_array($source, $plugins)) {
				$callable = Inflector::camelize($source) . '.' . $name;
			}
			$shell = $shells->addChild('shell');
			$shell->addAttribute('name', $name);
			$shell->addAttribute('call_as', $callable);
			$shell->addAttribute('provider', $source);
			$shell->addAttribute('help', $callable . ' -h');
		}
		$this->stdout->outputAs(ConsoleOutput::RAW);
		$this->out($shells->saveXml());
	}

/**
 * get the option parser
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(__d('cake_console', 'Get the list of available shells for this CakePHP application.'))
			->addOption('xml', array(
				'help' => __d('cake_console', 'Get the listing as XML.'),
				'boolean' => true
			))->addOption('sort', array(
				'help' => __d('cake_console', 'Sorts the commands by where they are located.'),
				'boolean' => true
			));
	}
}
