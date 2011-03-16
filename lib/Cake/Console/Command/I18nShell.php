<?php
/**
 * Internationalization Management Shell
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.console.shells
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Shell for I18N management.
 *
 * @package       cake.console.shells
 */
class I18nShell extends Shell {

/**
 * Contains database source to use
 *
 * @var string
 * @access public
 */
	public $dataSource = 'default';

/**
 * Contains tasks to load and instantiate
 *
 * @var array
 * @access public
 */
	public $tasks = array('DbConfig', 'Extract');

/**
 * Override startup of the Shell
 *
 */
	public function startup() {
		$this->_welcome();
		if (isset($this->params['datasource'])) {
			$this->dataSource = $this->params['datasource'];
		}

		if ($this->command && !in_array($this->command, array('help'))) {
			if (!config('database')) {
				$this->out(__('Your database configuration was not found. Take a moment to create one.'), true);
				return $this->DbConfig->execute();
			}
		}
	}

/**
 * Override main() for help message hook
 *
 */
	public function main() {
		$this->out(__('<info>I18n Shell</info>'));
		$this->hr();
		$this->out(__('[E]xtract POT file from sources'));
		$this->out(__('[I]nitialize i18n database table'));
		$this->out(__('[H]elp'));
		$this->out(__('[Q]uit'));

		$choice = strtolower($this->in(__('What would you like to do?'), array('E', 'I', 'H', 'Q')));
		switch ($choice) {
			case 'e':
				$this->Extract->execute();
			break;
			case 'i':
				$this->initdb();
			break;
			case 'h':
				$this->out($this->OptionParser->help());
			break;
			case 'q':
				exit(0);
			break;
			default:
				$this->out(__('You have made an invalid selection. Please choose a command to execute by entering E, I, H, or Q.'));
		}
		$this->hr();
		$this->main();
	}

/**
 * Initialize I18N database.
 *
 */
	public function initdb() {
		$this->dispatchShell('schema create i18n');
	}

/**
 * Get and configure the Option parser
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
			__('I18n Shell initializes i18n database table for your application and generates .pot files(s) with translations.')
			)->addSubcommand('initdb', array(
				'help' => __('Initialize the i18n table.')
			))->addSubcommand('extract', array(
				'help' => __('Extract the po translations from your application'),
				'parser' => $this->Extract->getOptionParser()
			));
	}
}
