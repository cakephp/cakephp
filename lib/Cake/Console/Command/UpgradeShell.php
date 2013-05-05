<?php
/**
 * Upgrade Shell
 *
 * PHP 5
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
 * @package       Cake.Console.Command
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppShell', 'Console/Command');
App::uses('Folder', 'Utility');

/**
 * A shell class to help developers upgrade applications to CakePHP 2.0
 *
 * @package       Cake.Console.Command
 */
class UpgradeShell extends AppShell {

/**
 * Files
 *
 * @var array
 */
	protected $_files = array();

/**
 * Paths
 *
 * @var array
 */
	protected $_paths = array();

/**
 * Map
 *
 * @var array
 */
	protected $_map = array(
		'Controller' => 'Controller',
		'Component' => 'Controller/Component',
		'Model' => 'Model',
		'Behavior' => 'Model/Behavior',
		'Datasource' => 'Model/Datasource',
		'Dbo' => 'Model/Datasource/Database',
		'View' => 'View',
		'Helper' => 'View/Helper',
		'Shell' => 'Console/Command',
		'Task' => 'Console/Command/Task',
		'Case' => 'Test/Case',
		'Fixture' => 'Test/Fixture',
		'Error' => 'Lib/Error',
	);

/**
 * Shell startup, prints info message about dry run.
 *
 * @return void
 */
	public function startup() {
		parent::startup();
		if ($this->params['dry-run']) {
			$this->out(__d('cake_console', '<warning>Dry-run mode enabled!</warning>'), 1, Shell::QUIET);
		}
		if ($this->params['git'] && !is_dir('.git')) {
			$this->out(__d('cake_console', '<warning>No git repository detected!</warning>'), 1, Shell::QUIET);
		}
	}

/**
 * Run all upgrade steps one at a time
 *
 * @return void
 */
	public function all() {
		foreach ($this->OptionParser->subcommands() as $command) {
			$name = $command->name();
			if ($name === 'all') {
				continue;
			}
			$this->out(__d('cake_console', 'Running %s', $name));
			$this->$name();
		}
	}

/**
 * Update tests.
 *
 * - Update tests class names to FooTest rather than FooTestCase.
 *
 * @return void
 */
	public function tests() {
		$this->_paths = array(APP . 'tests' . DS);
		if (!empty($this->params['plugin'])) {
			$this->_paths = array(App::pluginPath($this->params['plugin']) . 'tests' . DS);
		}
		$patterns = array(
			array(
				'*TestCase extends CakeTestCase to *Test extends CakeTestCase',
				'/([a-zA-Z]*Test)Case extends CakeTestCase/',
				'\1 extends CakeTestCase'
			),
		);

		$this->_filesRegexpUpdate($patterns);
	}

/**
 * Move files and folders to their new homes
 *
 * Moves folders containing files which cannot necessarily be auto-detected (libs and templates)
 * and then looks for all php files except vendors, and moves them to where Cake 2.0 expects
 * to find them.
 *
 * @return void
 */
	public function locations() {
		$cwd = getcwd();

		if (!empty($this->params['plugin'])) {
			chdir(App::pluginPath($this->params['plugin']));
		}

		if (is_dir('plugins')) {
			$Folder = new Folder('plugins');
			list($plugins) = $Folder->read();
			foreach ($plugins as $plugin) {
				chdir($cwd . DS . 'plugins' . DS . $plugin);
				$this->out(__d('cake_console', 'Upgrading locations for plugin %s', $plugin));
				$this->locations();
			}
			$this->_files = array();
			chdir($cwd);
			$this->out(__d('cake_console', 'Upgrading locations for app directory'));
		}
		$moves = array(
			'config' => 'Config',
			'Config' . DS . 'schema' => 'Config' . DS . 'Schema',
			'libs' => 'Lib',
			'tests' => 'Test',
			'views' => 'View',
			'models' => 'Model',
			'Model' . DS . 'behaviors' => 'Model' . DS . 'Behavior',
			'Model' . DS . 'datasources' => 'Model' . DS . 'Datasource',
			'Test' . DS . 'cases' => 'Test' . DS . 'Case',
			'Test' . DS . 'fixtures' => 'Test' . DS . 'Fixture',
			'vendors' . DS . 'shells' . DS . 'templates' => 'Console' . DS . 'Templates',
		);
		foreach ($moves as $old => $new) {
			if (is_dir($old)) {
				$this->out(__d('cake_console', 'Moving %s to %s', $old, $new));
				if (!$this->params['dry-run']) {
					if ($this->params['git']) {
						exec('git mv -f ' . escapeshellarg($old) . ' ' . escapeshellarg($old . '__'));
						exec('git mv -f ' . escapeshellarg($old . '__') . ' ' . escapeshellarg($new));
					} else {
						$Folder = new Folder($old);
						$Folder->move($new);
					}
				}
			}
		}

		$this->_moveViewFiles();
		$this->_moveAppClasses();

		$sourceDirs = array(
			'.' => array('recursive' => false),
			'Console',
			'controllers',
			'Controller',
			'Lib' => array('checkFolder' => false),
			'models',
			'Model',
			'tests',
			'Test' => array('regex' => '@class (\S*Test) extends CakeTestCase@'),
			'views',
			'View',
			'vendors/shells',
		);

		$defaultOptions = array(
			'recursive' => true,
			'checkFolder' => true,
			'regex' => '@class (\S*) .*(\s|\v)*{@i'
		);
		foreach ($sourceDirs as $dir => $options) {
			if (is_numeric($dir)) {
				$dir = $options;
				$options = array();
			}
			$options = array_merge($defaultOptions, $options);
			$this->_movePhpFiles($dir, $options);
		}
	}

/**
 * Update helpers.
 *
 * - Converts helpers usage to new format.
 *
 * @return void
 */
	public function helpers() {
		$this->_paths = array_diff(App::path('views'), App::core('views'));

		if (!empty($this->params['plugin'])) {
			$this->_paths = array(App::pluginPath($this->params['plugin']) . 'views' . DS);
		}

		$patterns = array();
		App::build(array(
			'View/Helper' => App::core('View/Helper'),
		), App::APPEND);
		$helpers = App::objects('helper');
		$plugins = App::objects('plugin');
		$pluginHelpers = array();
		foreach ($plugins as $plugin) {
			CakePlugin::load($plugin);
			$pluginHelpers = array_merge(
				$pluginHelpers,
				App::objects('helper', App::pluginPath($plugin) . DS . 'views' . DS . 'helpers' . DS, false)
			);
		}
		$helpers = array_merge($pluginHelpers, $helpers);
		foreach ($helpers as $helper) {
			$helper = preg_replace('/Helper$/', '', $helper);
			$oldHelper = $helper;
			$oldHelper{0} = strtolower($oldHelper{0});
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
	public function i18n() {
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
 * Upgrade the removed basics functions.
 *
 * - a(*) -> array(*)
 * - e(*) -> echo *
 * - ife(*, *, *) -> !empty(*) ? * : *
 * - a(*) -> array(*)
 * - r(*, *, *) -> str_replace(*, *, *)
 * - up(*) -> strtoupper(*)
 * - low(*, *, *) -> strtolower(*)
 * - getMicrotime() -> microtime(true)
 *
 * @return void
 */
	public function basics() {
		$this->_paths = array(
			APP
		);
		if (!empty($this->params['plugin'])) {
			$this->_paths = array(App::pluginPath($this->params['plugin']));
		}
		$patterns = array(
			array(
				'a(*) -> array(*)',
				'/\ba\((.*)\)/',
				'array(\1)'
			),
			array(
				'e(*) -> echo *',
				'/\be\((.*)\)/',
				'echo \1'
			),
			array(
				'ife(*, *, *) -> !empty(*) ? * : *',
				'/ife\((.*), (.*), (.*)\)/',
				'!empty(\1) ? \2 : \3'
			),
			array(
				'r(*, *, *) -> str_replace(*, *, *)',
				'/\br\(/',
				'str_replace('
			),
			array(
				'up(*) -> strtoupper(*)',
				'/\bup\(/',
				'strtoupper('
			),
			array(
				'low(*) -> strtolower(*)',
				'/\blow\(/',
				'strtolower('
			),
			array(
				'getMicrotime() -> microtime(true)',
				'/getMicrotime\(\)/',
				'microtime(true)'
			),
		);
		$this->_filesRegexpUpdate($patterns);
	}

/**
 * Update the properties moved to CakeRequest.
 *
 * @return void
 */
	public function request() {
		$views = array_diff(App::path('views'), App::core('views'));
		$controllers = array_diff(App::path('controllers'), App::core('controllers'), array(APP));
		$components = array_diff(App::path('components'), App::core('components'));

		$this->_paths = array_merge($views, $controllers, $components);

		if (!empty($this->params['plugin'])) {
			$pluginPath = App::pluginPath($this->params['plugin']);
			$this->_paths = array(
				$pluginPath . 'controllers' . DS,
				$pluginPath . 'controllers' . DS . 'components' . DS,
				$pluginPath . 'views' . DS,
			);
		}
		$patterns = array(
			array(
				'$this->data -> $this->request->data',
				'/(\$this->data\b(?!\())/',
				'$this->request->data'
			),
			array(
				'$this->params -> $this->request->params',
				'/(\$this->params\b(?!\())/',
				'$this->request->params'
			),
			array(
				'$this->webroot -> $this->request->webroot',
				'/(\$this->webroot\b(?!\())/',
				'$this->request->webroot'
			),
			array(
				'$this->base -> $this->request->base',
				'/(\$this->base\b(?!\())/',
				'$this->request->base'
			),
			array(
				'$this->here -> $this->request->here',
				'/(\$this->here\b(?!\())/',
				'$this->request->here'
			),
			array(
				'$this->action -> $this->request->action',
				'/(\$this->action\b(?!\())/',
				'$this->request->action'
			),
		);
		$this->_filesRegexpUpdate($patterns);
	}

/**
 * Update Configure::read() calls with no params.
 *
 * @return void
 */
	public function configure() {
		$this->_paths = array(
			APP
		);
		if (!empty($this->params['plugin'])) {
			$this->_paths = array(App::pluginPath($this->params['plugin']));
		}
		$patterns = array(
			array(
				"Configure::read() -> Configure::read('debug')",
				'/Configure::read\(\)/',
				'Configure::read(\'debug\')'
			),
		);
		$this->_filesRegexpUpdate($patterns);
	}

/**
 * constants
 *
 * @return void
 */
	public function constants() {
		$this->_paths = array(
			APP
		);
		if (!empty($this->params['plugin'])) {
			$this->_paths = array(App::pluginPath($this->params['plugin']));
		}
		$patterns = array(
			array(
				"LIBS -> CAKE",
				'/\bLIBS\b/',
				'CAKE'
			),
			array(
				"CONFIGS -> APP . 'Config' . DS",
				'/\bCONFIGS\b/',
				'APP . \'Config\' . DS'
			),
			array(
				"CONTROLLERS -> APP . 'Controller' . DS",
				'/\bCONTROLLERS\b/',
				'APP . \'Controller\' . DS'
			),
			array(
				"COMPONENTS -> APP . 'Controller' . DS . 'Component' . DS",
				'/\bCOMPONENTS\b/',
				'APP . \'Controller\' . DS . \'Component\''
			),
			array(
				"MODELS -> APP . 'Model' . DS",
				'/\bMODELS\b/',
				'APP . \'Model\' . DS'
			),
			array(
				"BEHAVIORS -> APP . 'Model' . DS . 'Behavior' . DS",
				'/\bBEHAVIORS\b/',
				'APP . \'Model\' . DS . \'Behavior\' . DS'
			),
			array(
				"VIEWS -> APP . 'View' . DS",
				'/\bVIEWS\b/',
				'APP . \'View\' . DS'
			),
			array(
				"HELPERS -> APP . 'View' . DS . 'Helper' . DS",
				'/\bHELPERS\b/',
				'APP . \'View\' . DS . \'Helper\' . DS'
			),
			array(
				"LAYOUTS -> APP . 'View' . DS . 'Layouts' . DS",
				'/\bLAYOUTS\b/',
				'APP . \'View\' . DS . \'Layouts\' . DS'
			),
			array(
				"ELEMENTS -> APP . 'View' . DS . 'Elements' . DS",
				'/\bELEMENTS\b/',
				'APP . \'View\' . DS . \'Elements\' . DS'
			),
			array(
				"CONSOLE_LIBS -> CAKE . 'Console' . DS",
				'/\bCONSOLE_LIBS\b/',
				'CAKE . \'Console\' . DS'
			),
			array(
				"CAKE_TESTS_LIB -> CAKE . 'TestSuite' . DS",
				'/\bCAKE_TESTS_LIB\b/',
				'CAKE . \'TestSuite\' . DS'
			),
			array(
				"CAKE_TESTS -> CAKE . 'Test' . DS",
				'/\bCAKE_TESTS\b/',
				'CAKE . \'Test\' . DS'
			)
		);
		$this->_filesRegexpUpdate($patterns);
	}

/**
 * Update components.
 *
 * - Make components that extend Object to extend Component.
 *
 * @return void
 */
	public function components() {
		$this->_paths = App::Path('Controller/Component');
		if (!empty($this->params['plugin'])) {
			$this->_paths = App::Path('Controller/Component', $this->params['plugin']);
		}
		$patterns = array(
			array(
				'*Component extends Object to *Component extends Component',
				'/([a-zA-Z]*Component extends) Object/',
				'\1 Component'
			),
		);

		$this->_filesRegexpUpdate($patterns);
	}

/**
 * Replace cakeError with built-in exceptions.
 * NOTE: this ignores calls where you've passed your own secondary parameters to cakeError().
 * @return void
 */
	public function exceptions() {
		$controllers = array_diff(App::path('controllers'), App::core('controllers'), array(APP));
		$components = array_diff(App::path('components'), App::core('components'));

		$this->_paths = array_merge($controllers, $components);

		if (!empty($this->params['plugin'])) {
			$pluginPath = App::pluginPath($this->params['plugin']);
			$this->_paths = array(
				$pluginPath . 'controllers' . DS,
				$pluginPath . 'controllers' . DS . 'components' . DS,
			);
		}
		$patterns = array(
			array(
				'$this->cakeError("error400") -> throw new BadRequestException()',
				'/(\$this->cakeError\(["\']error400["\']\));/',
				'throw new BadRequestException();'
			),
			array(
				'$this->cakeError("error404") -> throw new NotFoundException()',
				'/(\$this->cakeError\(["\']error404["\']\));/',
				'throw new NotFoundException();'
			),
			array(
				'$this->cakeError("error500") -> throw new InternalErrorException()',
				'/(\$this->cakeError\(["\']error500["\']\));/',
				'throw new InternalErrorException();'
			),
		);
		$this->_filesRegexpUpdate($patterns);
	}

/**
 * Move application views files to where they now should be
 *
 * Find all view files in the folder and determine where cake expects the file to be
 *
 * @return void
 */
	protected function _moveViewFiles() {
		if (!is_dir('View')) {
			return;
		}

		$dirs = scandir('View');
		foreach ($dirs as $old) {
			if (!is_dir('View' . DS . $old) || $old === '.' || $old === '..') {
				continue;
			}

			$new = 'View' . DS . Inflector::camelize($old);
			$old = 'View' . DS . $old;
			if ($new == $old) {
				continue;
			}

			$this->out(__d('cake_console', 'Moving %s to %s', $old, $new));
			if (!$this->params['dry-run']) {
				if ($this->params['git']) {
					exec('git mv -f ' . escapeshellarg($old) . ' ' . escapeshellarg($old . '__'));
					exec('git mv -f ' . escapeshellarg($old . '__') . ' ' . escapeshellarg($new));
				} else {
					$Folder = new Folder($old);
					$Folder->move($new);
				}
			}
		}
	}

/**
 * Move the AppController, and AppModel classes.
 *
 * @return void
 */
	protected function _moveAppClasses() {
		$files = array(
			APP . 'app_controller.php' => APP . 'Controller' . DS . 'AppController.php',
			APP . 'controllers' . DS . 'app_controller.php' => APP . 'Controller' . DS . 'AppController.php',
			APP . 'app_model.php' => APP . 'Model' . DS . 'AppModel.php',
			APP . 'models' . DS . 'app_model.php' => APP . 'Model' . DS . 'AppModel.php',
		);
		foreach ($files as $old => $new) {
			if (file_exists($old)) {
				$this->out(__d('cake_console', 'Moving %s to %s', $old, $new));

				if ($this->params['dry-run']) {
					continue;
				}
				if ($this->params['git']) {
					exec('git mv -f ' . escapeshellarg($old) . ' ' . escapeshellarg($old . '__'));
					exec('git mv -f ' . escapeshellarg($old . '__') . ' ' . escapeshellarg($new));
				} else {
					rename($old, $new);
				}
			}
		}
	}

/**
 * Move application php files to where they now should be
 *
 * Find all php files in the folder (honoring recursive) and determine where CakePHP expects the file to be
 * If the file is not exactly where CakePHP expects it - move it.
 *
 * @param string $path
 * @param array $options array(recursive, checkFolder)
 * @return void
 */
	protected function _movePhpFiles($path, $options) {
		if (!is_dir($path)) {
			return;
		}

		$paths = $this->_paths;

		$this->_paths = array($path);
		$this->_files = array();
		if ($options['recursive']) {
			$this->_findFiles('php');
		} else {
			$this->_files = scandir($path);
			foreach ($this->_files as $i => $file) {
				if (strlen($file) < 5 || substr($file, -4) !== '.php') {
					unset($this->_files[$i]);
				}
			}
		}

		$cwd = getcwd();
		foreach ($this->_files as &$file) {
			$file = $cwd . DS . $file;

			$contents = file_get_contents($file);
			preg_match($options['regex'], $contents, $match);
			if (!$match) {
				continue;
			}

			$class = $match[1];

			if (substr($class, 0, 3) === 'Dbo') {
				$type = 'Dbo';
			} else {
				preg_match('@([A-Z][^A-Z]*)$@', $class, $match);
				if ($match) {
					$type = $match[1];
				} else {
					$type = 'unknown';
				}
			}

			preg_match('@^.*[\\\/]plugins[\\\/](.*?)[\\\/]@', $file, $match);
			$base = $cwd . DS;
			$plugin = false;
			if ($match) {
				$base = $match[0];
				$plugin = $match[1];
			}

			if ($options['checkFolder'] && !empty($this->_map[$type])) {
				$folder = str_replace('/', DS, $this->_map[$type]);
				$new = $base . $folder . DS . $class . '.php';
			} else {
				$new = dirname($file) . DS . $class . '.php';
			}

			if ($file === $new) {
				continue;
			}

			$dir = dirname($new);
			if (!is_dir($dir)) {
				new Folder($dir, true);
			}

			$this->out(__d('cake_console', 'Moving %s to %s', $file, $new), 1, Shell::VERBOSE);
			if (!$this->params['dry-run']) {
				if ($this->params['git']) {
					exec('git mv -f ' . escapeshellarg($file) . ' ' . escapeshellarg($file . '__'));
					exec('git mv -f ' . escapeshellarg($file . '__') . ' ' . escapeshellarg($new));
				} else {
					rename($file, $new);
				}
			}
		}

		$this->_paths = $paths;
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
			$this->out(__d('cake_console', 'Updating %s...', $file), 1, Shell::VERBOSE);
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
		$this->_files = array();
		foreach ($this->_paths as $path) {
			if (!is_dir($path)) {
				continue;
			}
			$Iterator = new RegexIterator(
				new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)),
				'/^.+\.(' . $extensions . ')$/i',
				RegexIterator::MATCH
			);
			foreach ($Iterator as $file) {
				if ($file->isFile()) {
					$this->_files[] = $file->getPathname();
				}
			}
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
			$this->out(__d('cake_console', ' * Updating %s', $pattern[0]), 1, Shell::VERBOSE);
			$contents = preg_replace($pattern[1], $pattern[2], $contents);
		}

		$this->out(__d('cake_console', 'Done updating %s', $file), 1);
		if (!$this->params['dry-run']) {
			file_put_contents($file, $contents);
		}
	}

/**
 * get the option parser
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$subcommandParser = array(
			'options' => array(
				'plugin' => array(
					'short' => 'p',
					'help' => __d('cake_console', 'The plugin to update. Only the specified plugin will be updated.')
				),
				'ext' => array(
					'short' => 'e',
					'help' => __d('cake_console', 'The extension(s) to search. A pipe delimited list, or a preg_match compatible subpattern'),
					'default' => 'php|ctp|thtml|inc|tpl'
				),
				'git' => array(
					'short' => 'g',
					'help' => __d('cake_console', 'Use git command for moving files around.'),
					'boolean' => true
				),
				'dry-run' => array(
					'short' => 'd',
					'help' => __d('cake_console', 'Dry run the update, no files will actually be modified.'),
					'boolean' => true
				)
			)
		);

		return parent::getOptionParser()
			->description(__d('cake_console', "A shell to help automate upgrading from CakePHP 1.3 to 2.0. \n" .
				"Be sure to have a backup of your application before running these commands."))
			->addSubcommand('all', array(
				'help' => __d('cake_console', 'Run all upgrade commands.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('tests', array(
				'help' => __d('cake_console', 'Update tests class names to FooTest rather than FooTestCase.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('locations', array(
				'help' => __d('cake_console', 'Move files and folders to their new homes.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('i18n', array(
				'help' => __d('cake_console', 'Update the i18n translation method calls.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('helpers', array(
				'help' => __d('cake_console', 'Update calls to helpers.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('basics', array(
				'help' => __d('cake_console', 'Update removed basics functions to PHP native functions.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('request', array(
				'help' => __d('cake_console', 'Update removed request access, and replace with $this->request.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('configure', array(
				'help' => __d('cake_console', "Update Configure::read() to Configure::read('debug')"),
				'parser' => $subcommandParser
			))
			->addSubcommand('constants', array(
				'help' => __d('cake_console', "Replace Obsolete constants"),
				'parser' => $subcommandParser
			))
			->addSubcommand('components', array(
				'help' => __d('cake_console', 'Update components to extend Component class.'),
				'parser' => $subcommandParser
			))
			->addSubcommand('exceptions', array(
				'help' => __d('cake_console', 'Replace use of cakeError with exceptions.'),
				'parser' => $subcommandParser
			));
	}

}
