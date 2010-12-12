<?php
/**
 * A shell class to help developers upgrade applications to CakePHP 2.0
 *
 * @package cake.console/shells
 */
class UpgradeShell extends Shell {

	protected $_files = array();
	protected $_paths = array();

/**
 * Update helpers.
 *
 * - Converts helpers usage to new format.
 *
 * @return void
 */
	function helpers() {
		$this->_paths = array(
			VIEWS
		);
		if (!empty($this->params['plugin'])) {
			$this->_paths = array(App::pluginPath($this->params['plugin']) . 'views' . DS);
		}

		$patterns = array();
		foreach (App::objects('helper') as $helper) {
			$oldHelper = strtolower(substr($helper, 0, 1)).substr($helper, 1);
			$patterns[] = array(
				"\${$oldHelper} to \$this->{$helper}",
				"/\\\${$oldHelper}->/",
				"\\\$this->{$helper}->"
			);
		}
		
		$this->_filesRegexpUpdate($patterns);
	}

/**
 * Update i18n.
 *
 * - Removes extra true param.
 * - Add the echo to __*() calls that didn't need them before.
 *
 * @return void
 */
	function i18n() {
		$this->_paths = array(
			APP
		);
		if (!empty($this->params['plugin'])) {
			$this->_paths = array(App::pluginPath($this->params['plugin']));
		}

		$patterns = array(
			array(
				'<?php __*(*) to <?php echo __*(*)',
				'/<\?php\s*(__[a-z]*\(.*?\))/',
				'<?php echo \1'
			),
			array(
				'<?php __*(*, true) to <?php echo __*()',
				'/<\?php\s*(__[a-z]*\(.*?)(,\s*true)(\))/',
				'<?php echo \1\3'
			),
			array('__*(*, true) to __*(*)', '/(__[a-z]*\(.*?)(,\s*true)(\))/', '\1\3')
		);

		$this->_filesRegexpUpdate($patterns);
	}

/**
 * Updates files based on regular expressions.
 *
 * @param array $patterns Array of search and replacement patterns.
 * @return void
 */
	protected function _filesRegexpUpdate($patterns) {
		$this->_findFiles($this->params['ext']);
		foreach ($this->_files as $file) {
			$this->out('Updating ' . $file . '...', 1, Shell::VERBOSE);
			$this->_updateFile($file, $patterns);
		}
	}

/**
 * Searches the paths and finds files based on extension.
 *
 * @param string $extensions 
 * @return void
 */
	protected function _findFiles($extensions = '') {
		foreach ($this->_paths as $path) {
			$files = array();
			$Iterator = new RegexIterator(
				new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)),
				'/^.+\.(' . $extensions . ')$/i',
				RegexIterator::MATCH
			);
			foreach ($Iterator as $file) {
				if ($file->isFile()) {
					$files[] = $file->getPathname();
				}
			}
			$this->_files = array_merge($this->_files, $files);
		}
	}

/**
 * Update a single file.
 *
 * @param string $file The file to update
 * @param array $patterns The replacement patterns to run.
 * @return void
 */
	protected function _updateFile($file, $patterns) {
		$contents = file_get_contents($file);
		
		foreach ($patterns as $pattern) {
			$this->out(' * Updating ' . $pattern[0], 1, Shell::VERBOSE);
			$contents = preg_replace($pattern[1], $pattern[2], $contents);
		}

		$this->out('Done updating ' . $file, 1);
		file_put_contents($file, $contents);
	}

/**
 * get the option parser
 *
 * @return ConsoleOptionParser
 */
	function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'plugin' => array(
					'short' => 'p',
					'help' => __('The plugin to update. Only the specified plugin will be updated.'
				)),
				'ext' => array(
					'short' => 'e',
					'help' => __('The extension(s) to search. A pipe delimited list, or a preg_match compatible subpattern'),
					'default' => 'php|ctp|thtml|inc|tpl'
				),
			)
		);

		return parent::getOptionParser()
			->description("A shell to help automate upgrading from CakePHP 1.3 to 2.0. \nBe sure to have a backup of your application before running these commands.")
			->addSubcommand('i18n', array(
				'help' => 'Update the i18n translation method calls.',
				'parser' => $subcommandParser
			))
			->addSubcommand('helpers', array(
				'help' => 'Update calls to helpers.',
				'parser' => $subcommandParser
			));
	}
}