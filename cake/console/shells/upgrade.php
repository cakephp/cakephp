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

		$patterns = array();
		foreach(App::objects('helper') as $helper) {
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
 *
 * @return void
 */
	function i18n() {
		$this->_paths = array(
			CONTROLLERS,
			MODELS,
			VIEWS
		);

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

	protected function _filesRegexpUpdate($patterns) {
		if (!empty($this->params['plugin'])) {
			$this->_paths = array(App::pluginPath($this->params['plugin']));
		}

		$this->_findFiles();
		foreach ($this->_files as $file) {
			$this->out('Updating ' . $file . '...', 1, Shell::VERBOSE);
			$this->_updateFile($file, $patterns);
		}
	}

	protected function _findFiles($pattern = '') {
		foreach ($this->_paths as $path) {
			$Folder = new Folder($path);
			$files = $Folder->findRecursive('.*\.(php|ctp|thtml|inc|tpl)', true);
			if (!empty($pattern)) {
				foreach ($files as $i => $file) {
					if (preg_match($pattern, $file)) {
						unset($files[$i]);
					}
				}
				$files = array_values($files);
			}
			$this->_files = array_merge($this->_files, $files);
		}
	}

/**
 * Update a single file.
 *
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
 * @return void
 */
	function getOptionParser() {
		return parent::getOptionParser()
			->addSubcommand('i18n', array(
				'help' => 'Update the i18n translation method calls.',
				'parser' => array(
					'options' => array(
						'plugin' => array('short' => 'p', 'help' => __('The plugin to update.'))
					)
				)
			))
			->addSubcommand('helpers', array(
				'help' => 'Update calls to helpers.',
				'parser' => array(
					'options' => array(
						'plugin' => array('short' => 'p', 'help' => __('The plugin to update.'))
					)
				)
			));
	}
}