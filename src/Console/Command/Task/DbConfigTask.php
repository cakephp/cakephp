<?php
/**
 * The DbConfig Task handles creating and updating the datasources.php
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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

/**
 * Task class for creating and updating the database configuration file.
 *
 */
class DbConfigTask extends Shell {

/**
 * path to CONFIG directory
 *
 * @var string
 */
	public $path = null;

/**
 * Default configuration settings to use
 *
 * @var array
 */
	protected $_defaultConfig = [
		'name' => 'default',
		'datasource' => 'Database/Mysql',
		'persistent' => 'false',
		'host' => 'localhost',
		'login' => 'root',
		'password' => 'password',
		'database' => 'project_name',
		'schema' => null,
		'prefix' => null,
		'encoding' => null,
		'port' => null
	];

/**
 * initialization callback
 *
 * @return void
 */
	public function initialize() {
		$this->path = APP . 'Config/';
	}

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		if (empty($this->args)) {
			$this->_interactive();
			return $this->_stop();
		}
	}

/**
 * Interactive interface
 *
 * @return void
 */
	protected function _interactive() {
		$this->hr();
		$this->out(__d('cake_console', 'Database Configuration:'));
		$this->hr();
		$done = false;
		$dbConfigs = [];

		while (!$done) {
			$name = '';

			while (!$name) {
				$name = $this->in(__d('cake_console', "Name:"), null, 'default');
				if (preg_match('/[^a-z0-9_]/i', $name)) {
					$name = '';
					$this->out(__d('cake_console', 'The name may only contain unaccented latin characters, numbers or underscores'));
				} elseif (preg_match('/^[^a-z_]/i', $name)) {
					$name = '';
					$this->out(__d('cake_console', 'The name must start with an unaccented latin character or an underscore'));
				}
			}

			$datasource = $this->in(__d('cake_console', 'Datasource:'), ['Mysql', 'Postgres', 'Sqlite', 'Sqlserver'], 'Mysql');

			$persistent = $this->in(__d('cake_console', 'Persistent Connection?'), ['y', 'n'], 'n');
			if (strtolower($persistent) === 'n') {
				$persistent = 'false';
			} else {
				$persistent = 'true';
			}

			$host = '';
			while (!$host) {
				$host = $this->in(__d('cake_console', 'Database Host:'), null, 'localhost');
			}

			$port = '';
			while (!$port) {
				$port = $this->in(__d('cake_console', 'Port?'), null, 'n');
			}

			if (strtolower($port) === 'n') {
				$port = null;
			}

			$login = '';
			while (!$login) {
				$login = $this->in(__d('cake_console', 'User:'), null, 'root');
			}
			$password = '';
			$blankPassword = false;

			while (!$password && !$blankPassword) {
				$password = $this->in(__d('cake_console', 'Password:'));

				if (!$password) {
					$blank = $this->in(__d('cake_console', 'The password you supplied was empty. Use an empty password?'), ['y', 'n'], 'n');
					if ($blank === 'y') {
						$blankPassword = true;
					}
				}
			}

			$database = '';
			while (!$database) {
				$database = $this->in(__d('cake_console', 'Database Name:'), null, 'cake');
			}

			$prefix = '';
			while (!$prefix) {
				$prefix = $this->in(__d('cake_console', 'Table Prefix?'), null, 'n');
			}
			if (strtolower($prefix) === 'n') {
				$prefix = null;
			}

			$encoding = '';
			while (!$encoding) {
				$encoding = $this->in(__d('cake_console', 'Table encoding?'), null, 'n');
			}
			if (strtolower($encoding) === 'n') {
				$encoding = null;
			}

			$schema = '';
			if ($datasource === 'postgres') {
				while (!$schema) {
					$schema = $this->in(__d('cake_console', 'Table schema?'), null, 'n');
				}
			}
			if (strtolower($schema) === 'n') {
				$schema = null;
			}

			$config = compact('name', 'datasource', 'persistent', 'host', 'login', 'password', 'database', 'prefix', 'encoding', 'port', 'schema');

			while (!$this->_verify($config)) {
				$this->_interactive();
			}

			$dbConfigs[] = $config;
			$doneYet = $this->in(__d('cake_console', 'Do you wish to add another database configuration?'), null, 'n');

			if (strtolower($doneYet === 'n')) {
				$done = true;
			}
		}

		$this->bake($dbConfigs);
		config('database');
		return true;
	}

/**
 * Output verification message and bake if it looks good
 *
 * @param array $config
 * @return boolean True if user says it looks good, false otherwise
 */
	protected function _verify($config) {
		$config = array_merge($this->_defaultConfig, $config);
		extract($config);
		$this->out();
		$this->hr();
		$this->out(__d('cake_console', 'The following database configuration will be created:'));
		$this->hr();
		$this->out(__d('cake_console', "Name:         %s", $name));
		$this->out(__d('cake_console', "Datasource:   %s", $datasource));
		$this->out(__d('cake_console', "Persistent:   %s", $persistent));
		$this->out(__d('cake_console', "Host:         %s", $host));

		if ($port) {
			$this->out(__d('cake_console', "Port:         %s", $port));
		}

		$this->out(__d('cake_console', "User:         %s", $login));
		$this->out(__d('cake_console', "Pass:         %s", str_repeat('*', strlen($password))));
		$this->out(__d('cake_console', "Database:     %s", $database));

		if ($prefix) {
			$this->out(__d('cake_console', "Table prefix: %s", $prefix));
		}

		if ($schema) {
			$this->out(__d('cake_console', "Schema:       %s", $schema));
		}

		if ($encoding) {
			$this->out(__d('cake_console', "Encoding:     %s", $encoding));
		}

		$this->hr();
		$looksGood = $this->in(__d('cake_console', 'Look okay?'), ['y', 'n'], 'y');

		if (strtolower($looksGood) === 'y') {
			return $config;
		}
		return false;
	}

/**
 * Assembles and writes datasources.php
 *
 * @param array $configs Configuration settings to use
 * @return boolean Success
 */
	public function bake($configs) {
		if (!is_dir($this->path)) {
			$this->err(__d('cake_console', '%s not found', $this->path));
			return false;
		}

		$filename = $this->path . 'datasources.php';
		$oldConfigs = [];
		if (file_exists($filename)) {
			$oldConfigs = Configure::read('Datasource');

			foreach ($oldConfigs as $configName => $info) {
				$info = array_merge($this->_defaultConfig, $info);

				if (!isset($info['schema'])) {
					$info['schema'] = null;
				}
				if (!isset($info['encoding'])) {
					$info['encoding'] = null;
				}
				if (!isset($info['port'])) {
					$info['port'] = null;
				}

				$info['persistent'] = var_export((bool)$info['persistent'], true);

				$oldConfigs[$configName] = [
					'name' => $configName,
					'datasource' => $info['datasource'],
					'persistent' => $info['persistent'],
					'host' => $info['host'],
					'port' => $info['port'],
					'login' => $info['login'],
					'password' => $info['password'],
					'database' => $info['database'],
					'prefix' => $info['prefix'],
					'schema' => $info['schema'],
					'encoding' => $info['encoding']
				];
			}
		}

		foreach ($oldConfigs as $key => $oldConfig) {
			foreach ($configs as $config) {
				if ($oldConfig['name'] == $config['name']) {
					unset($oldConfigs[$key]);
				}
			}
		}

		$configs = array_merge($oldConfigs, $configs);
		$out = "<?php\n";
		$out .= "namespace " . Configure::read('App.namespace') . "\Config;\n";
		$out .= "use Cake\Core\Configure;\n\n";

		foreach ($configs as $config) {
			$config = array_merge($this->_defaultConfig, $config);
			extract($config);

			if (strpos($datasource, 'Database/') === false) {
				$datasource = "Database/{$datasource}";
			}
			$out .= "Configure::write('Datasource.{$name}', [\n";
			$out .= "\t'datasource' => '{$datasource}',\n";
			$out .= "\t'persistent' => {$persistent},\n";
			$out .= "\t'host' => '{$host}',\n";

			if ($port) {
				$out .= "\t'port' => {$port},\n";
			}

			$out .= "\t'login' => '{$login}',\n";
			$out .= "\t'password' => '{$password}',\n";
			$out .= "\t'database' => '{$database}',\n";

			if ($schema) {
				$out .= "\t'schema' => '{$schema}',\n";
			}

			if ($prefix) {
				$out .= "\t'prefix' => '{$prefix}',\n";
			}

			if ($encoding) {
				$out .= "\t'encoding' => '{$encoding}'\n";
			}

			$out .= "]);\n";
		}

		$filename = $this->path . 'datasources.php';
		return $this->createFile($filename, $out);
	}

/**
 * Get a user specified Connection name
 *
 * @return void
 */
	public function getConfig() {
		$configs = ConnectionManager::configured();

		$useDbConfig = current($configs);
		if (!is_array($configs) || empty($configs)) {
			return $this->execute();
		}

		if (count($configs) > 1) {
			$useDbConfig = $this->in(__d('cake_console', 'Use Database Config') . ':', $configs, $useDbConfig);
		}
		return $useDbConfig;
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			__d('cake_console', 'Bake new database configuration settings.')
		);

		return $parser;
	}

}
