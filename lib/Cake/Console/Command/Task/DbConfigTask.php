<?php
/**
 * The DbConfig Task handles creating and updating the database.php
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppShell', 'Console/Command');

/**
 * Task class for creating and updating the database configuration file.
 *
 * @package       Cake.Console.Command.Task
 */
class DbConfigTask extends AppShell {

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
	protected $_defaultConfig = array(
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
	);

/**
 * String name of the database config class name.
 * Used for testing.
 *
 * @var string
 */
	public $databaseClassName = 'DATABASE_CONFIG';

/**
 * initialization callback
 *
 * @return void
 */
	public function initialize() {
		$this->path = APP . 'Config' . DS;
	}

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		if (empty($this->args)) {
			$this->_interactive();
			$this->_stop();
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
		$dbConfigs = array();

		while ($done == false) {
			$name = '';

			while ($name == '') {
				$name = $this->in(__d('cake_console', "Name:"), null, 'default');
				if (preg_match('/[^a-z0-9_]/i', $name)) {
					$name = '';
					$this->out(__d('cake_console', 'The name may only contain unaccented latin characters, numbers or underscores'));
				} elseif (preg_match('/^[^a-z_]/i', $name)) {
					$name = '';
					$this->out(__d('cake_console', 'The name must start with an unaccented latin character or an underscore'));
				}
			}

			$datasource = $this->in(__d('cake_console', 'Datasource:'), array('Mysql', 'Postgres', 'Sqlite', 'Sqlserver'), 'Mysql');

			$persistent = $this->in(__d('cake_console', 'Persistent Connection?'), array('y', 'n'), 'n');
			if (strtolower($persistent) == 'n') {
				$persistent = 'false';
			} else {
				$persistent = 'true';
			}

			$host = '';
			while ($host == '') {
				$host = $this->in(__d('cake_console', 'Database Host:'), null, 'localhost');
			}

			$port = '';
			while ($port == '') {
				$port = $this->in(__d('cake_console', 'Port?'), null, 'n');
			}

			if (strtolower($port) == 'n') {
				$port = null;
			}

			$login = '';
			while ($login == '') {
				$login = $this->in(__d('cake_console', 'User:'), null, 'root');
			}
			$password = '';
			$blankPassword = false;

			while ($password == '' && $blankPassword == false) {
				$password = $this->in(__d('cake_console', 'Password:'));

				if ($password == '') {
					$blank = $this->in(__d('cake_console', 'The password you supplied was empty. Use an empty password?'), array('y', 'n'), 'n');
					if ($blank == 'y') {
						$blankPassword = true;
					}
				}
			}

			$database = '';
			while ($database == '') {
				$database = $this->in(__d('cake_console', 'Database Name:'), null, 'cake');
			}

			$prefix = '';
			while ($prefix == '') {
				$prefix = $this->in(__d('cake_console', 'Table Prefix?'), null, 'n');
			}
			if (strtolower($prefix) == 'n') {
				$prefix = null;
			}

			$encoding = '';
			while ($encoding == '') {
				$encoding = $this->in(__d('cake_console', 'Table encoding?'), null, 'n');
			}
			if (strtolower($encoding) == 'n') {
				$encoding = null;
			}

			$schema = '';
			if ($datasource == 'postgres') {
				while ($schema == '') {
					$schema = $this->in(__d('cake_console', 'Table schema?'), null, 'n');
				}
			}
			if (strtolower($schema) == 'n') {
				$schema = null;
			}

			$config = compact('name', 'datasource', 'persistent', 'host', 'login', 'password', 'database', 'prefix', 'encoding', 'port', 'schema');

			while ($this->_verify($config) == false) {
				$this->_interactive();
			}

			$dbConfigs[] = $config;
			$doneYet = $this->in(__d('cake_console', 'Do you wish to add another database configuration?'), null, 'n');

			if (strtolower($doneYet == 'n')) {
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
		$this->out(__d('cake_console', "Datasource:       %s", $datasource));
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
		$looksGood = $this->in(__d('cake_console', 'Look okay?'), array('y', 'n'), 'y');

		if (strtolower($looksGood) == 'y') {
			return $config;
		}
		return false;
	}

/**
 * Assembles and writes database.php
 *
 * @param array $configs Configuration settings to use
 * @return boolean Success
 */
	public function bake($configs) {
		if (!is_dir($this->path)) {
			$this->err(__d('cake_console', '%s not found', $this->path));
			return false;
		}

		$filename = $this->path . 'database.php';
		$oldConfigs = array();

		if (file_exists($filename)) {
			config('database');
			$db = new $this->databaseClassName;
			$temp = get_class_vars(get_class($db));

			foreach ($temp as $configName => $info) {
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

				if ($info['persistent'] === false) {
					$info['persistent'] = 'false';
				} else {
					$info['persistent'] = ($info['persistent'] == true) ? 'true' : 'false';
				}

				$oldConfigs[] = array(
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
				);
			}
		}

		foreach ($oldConfigs as $key => $oldConfig) {
			foreach ($configs as $k => $config) {
				if ($oldConfig['name'] == $config['name']) {
					unset($oldConfigs[$key]);
				}
			}
		}

		$configs = array_merge($oldConfigs, $configs);
		$out = "<?php\n";
		$out .= "class DATABASE_CONFIG {\n\n";

		foreach ($configs as $config) {
			$config = array_merge($this->_defaultConfig, $config);
			extract($config);

			if (strpos($datasource, 'Database/') === false) {
				$datasource = "Database/{$datasource}";
			}
			$out .= "\tpublic \${$name} = array(\n";
			$out .= "\t\t'datasource' => '{$datasource}',\n";
			$out .= "\t\t'persistent' => {$persistent},\n";
			$out .= "\t\t'host' => '{$host}',\n";

			if ($port) {
				$out .= "\t\t'port' => {$port},\n";
			}

			$out .= "\t\t'login' => '{$login}',\n";
			$out .= "\t\t'password' => '{$password}',\n";
			$out .= "\t\t'database' => '{$database}',\n";

			if ($schema) {
				$out .= "\t\t'schema' => '{$schema}',\n";
			}

			if ($prefix) {
				$out .= "\t\t'prefix' => '{$prefix}',\n";
			}

			if ($encoding) {
				$out .= "\t\t'encoding' => '{$encoding}'\n";
			}

			$out .= "\t);\n";
		}

		$out .= "}\n";
		$filename = $this->path . 'database.php';
		return $this->createFile($filename, $out);
	}

/**
 * Get a user specified Connection name
 *
 * @return void
 */
	public function getConfig() {
		App::uses('ConnectionManager', 'Model');
		$configs = ConnectionManager::enumConnectionObjects();

		$useDbConfig = key($configs);
		if (!is_array($configs) || empty($configs)) {
			return $this->execute();
		}
		$connections = array_keys($configs);

		if (count($connections) > 1) {
			$useDbConfig = $this->in(__d('cake_console', 'Use Database Config') . ':', $connections, $useDbConfig);
		}
		return $useDbConfig;
	}

/**
 * get the option parser
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
				__d('cake_console', 'Bake new database configuration settings.')
			);
	}

}
