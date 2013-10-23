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
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Console.Command
 * @since         CakePHP v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
			$this->out(" -app: " . APP_DIR);
			$this->out(" -working: " . rtrim(APP, DS));
			$this->out(" -root: " . rtrim(ROOT, DS));
			$this->out(" -core: " . rtrim(CORE_PATH, DS));
			$this->out("");
			$this->out(__d('cake_console', "<info>Changing Paths:</info>"), 2);
			$this->out(__d('cake_console', "Your working path should be the same as your application path. To change your path use the '-app' param."));
			$this->out(__d('cake_console', "Example: %s or %s", '-app relative/path/to/myapp', '-app /absolute/path/to/myapp'), 2);

			$this->out(__d('cake_console', "<info>Available Shells:</info>"), 2);
		}

		$shellList = $this->_getShellList();
		if (empty($shellList)) {
			return;
		}

		if (empty($this->params['xml'])) {
			$this->_asText($shellList);
		} else {
			$this->_asXml($shellList);
		}
	}

/**
 * Gets the shell command listing.
 *
 * @return array
 */
	protected function _getShellList() {
		$skipFiles = array('AppShell');

		$plugins = CakePlugin::loaded();
		$shellList = array_fill_keys($plugins, null) + array('CORE' => null, 'app' => null);

		$corePath = App::core('Console/Command');
		$shells = App::objects('file', $corePath[0]);
		$shells = array_diff($shells, $skipFiles);
		$this->_appendShells('CORE', $shells, $shellList);

		$appShells = App::objects('Console/Command', null, false);
		$appShells = array_diff($appShells, $shells, $skipFiles);
		$this->_appendShells('app', $appShells, $shellList);

		foreach ($plugins as $plugin) {
			$pluginShells = App::objects($plugin . '.Console/Command');
			$this->_appendShells($plugin, $pluginShells, $shellList);
		}

		return array_filter($shellList);
	}

/**
 * Scan the provided paths for shells, and append them into $shellList
 *
 * @param string $type
 * @param array $shells
 * @param array $shellList
 * @return void
 */
	protected function _appendShells($type, $shells, &$shellList) {
		foreach ($shells as $shell) {
			$shellList[$type][] = Inflector::underscore(str_replace('Shell', '', $shell));
		}
	}

/**
 * Output text.
 *
 * @param array $shellList
 * @return void
 */
	protected function _asText($shellList) {
		foreach ($shellList as $plugin => $commands) {
			sort($commands);
			$this->out(sprintf('[<info>%s</info>] %s', $plugin, implode(', ', $commands)));
			$this->out();
		}

		$this->out(__d('cake_console', "To run an app or core command, type <info>cake shell_name [args]</info>"));
		$this->out(__d('cake_console', "To run a plugin command, type <info>cake Plugin.shell_name [args]</info>"));
		$this->out(__d('cake_console', "To get help on a specific command, type <info>cake shell_name --help</info>"), 2);
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
		foreach ($shellList as $plugin => $commands) {
			foreach ($commands as $command) {
				$callable = $command;
				if (in_array($plugin, $plugins)) {
					$callable = Inflector::camelize($plugin) . '.' . $command;
				}

				$shell = $shells->addChild('shell');
				$shell->addAttribute('name', $command);
				$shell->addAttribute('call_as', $callable);
				$shell->addAttribute('provider', $plugin);
				$shell->addAttribute('help', $callable . ' -h');
			}
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
			->addOption('sort', array(
				'help' => __d('cake_console', 'Does nothing (deprecated)'),
				'boolean' => true
			))
			->addOption('xml', array(
				'help' => __d('cake_console', 'Get the listing as XML.'),
				'boolean' => true
			));
	}

}
