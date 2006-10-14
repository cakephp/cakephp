#!/usr/bin/php -q
<?php
/* SVN FILE: $Id$ */
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * Bake is CakePHP's code generation script, which can help you kickstart
 * application development by writing fully functional skeleton controllers,
 * models, and views. Going further, Bake can also write Unit Tests for you.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2005, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.scripts.bake
 * @since			CakePHP v 0.10.0.1232
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
	define ('DS', DIRECTORY_SEPARATOR);
	if (function_exists('ini_set')) {
		ini_set('display_errors', '1');
		ini_set('error_reporting', '7');
	}

	$app = null;
	$root = dirname(dirname(dirname(__FILE__)));
	$core = null;
	$here = $argv[0];
	$help = null;
	$project = null;

	for ($i = 1; $i < count($argv); $i += 2) {
		switch ($argv[$i]) {
			case '-a':
			case '-app':
				$app = $argv[$i + 1];
			break;
			case '-c':
			case '-core':
				$core = $argv[$i + 1];
			break;
			case '-r':
			case '-root':
				$root = $argv[$i + 1];
			break;
			case '-h':
			case '-help':
				$help = true;
			break;
			case '-p':
			case '-project':
				$project = true;
				$projectPath = $argv[$i + 1];
				$app = $argv[$i + 1];
			break;
		}
	}
	if(!$app) {
		$app = $argv[1];
	}
	if(!is_dir($app)) {
		$project = true;
		$projectPath = $app;

	}

	if($project) {
		$app = $projectPath;
	}

	$shortPath = str_replace($root, '', $app);
	$shortPath = str_replace('../', '', $shortPath);
	$shortPath = str_replace('//', '/', $shortPath);

	$pathArray = explode('/', $shortPath);
	$appDir = array_pop($pathArray);
	$rootDir = implode('/', $pathArray);
	$rootDir = str_replace('//', '', $rootDir);

	if(!$rootDir) {
		$rootDir = $root;
		$projectPath = $root.DS.$appDir;
	}

	define ('ROOT', $rootDir);
	define ('APP_DIR', $appDir);

	define ('DEBUG', 1);;
	define('CAKE_CORE_INCLUDE_PATH', $root);

	if(function_exists('ini_set')) {
		ini_set('include_path',ini_get('include_path').
													PATH_SEPARATOR.CAKE_CORE_INCLUDE_PATH.DS.
													PATH_SEPARATOR.ROOT.DS.APP_DIR.DS);
		define('APP_PATH', null);
		define('CORE_PATH', null);
	} else {
		define('APP_PATH', ROOT . DS . APP_DIR . DS);
		define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
	}
	/**
	 * Tag template for a div with a class attribute.
	 */
		define('TAG_DIV', '<div class="%s">%s</div>');
	/**
	 * Tag template for a paragraph with a class attribute.
	 */
		define('TAG_P_CLASS', '<p class="%s">%s</p>');
	/**
	 * Tag template for a label with a for attribute.
	 */
		define('TAG_LABEL', '<label for="%s">%s</label>');
	/**
	 * Tag template for a fieldset with a legend tag inside.
	 */
		define('TAG_FIELDSET', '<fieldset><legend>%s</legend>%s</label>');


	require_once (CORE_PATH.'cake'.DS.'basics.php');
	require_once (CORE_PATH.'cake'.DS.'config'.DS.'paths.php');
	require_once (CORE_PATH.'cake'.DS.'dispatcher.php');
	require_once (CORE_PATH.'cake'.DS.'scripts'.DS.'templates'.DS.'skel'.DS.'config'.DS.'core.php');
	uses ('inflector', 'model'.DS.'model');
	require_once (CORE_PATH.'cake'.DS.'app_model.php');
	require_once (CORE_PATH.'cake'.DS.'app_controller.php');
	uses ('neat_array', 'model'.DS.'connection_manager', 'controller'.DS.'controller', 'session',
			'configure', 'security', DS.'controller'.DS.'scaffold');

	$pattyCake = new Bake();
	if($help === true)
	{
		$pattyCake->help();
		exit();
	}
	if($project === true)
	{
		$pattyCake->project($projectPath);
		exit();
	}
	$pattyCake->main();
/**
 * Bake is a command-line code generation utility for automating programmer chores.
 *
 * @package		cake
 * @subpackage	cake.cake.scripts
 */
class Bake {

/**
 * Standard input stream.
 *
 * @var filehandle
 */
	var $stdin;
/**
 * Standard output stream.
 *
 * @var filehandle
 */
	var $stdout;
/**
 * Standard error stream.
 *
 * @var filehandle
 */
	var $stderr;
/**
 * Associated controller name.
 *
 * @var string
 */
	var $controllerName = null;
/**
 * If true, Bake will ask for permission to perform actions.
 *
 * @var boolean
 */
	var $interactive = false;

	var $__modelAlias = false;
/**
 * Private helper function for constructor
 * @access private
 */
	function __construct() {
		$this->stdin = fopen('php://stdin', 'r');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');
		$this->welcome();
	}
/**
 * Constructor.
 *
 * @return Bake
 */
	function Bake() {
		return $this->__construct();
	}
/**
 * Main-loop method.
 *
 */
	function main() {
		if(!file_exists(CONFIGS.'database.php')) {
			$this->stdout('');
			$this->stdout('');
			$this->stdout('Your database configuration was not found. Take a moment to create one:');
			$this->stdout('');
			$this->stdout('');
			$this->doDbConfig();
		}
		require_once (CONFIGS.'database.php');

		$this->stdout('[M]odel');
		$this->stdout('[C]ontroller');
		$this->stdout('[V]iew');
		$invalidSelection = true;

		while ($invalidSelection) {
			$classToBake = strtoupper($this->getInput('Please select a class to Bake:', array('M', 'V', 'C')));
			switch($classToBake) {
				case 'M':
					$invalidSelection = false;
					$this->doModel();
					break;
				case 'V':
					$invalidSelection = false;
					$this->doView();
					break;
				case 'C':
					$invalidSelection = false;
					$this->doController();
					break;
				default:
					$this->stdout('You have made an invalid selection. Please choose a type of class to Bake by entering M, V, or C.');
			}
		}
	}
/**
 * Database configuration setup.
 *
 */
	function doDbConfig() {
		$this->hr();
		$this->stdout('Database Configuration Bake:');
		$this->hr();

		$driver = '';

		while ($driver == '') {
			$driver = $this->getInput('What database driver would you like to use?', array('mysql','mysqli','mssql','sqlite','postgres', 'odbc'), 'mysql');
			if ($driver == '') {
				$this->stdout('The database driver supplied was empty. Please supply a database driver.');
			}
		}

		switch($driver) {
			case 'mysql':
			$connect = 'mysql_connect';
			break;
			case 'mysqli':
			$connect = 'mysqli_connect';
			break;
			case 'mssql':
			$connect = 'mssql_connect';
			break;
			case 'sqlite':
			$connect = 'sqlite_open';
			break;
			case 'postgres':
			$connect = 'pg_connect';
			break;
			case 'odbc':
			$connect = 'odbc_connect';
			break;
			default:
			$this->stdout('The connection parameter could not be set.');
			break;
		}

		$host = '';

		while ($host == '') {
			$host = $this->getInput('What is the hostname for the database server?', null, 'localhost');
			if ($host == '') {
				$this->stdout('The host name you supplied was empty. Please supply a hostname.');
			}
		}
		$login = '';

		while ($login == '') {
			$login = $this->getInput('What is the database username?');

			if ($login == '') {
				$this->stdout('The database username you supplied was empty. Please try again.');
			}
		}
		$password = '';
		$blankPassword = false;

		while ($password == '' && $blankPassword == false) {
			$password = $this->getInput('What is the database password?');
			if ($password == '') {
				$blank = $this->getInput('The password you supplied was empty. Use an empty password?', array('y', 'n'), 'n');
				if($blank == 'y')
				{
					$blankPassword = true;
				}
			}
		}
		$database = '';

		while ($database == '') {
			$database = $this->getInput('What is the name of the database you will be using?');

			if ($database == '')  {
				$this->stdout('The database name you supplied was empty. Please try again.');
			}
		}

		$prefix = '';

		while ($prefix == '') {
			$prefix = $this->getInput('Enter a table prefix?', null, 'n');
		}
		if(low($prefix) == 'n') {
			$prefix = '';
		}

		$this->stdout('');
		$this->hr();
		$this->stdout('The following database configuration will be created:');
		$this->hr();
		$this->stdout("Driver:        $driver");
		$this->stdout("Connection:    $connect");
		$this->stdout("Host:          $host");
		$this->stdout("User:          $login");
		$this->stdout("Pass:          " . str_repeat('*', strlen($password)));
		$this->stdout("Database:      $database");
		$this->stdout("Table prefix:  $prefix");
		$this->hr();
		$looksGood = $this->getInput('Look okay?', array('y', 'n'), 'y');

		if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes') {
			$this->bakeDbConfig($driver, $connect, $host, $login, $password, $database, $prefix);
		} else {
			$this->stdout('Bake Aborted.');
		}
	}
/**
 * Action to create a Model.
 *
 */
	function doModel()
	{
		$this->hr();
		$this->stdout('Model Bake:');
		$this->hr();
		$this->interactive = true;
		$dbConnection = 'default';
		/*$usingDefault = $this->getInput('Will your model be using a database connection setting other than the default?');
		if (strtolower($usingDefault) == 'y' || strtolower($usingDefault) == 'yes')
		{
			$dbConnection = $this->getInput('Please provide the name of the connection you wish to use.');
		}*/
		$db =& ConnectionManager::getDataSource($dbConnection);
		$usePrefix = empty($db->config['prefix']) ? '' : $db->config['prefix'];
		if ($usePrefix) {
			$tables = array();
			foreach ($db->listSources() as $table) {
				if (!strncmp($table, $usePrefix, strlen($usePrefix))) {
					$tables[] = substr($table, strlen($usePrefix));
				}
			}
		} else {
			$tables = $db->listSources();
		}

		$this->stdout('Possible models based on your current database:');

		for ($i = 0; $i < count($tables); $i++) {
			$this->stdout($i + 1 . ". " . $this->__modelName($tables[$i]));
		}

		$enteredModel = '';

		while ($enteredModel == '') {
			$enteredModel = $this->getInput('Enter a number from the list above, or type in the name of another model.');

			if ($enteredModel == '' || intval($enteredModel) > $i) {
				$this->stdout('Error:');
				$this->stdout("The model name you supplied was empty, or the number \nyou selected was not an option. Please try again.");
				$enteredModel = '';
			}
		}

		if (intval($enteredModel) > 0 && intval($enteredModel) <= $i ) {
			$currentModelName = $this->__modelName($tables[intval($enteredModel) - 1]);
		} else {
			$currentModelName = $this->__modelName($enteredModel);
		}

		$currentTableName = Inflector::tableize($currentModelName);
		if(array_search($currentTableName, $tables) === false) {
			$this->stdout("\nGiven your model named '$currentModelName', Cake would expect a database table named '" . $currentTableName . "'.");
			$tableIsGood = $this->getInput('Is this correct?', array('y','n'), 'y');
		}

		if (strtolower($tableIsGood) == 'n' || strtolower($tableIsGood) == 'no') {
			$table = $this->getInput('What is the name of the table (enter "null" to use NO table)?');
		}


		$wannaDoValidation = $this->getInput('Would you like to supply validation criteria for the fields in your model?', array('y','n'), 'y');

		$tempModel = new Model(false, $currentTableName);
		$modelFields = $db->describe($tempModel);

		$validate = array();

		if (array_search($currentTableName, $tables) !== false && (strtolower($wannaDoValidation) == 'y' || strtolower($wannaDoValidation) == 'yes')) {
			foreach($modelFields as $field) {
				$this->stdout('');
				$prompt .= 'Name: ' . $field['name'] . "\n";
				$prompt .= 'Type: ' . $field['type'] . "\n";
				$prompt .= '---------------------------------------------------------------'."\n";
				$prompt .= 'Please select one of the following validation options:'."\n";
				$prompt .= '---------------------------------------------------------------'."\n";
				$prompt .= "1- VALID_NOT_EMPTY\n";
				$prompt .= "2- VALID_EMAIL\n";
				$prompt .= "3- VALID_NUMBER\n";
				$prompt .= "4- VALID_YEAR\n";
				$prompt .= "5- Do not do any validation on this field.\n\n";
				$prompt .= "... or enter in a valid regex validation string.\n\n";

				if($field['name'] == 'id' || $field['name'] == 'created' || $field['name'] == 'modified') {
					$validation = $this->getInput($prompt, null, '5');
				} else {
					$validation = $this->getInput($prompt, null, '1');
				}

				switch ($validation) {
					case '1':
						$validate[$field['name']] = 'VALID_NOT_EMPTY';
						break;
					case '2':
						$validate[$field['name']] = 'VALID_EMAIL';
						break;
					case '3':
						$validate[$field['name']] = 'VALID_NUMBER';
						break;
					case '4':
						$validate[$field['name']] = 'VALID_YEAR';
						break;
					case '5':
						break;
					default:
						$validate[$field['name']] = $validation;
					break;
				}
			}
		}

		$wannaDoAssoc = $this->getInput('Would you like to define model associations (hasMany, hasOne, belongsTo, etc.)?', array('y','n'), 'y');

		if((strtolower($wannaDoAssoc) == 'y' || strtolower($wannaDoAssoc) == 'yes')) {
			$this->stdout('One moment while I try to detect any associations...');
			//Look for belongsTo
			foreach($modelFields as $field) {
				$offset = strpos($field['name'], '_id');
				if($offset !== false) {
					$belongsToClasses[] = $this->__modelNameFromKey($field['name']);
				}
			}
			//Look for hasOne and hasMany and hasAndBelongsToMany
			foreach($tables as $otherTable) {
				$tempOtherModel = new Model(false, $otherTable);
				$modelFieldsTemp = $db->describe($tempOtherModel);

				foreach($modelFieldsTemp as $field) {
					if($field['name'] == $this->__modelKey($currentModelName)) {
						$hasOneClasses[] = $this->__modelName($otherTable);
						$hasManyClasses[] = $this->__modelName($otherTable);
					}
				}
				$offset = strpos($otherTable, $currentTableName . '_');
				if($offset !== false) {
					$offset = strlen($currentTableName . '_');
					echo $this->__modelName(substr($otherTable, $offset));
					$hasAndBelongsToManyClasses[] = $this->__modelName(substr($otherTable, $offset));
				}
				$offset = strpos($otherTable, '_' . $currentTableName);
				if ($offset !== false) {
					echo $this->__modelName(substr($otherTable, 0, $offset));
					$hasAndBelongsToManyClasses[] = $this->__modelName(substr($otherTable, 0, $offset));
				}
			}

			$this->stdout('Done.');
			$this->hr();
			//if none found...
			if(count($hasOneClasses) < 1 && count($hasManyClasses) < 1 && count($hasAndBelongsToManyClasses) < 1 && count($belongsToClasses) < 1) {
				$this->stdout('None found.');
			} else {
				$this->stdout('Please confirm the following associations:');
				$this->hr();

				if(count($belongsToClasses)) {
					for($i = 0; $i < count($belongsToClasses); $i++) {
						$response = $this->getInput("$currentModelName belongsTo {$belongsToClasses[$i]}?", array('y','n'), 'y');

						if($response == 'y') {
							$modelAssociations['belongsTo'][] = $belongsToClasses[$i];
						}
					}
				}

				if(count($hasOneClasses)) {
					for($i = 0; $i < count($hasOneClasses); $i++) {
						$response = $this->getInput("$currentModelName hasOne {$hasOneClasses[$i]}?", array('y','n'), 'y');

						if($response == 'y') {
							$modelAssociations['hasOne'][] = $hasOneClasses[$i];
						}
					}
				}

				if(count($hasManyClasses)) {
					for($i = 0; $i < count($hasManyClasses); $i++) {
						$response = $this->getInput("$currentModelName hasMany {$hasManyClasses[$i]}?", array('y','n'), 'y');

						if($response == 'y') {
							$modelAssociations['hasMany'][] = $hasManyClasses[$i];
						}
					}
				}

				if(count($hasAndBelongsToManyClasses)) {
					for($i = 0; $i < count($hasAndBelongsToManyClasses); $i++) {
						$response = $this->getInput("$currentModelName hasAndBelongsToMany {$hasAndBelongsToManyClasses[$i]}?", array('y','n'), 'y');

						if($response == 'y') {
							$modelAssociations['hasAndBelongsToMany'][] = $hasAndBelongsToManyClasses[$i];
						}
					}
				}
			}
			$wannaDoMoreAssoc = $this->getInput('Would you like to define some additional model associations?', array('y','n'), 'y');

			while((strtolower($wannaDoMoreAssoc) == 'y' || strtolower($wannaDoMoreAssoc) == 'yes')) {
				$assocs = array(1=>'belongsTo', 2=>'hasOne', 3=>'hasMany', 4=>'hasAndBelongsToMany');
				$bad = true;
				while($bad) {
					$this->stdout('What is the association type?');
					$prompt = "1- belongsTo\n";
					$prompt .= "2- hasOne\n";
					$prompt .= "3- hasMany\n";
					$prompt .= "4- hasAndBelongsToMany\n";
					$assocType = intval($this->getInput($prompt, null, null));

					if(intval($assocType) < 1 || intval($assocType) > 4) {
						$this->stdout('The selection you entered was invalid. Please enter a number between 1 and 4.');
					} else {
						$bad = false;
					}
				}
				$assocClassName = $this->getInput('Classname of associated Model?');
				$modelAssociations[$assocs[$assocType]][] = $assocClassName;
				$this->stdout("Association '$currentModelName {$assocs[$assocType]} $assocClassName' defined.");
				$wannaDoMoreAssoc = $this->getInput('Define another association?', array('y','n'), 'y');
			}
		}
		$this->stdout('');
		$this->hr();
		$this->stdout('The following model will be created:');
		$this->hr();
		$this->stdout("Model Name:    $currentModelName");
		$this->stdout("DB Connection: " . ($usingDefault ? 'default' : $dbConnection));
		$this->stdout("Model Table:   " . $currentTableName);
		$this->stdout("Validation:    " . print_r($validate, true));

		if(count($belongsToClasses) || count($hasOneClasses) || count($hasManyClasses) || count($hasAndBelongsToManyClasses)) {
			$this->stdout("Associations:");

			if(count($modelAssociations['belongsTo'])) {
				for($i = 0; $i < count($modelAssociations['belongsTo']); $i++) {
					$this->stdout("            $currentModelName belongsTo {$modelAssociations['belongsTo'][$i]}");
				}
			}

			if(count($modelAssociations['hasOne'])) {
				for($i = 0; $i < count($modelAssociations['hasOne']); $i++) {
					$this->stdout("            $currentModelName hasOne	{$modelAssociations['hasOne'][$i]}");
				}
			}

			if(count($modelAssociations['hasMany'])) {
				for($i = 0; $i < count($modelAssociations['hasMany']); $i++) {
					$this->stdout("            $currentModelName hasMany   {$modelAssociations['hasMany'][$i]}");
				}
			}

			if(count($modelAssociations['hasAndBelongsToMany'])) {
				for($i = 0; $i < count($modelAssociations['hasAndBelongsToMany']); $i++) {
					$this->stdout("            $currentModelName hasAndBelongsToMany {$modelAssociations['hasAndBelongsToMany'][$i]}");
				}
			}
		}
		$this->hr();
		$looksGood = $this->getInput('Look okay?', array('y','n'), 'y');

		if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes') {
			if ($currentTableName == Inflector::tableize($currentModelName)) {
				// set it to null...
				// putting $useTable in the model
				// is unnecessary.
				$modelTableName = null;
			}
			$this->bakeModel($currentModelName, $dbConnection, $currentTableName, $validate, $modelAssociations);

			if ($this->doUnitTest()) {
				$this->bakeUnitTest('model', $currentModelName);
			}
		} else {
			$this->stdout('Bake Aborted.');
		}
	}
/**
 * Action to create a View.
 *
 */
	function doView() {
		$this->hr();
		$this->stdout('View Bake:');
		$this->hr();
		$uses = array();
		$wannaUseSession = 'y';
		$wannaDoScaffold = 'y';

		$controllerName = '';
		while ($controllerName == '') {
			$controllerName = $this->getInput('Controller Name? (plural)');

			if ($controllerName == '') {
				$this->stdout('The controller name you supplied was empty. Please try again.');
			}
		}
		$controllerPath = $this->__controllerPath($controllerName);
		$controllerName = $this->__controllerName($controllerName);

		$doItInteractive = $this->getInput("Would you like bake to build your views interactively?\nWarning: Choosing no will overwrite {$controllerClassName} views if it exist.", array('y','n'), 'y');

		if (strtolower($doItInteractive) == 'y' || strtolower($doItInteractive) == 'yes') {
			$this->interactive = true;
			$wannaDoScaffold = $this->getInput("Would you like to create some scaffolded views (index, add, view, edit) for this controller?\nNOTE: Before doing so, you'll need to create your controller and model classes (including associated models).", array('y','n'), 'n');
		}

		$admin = null;
		if (strtolower($wannaDoScaffold) == 'y' || strtolower($wannaDoScaffold) == 'yes') {
			$wannaDoAdmin = $this->getInput("Would you like to create the views for admin routing?", array('y','n'), 'n');
		}

		if ((strtolower($wannaDoAdmin) == 'y' || strtolower($wannaDoAdmin) == 'yes')) {
			require(CONFIGS.'core.php');
			if(defined('CAKE_ADMIN')) {
				$admin = CAKE_ADMIN . '_';
			} else {
				$adminRoute = '';
				$this->stdout('You need to enable CAKE_ADMIN in /app/config/core.php to use admin routing.');
				$this->stdout('What would you like the admin route to be?');
				$this->stdout('Example: www.example.com/admin/controller');
				while ($adminRoute == '') {
					$adminRoute = $this->getInput("What would you like the admin route to be?", null, 'admin');
				}
				if($this->__addAdminRoute($adminRoute) !== true){
					$this->stdout('Unable to write to /app/config/core.php.');
					$this->stdout('You need to enable CAKE_ADMIN in /app/config/core.php to use admin routing.');
					exit();
				} else {
					$admin = $adminRoute . '_';
				}
			}
		}
		if (strtolower($wannaDoScaffold) == 'y' || strtolower($wannaDoScaffold) == 'yes') {
			$file = CONTROLLERS . $controllerPath . '_controller.php';

			if(!file_exists($file)) {
				$shortPath = str_replace(ROOT, null, $file);
				$shortPath = str_replace('../', '', $shortPath);
				$shortPath = str_replace('//', '/', $shortPath);
				$this->stdout('');
				$this->stdout("The file '$shortPath' could not be found.\nIn order to scaffold, you'll need to first create the controller. ");
				$this->stdout('');
				die();
			} else {
				loadController($controllerName);
				$controllerClassName = $controllerName.'Controller';
				$controllerObj = & new $controllerClassName();

				if(!in_array('Html', $controllerObj->helpers)) {
					$controllerObj->helpers[] = 'Html';
				}
				if(!in_array('Form', $controllerObj->helpers)) {
					$controllerObj->helpers[] = 'Form';
				}

				loadModels();
				$controllerObj->constructClasses();
				$currentModelName = $controllerObj->modelClass;
				$this->__modelClass = $currentModelName;
				$modelKey = Inflector::underscore($currentModelName);
				$modelObj =& ClassRegistry::getObject($modelKey);

				$singularName = $this->__singularName($currentModelName);
				$pluralName = $this->__pluralName($currentModelName);
				$singularHumanName = $this->__singularHumanName($modelObj->name);
				$pluralHumanName = $this->__pluralHumanName($controllerName);

				$fieldNames = $controllerObj->generateFieldNames(null, false);

				//-------------------------[INDEX]-------------------------//
				$indexView = null;
				if(!empty($modelObj->alias)) {
					foreach ($modelObj->alias as $key => $value) {
						$alias[] = $key;
					}
				}
				$indexView .= "<div class=\"{$pluralName}\">\n";
				$indexView .= "<h2>List " . $pluralHumanName . "</h2>\n\n";
				$indexView .= "<table cellpadding=\"0\" cellspacing=\"0\">\n";
				$indexView .= "<tr>\n";

				foreach ($fieldNames as $fieldName) {
					$indexView .= "\t<th>".$fieldName['prompt']."</th>\n";
				}
				$indexView .= "\t<th>Actions</th>\n";
				$indexView .= "</tr>\n";
				$indexView .= "<?php foreach (\${$pluralName} as \${$singularName}): ?>\n";
				$indexView .= "<tr>\n";
				$count = 0;
				foreach($fieldNames as $field => $value) {
					if(isset($value['foreignKey'])) {
						$otherModelName = $this->__modelName($value['model']);
						$otherModelKey = Inflector::underscore($otherModelName);
						$otherModelObj =& ClassRegistry::getObject($otherModelKey);
						$otherControllerName = $this->__controllerName($otherModelName);
						$otherControllerPath = $this->__controllerPath($otherControllerName);
						if(is_object($otherModelObj)) {
							$displayField = $otherModelObj->getDisplayField();
							$indexView .= "\t<td>&nbsp;<?php echo \$html->link(\$".$singularName."['{$alias[$count]}']['{$displayField}'], '/" . $otherControllerPath . "/view/' .\$".$singularName."['{$alias[$count]}']['{$otherModelObj->primaryKey}'])?></td>\n";
						} else {
							$indexView .= "\t<td><?php echo \$".$singularName."['{$modelObj->name}']['{$field}']; ?></td>\n";
						}
						$count++;
					} else {
						$indexView .= "\t<td><?php echo \$".$singularName."['{$modelObj->name}']['{$field}']; ?></td>\n";
					}
				}
				$indexView .= "\t<td nowrap>\n";
				$indexView .= "\t\t<?php echo \$html->link('View','/{$controllerPath}/view/' . \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}'])?>\n";
				$indexView .= "\t\t<?php echo \$html->link('Edit','/{$controllerPath}/edit/' . \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}'])?>\n";
				$indexView .= "\t\t<?php echo \$html->link('Delete','/{$controllerPath}/delete/' . \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}'], null, 'Are you sure you want to delete: id ' . \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}'])?>\n";
				$indexView .= "\t</td>\n";
				$indexView .= "</tr>\n";
				$indexView .= "<?php endforeach; ?>\n";
				$indexView .= "</table>\n\n";
				$indexView .= "<ul class=\"actions\">\n";
				$indexView .= "\t<li><?php echo \$html->link('New {$singularHumanName}', '/{$controllerPath}/add'); ?></li>\n";
				$indexView .= "</ul>\n";
				$indexView .= "</div>";

				//-------------------------[VIEW]-------------------------//
				$viewView = null;

				$viewView .= "<div class=\"{$singularName}\">\n";
				$viewView .= "<h2>View " . $singularHumanName . "</h2>\n\n";
				$viewView .= "<dl>\n";
				$count = 0;
				foreach($fieldNames as $field => $value) {
					$viewView .= "\t<dt>" . $value['prompt'] . "</dt>\n";
					if(isset($value['foreignKey'])) {
						$otherModelName = $this->__modelName($value['model']);
						$otherModelKey = Inflector::underscore($otherModelName);
						$otherModelObj =& ClassRegistry::getObject($otherModelKey);
						$otherControllerName = $this->__controllerName($otherModelName);
						$otherControllerPath = $this->__controllerPath($otherControllerName);
						$displayField = $otherModelObj->getDisplayField();
						$viewView .= "\t<dd>&nbsp;<?php echo \$html->link(\$".$singularName."['{$alias[$count]}']['{$displayField}'], '/" . $otherControllerPath . "/view/' .\$".$singularName."['{$alias[$count]}']['{$otherModelObj->primaryKey}'])?></dd>\n";
						$count++;
					} else {
						$viewView .= "\t<dd>&nbsp;<?php echo \$".$singularName."['{$modelObj->name}']['{$field}']?></dd>\n";
					}
				}
				$viewView .= "</dl>\n";
				$viewView .= "<ul class=\"actions\">\n";
				$viewView .= "\t<li><?php echo \$html->link('Edit " . $singularHumanName . "',   '/{$controllerPath}/edit/' . \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}']) ?> </li>\n";
				$viewView .= "\t<li><?php echo \$html->link('Delete " . $singularHumanName . "', '/{$controllerPath}/delete/' . \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}'], null, 'Are you sure you want to delete: id ' . \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}'] . '?') ?> </li>\n";
				$viewView .= "\t<li><?php echo \$html->link('List " . $pluralHumanName ."',   '/{$controllerPath}/index') ?> </li>\n";
				$viewView .= "\t<li><?php echo \$html->link('New " . $singularHumanName . "',	'/{$controllerPath}/add') ?> </li>\n";
				foreach( $fieldNames as $field => $value ) {
					if( isset( $value['foreignKey'] ) ) {
						$otherModelName = $this->__modelName($value['model']);
						if($otherModelName != $currentModelName) {
							$otherControllerName = $this->__controllerName($otherModelName);
							$otherControllerPath = $this->__controllerPath($otherControllerName);
							$singularHumanName = $this->__singularHumanName($value['controller']);
							$pluralHumanName = $this->__pluralHumanName($value['controller']);
							$viewView .= "\t<li><?php echo \$html->link('List " . $pluralHumanName . "', '/" . $otherControllerPath . "/index/')?> </li>\n";
							$viewView .= "\t<li><?php echo \$html->link('New " . $singularHumanName . "', '/" . $otherControllerPath . "/add/')?> </li>\n";
						}
					}
				}
				$viewView .= "</ul>\n\n";

				$viewView .= "</div>\n";


				foreach ($modelObj->hasOne as $associationName => $relation) {
					$new = true;

					$otherModelName = $this->__modelName($relation['className']);
					$otherControllerName = $this->__controllerName($otherModelName);
					$otherControllerPath = $this->__controllerPath($otherModelName);
					$otherSingularName = $this->__singularName($associationName);
					$otherPluralHumanName = $this->__pluralHumanName($associationName);
					$otherSingularHumanName = $this->__singularHumanName($associationName);

					$viewView .= "<div class=\"related\">\n";
					$viewView .= "<h3>Related " . $otherPluralHumanName . "</h3>\n";
					$viewView .= "<?php if(!empty(\$".$singularName."['{$associationName}'])): ?>\n";
					$viewView .= "<dl>\n";
					$viewView .= "\t<?php foreach(\$".$singularName."['{$associationName}'] as \$field => \$value): ?>\n";
					$viewView .= "\t\t<dt><?php echo \$field ?></dt>\n";
					$viewView .= "\t\t<dd>&nbsp;<?php echo \$value ?></dd>\n";
					$viewView .= "\t<?php endforeach; ?>\n";
					$viewView .= "</dl>\n";
					$viewView .= "<?php endif; ?>\n";
					$viewView .= "<ul class=\"actions\">\n";
					$viewView .= "\t<li><?php echo \$html->link('Edit " . $otherSingularHumanName . "', '/" .$otherControllerPath."/edit/' . \$".$singularName."['{$associationName}']['" . $modelObj->{$otherModelName}->primaryKey . "']);?></li>\n";
					$viewView .= "\t<li><?php echo \$html->link('New " . $otherSingularHumanName . "', '/" .$otherControllerPath."/add/');?> </li>\n";
					$viewView .= "</ul>\n";
					$viewView .= "</div>\n";
				}
				$relations = array_merge($modelObj->hasMany, $modelObj->hasAndBelongsToMany);

				foreach($relations as $associationName => $relation) {
					$otherModelName = $this->__modelName($relation['className']);
					$otherControllerName = $this->__controllerName($otherModelName);
					$otherControllerPath = $this->__controllerPath($otherModelName);
					$otherSingularName = $this->__singularName($associationName);
					$otherPluralHumanName = $this->__pluralHumanName($associationName);
					$otherSingularHumanName = $this->__singularHumanName($associationName);
					$otherModelKey = Inflector::underscore($otherModelName);
					$otherModelObj =& ClassRegistry::getObject($otherModelKey);

					$viewView .= "<div class=\"related\">\n";
					$viewView .= "<h3>Related " . $otherPluralHumanName . "</h3>\n";
					$viewView .= "<?php if(!empty(\$".$singularName."['{$associationName}'])):?>\n";
					$viewView .= "<table cellpadding=\"0\" cellspacing=\"0\">\n";
					$viewView .= "<tr>\n";
					$viewView .= "<?php foreach(\$".$singularName."['{$associationName}']['0'] as \$column => \$value): ?>\n";
					$viewView .= "<th><?php echo \$column?></th>\n";
					$viewView .= "<?php endforeach; ?>\n";
					$viewView .= "<th>Actions</th>\n";
					$viewView .= "</tr>\n";
					$viewView .= "<?php foreach(\$".$singularName."['{$associationName}'] as \$".$otherSingularName."):?>\n";
					$viewView .= "<tr>\n";
					$viewView .= "\t<?php foreach(\$".$otherSingularName." as \$column => \$value):?>\n";
					$viewView .= "\t\t<td><?php echo \$value;?></td>\n";
					$viewView .= "\t<?php endforeach;?>\n";
					$viewView .= "\t<td nowrap>\n";
					$viewView .= "\t\t<?php echo \$html->link('View', '/" . $otherControllerPath . "/view/' . \$".$otherSingularName."['{$otherModelObj->primaryKey}']);?>\n";
					$viewView .= "\t\t<?php echo \$html->link('Edit', '/" . $otherControllerPath . "/edit/' . \$".$otherSingularName."['{$otherModelObj->primaryKey}']);?>\n";
					$viewView .= "\t\t<?php echo \$html->link('Delete', '/" . $otherControllerPath . "/delete/' . \$".$otherSingularName."['{$otherModelObj->primaryKey}'], null, 'Are you sure you want to delete: id ' . \$".$otherSingularName."['{$otherModelObj->primaryKey}'] . '?');?>\n";
					$viewView .= "\t</td>\n";
					$viewView .= "</tr>\n";
					$viewView .= "<?php endforeach; ?>\n";
					$viewView .= "</table>\n";
					$viewView .= "<?php endif; ?>\n\n";
					$viewView .= "<ul class=\"actions\">\n";
					$viewView .= "\t<li><?php echo \$html->link('New " . $otherSingularHumanName . "', '/" .$otherControllerPath."/add/');?> </li>\n";
					$viewView .= "</ul>\n";

					$viewView .= "</div>\n";
				}
				//-------------------------[ADD]-------------------------//
				$addView = null;
				$addView .= "<h2>New " . $singularHumanName . "</h2>\n";
				$addView .= "<form action=\"<?php echo \$html->url('/{$controllerPath}/add'); ?>\" method=\"post\">\n";
				$addView .= $this->generateFields($controllerObj->generateFieldNames(null, true));
				$addView .= $this->generateSubmitDiv('Add');
				$addView .= "</form>\n";
				$addView .= "<ul class=\"actions\">\n";
				$addView .= "<li><?php echo \$html->link('List {$pluralHumanName}', '/{$controllerPath}/index')?></li>\n";
				foreach ($modelObj->belongsTo as $associationName => $relation) {
					$otherModelName = $this->__modelName($relation['className']);
					if($otherModelName != $currentModelName) {
						$otherControllerName = $this->__controllerName($otherModelName);
						$otherControllerPath = $this->__controllerPath($otherModelName);
						$otherSingularName = $this->__singularName($associationName);
						$addView .= "<li><?php echo \$html->link('View " . $this->__pluralHumanName($associationName) . "', '/" .$otherControllerPath."/index/');?></li>\n";
					}
				}
				$addView .= "</ul>\n";


				//-------------------------[EDIT]-------------------------//
				$editView = null;
				$editView .= "<h2>Edit " . $singularHumanName . "</h2>\n";
				$editView .= "<form action=\"<?php echo \$html->url('/{$controllerPath}/edit/'.\$html->tagValue('{$modelObj->name}/{$modelObj->primaryKey}')); ?>\" method=\"post\">\n";
				$editView .= $this->generateFields($controllerObj->generateFieldNames(null, true));
				$editView .= "<?php echo \$html->hidden('{$modelObj->name}/{$modelObj->primaryKey}')?>\n";
				$editView .= $this->generateSubmitDiv('Save');
				$editView .= "</form>\n";
				$editView .= "<ul class=\"actions\">\n";
				$editView .= "<li><?php echo \$html->link('Delete','/{$controllerPath}/delete/' . \$html->tagValue('{$modelObj->name}/{$modelObj->primaryKey}'), null, 'Are you sure you want to delete: id ' . \$html->tagValue('{$modelObj->name}/{$modelObj->primaryKey}'));?>\n";
				$editView .= "<li><?php echo \$html->link('List {$pluralHumanName}', '/{$controllerPath}/index')?></li>\n";
				foreach ($modelObj->belongsTo as $associationName => $relation) {
					$otherModelName = $this->__modelName($relation['className']);
					if($otherModelName != $currentModelName) {
						$otherControllerName = $this->__controllerName($otherModelName);
						$otherControllerPath = $this->__controllerPath($otherModelName);
						$otherSingularName = $this->__singularName($associationName);
						$editView .= "<li><?php echo \$html->link('View " . $this->__pluralHumanName($associationName) . "', '/" .$otherControllerPath."/index/');?></li>\n";
					}
				}
				$editView .= "</ul>\n";

				//------------------------------------------------------------------------------------//
				if(!file_exists(VIEWS.$controllerPath)) {
					mkdir(VIEWS.$controllerPath);
				}
				if($admin) {
					$filename = VIEWS . $controllerPath . DS . $admin . 'index.thtml';
					$this->createFile($filename, $indexView);
					$filename = VIEWS . $controllerPath . DS . $admin . 'view.thtml';
					$this->createFile($filename, $viewView);
					$filename = VIEWS . $controllerPath . DS . $admin . 'add.thtml';
					$this->createFile($filename, $addView);
					$filename = VIEWS . $controllerPath . DS . $admin . 'edit.thtml';
					$this->createFile($filename, $editView);
				}

				$filename = VIEWS . $controllerPath . DS . 'index.thtml';
				$this->createFile($filename, $indexView);
				$filename = VIEWS . $controllerPath . DS . 'view.thtml';
				$this->createFile($filename, $viewView);
				$filename = VIEWS . $controllerPath . DS . 'add.thtml';
				$this->createFile($filename, $addView);
				$filename = VIEWS . $controllerPath . DS . 'edit.thtml';
				$this->createFile($filename, $editView);
				$this->hr();
				$this->stdout('');
				$this->stdout('View Scaffolding Complete.'."\n");
			}
		} else {
			$actionName = '';

			while ($actionName == '') {
				$actionName = $this->getInput('Action Name? (use camelCased function name)');

				if ($actionName == '') {
					$this->stdout('The action name you supplied was empty. Please try again.');
				}
			}
			$this->stdout('');
			$this->hr();
			$this->stdout('The following view will be created:');
			$this->hr();
			$this->stdout("Controller Name: $controllerName");
			$this->stdout("Action Name:     $actionName");
			$this->stdout("Path:            app/views/" . $controllerPath . DS . Inflector::underscore($actionName) . '.thtml');
			$this->hr();
			$looksGood = $this->getInput('Look okay?', array('y','n'), 'y');

			if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes') {
				$this->bakeView($controllerName, $actionName);
			} else {
				$this->stdout('Bake Aborted.');
			}
		}
	}
/**
 * Action to create a Controller.
 *
 */
	function doController() {
		$this->hr();
		$this->stdout('Controller Bake:');
		$this->hr();
		$uses = array();
		$helpers = array();
		$components = array();
		$wannaUseSession = 'y';
		$wannaDoScaffolding = 'y';

		$controllerName = '';
		while ($controllerName == '') {
			$controllerName = $this->getInput('Controller name? Remember that Cake controller names are plural.');

			if ($controllerName == '') {
				$this->stdout('The controller name you supplied was empty. Please try again.');
			}
		}
		$controllerPath = $this->__controllerPath($controllerName);
		$controllerName = $this->__controllerName($controllerName);

		$doItInteractive = $this->getInput("Would you like bake to build your controller interactively?\nWarning: Choosing no will overwrite {$controllerClassName} controller if it exist.", array('y','n'), 'y');

		if (strtolower($doItInteractive) == 'y' || strtolower($doItInteractive) == 'yes') {
			$this->interactive = true;
			$wannaDoUses = $this->getInput("Would you like this controller to use other models besides '" . $this->__modelName($controllerName) .  "'?", array('y','n'), 'n');

			if (strtolower($wannaDoUses) == 'y' || strtolower($wannaDoUses) == 'yes') {
				$usesList = $this->getInput("Please provide a comma separated list of the classnames of other models you'd like to use.\nExample: 'Author, Article, Book'");
				$usesListTrimmed = str_replace(' ', '', $usesList);
				$uses = explode(',', $usesListTrimmed);
			}
			$wannaDoHelpers = $this->getInput("Would you like this controller to use other helpers besides HtmlHelper and FormHelper?", array('y','n'), 'n');

			if (strtolower($wannaDoHelpers) == 'y' || strtolower($wannaDoHelpers) == 'yes') {
				$helpersList = $this->getInput("Please provide a comma separated list of the other helper names you'd like to use.\nExample: 'Ajax, Javascript, Time'");
				$helpersListTrimmed = str_replace(' ', '', $helpersList);
				$helpers = explode(',', $helpersListTrimmed);
			}
			$wannaDoComponents = $this->getInput("Would you like this controller to use any components?", array('y','n'), 'n');

			if (strtolower($wannaDoComponents) == 'y' || strtolower($wannaDoComponents) == 'yes') {
				$componentsList = $this->getInput("Please provide a comma separated list of the component names you'd like to use.\nExample: 'Acl, MyNiftyHelper'");
				$componentsListTrimmed = str_replace(' ', '', $componentsList);
				$components = explode(',', $componentsListTrimmed);
			}

			$wannaUseSession = $this->getInput("Would you like to use Sessions?", array('y','n'), 'y');

			$wannaDoScaffolding = $this->getInput("Would you like to include some basic class methods (index(), add(), view(), edit())?", array('y','n'), 'n');

		}

		if (strtolower($wannaDoScaffolding) == 'y' || strtolower($wannaDoScaffolding) == 'yes') {
			$wannaDoAdmin = $this->getInput("Would you like to create the methods for admin routing?", array('y','n'), 'n');
		}

		$admin = null;
		if ((strtolower($wannaDoAdmin) == 'y' || strtolower($wannaDoAdmin) == 'yes')) {
			require(CONFIGS.'core.php');
			if(defined('CAKE_ADMIN')) {
				$admin = CAKE_ADMIN.'_';
			} else {
				$adminRoute = '';
				$this->stdout('You need to enable CAKE_ADMIN in /app/config/core.php to use admin routing.');
				$this->stdout('What would you like the admin route to be?');
				$this->stdout('Example: www.example.com/admin/controller');
				while ($adminRoute == '') {
					$adminRoute = $this->getInput("What would you like the admin route to be?", null, 'admin');
				}
				if($this->__addAdminRoute($adminRoute) !== true){
					$this->stdout('Unable to write to /app/config/core.php.');
					$this->stdout('You need to enable CAKE_ADMIN in /app/config/core.php to use admin routing.');
					exit();
				} else {
					$admin = $adminRoute . '_';
				}
			}
		}

		if (strtolower($wannaDoScaffolding) == 'y' || strtolower($wannaDoScaffolding) == 'yes') {
				loadModels();
				$actions = $this->__bakeActions($controllerName, null, $wannaUseSession);
			if($admin) {
				$actions .= $this->__bakeActions($controllerName, $admin, $wannaUseSession);
			}
		}

		if($this->interactive === true) {
			$this->stdout('');
			$this->hr();
			$this->stdout('The following controller will be created:');
			$this->hr();
			$this->stdout("Controller Name:	$controllerName");

			if(count($uses)) {
				$this->stdout("Uses:            ", false);

				foreach($uses as $use) {
					if ($use != $uses[count($uses) - 1]) {
						$this->stdout(ucfirst($use) . ", ", false);
					} else {
						$this->stdout(ucfirst($use));
					}
				}
			}

			if(count($helpers)) {
				$this->stdout("Helpers:			", false);

				foreach($helpers as $help) {
					if ($help != $helpers[count($helpers) - 1]) {
						$this->stdout(ucfirst($help) . ", ", false);
					} else {
						$this->stdout(ucfirst($help));
					}
				}
			}

			if(count($components)) {
				$this->stdout("Components:            ", false);

				foreach($components as $comp) {
					if ($comp != $components[count($components) - 1]) {
						$this->stdout(ucfirst($comp) . ", ", false);
					} else {
						$this->stdout(ucfirst($comp));
					}
				}
			}
			$this->hr();
			$looksGood = $this->getInput('Look okay?', array('y','n'), 'y');

			if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes') {
				$this->bakeController($controllerName, $uses, $helpers, $components, $actions);

				if ($this->doUnitTest()) {
					$this->bakeUnitTest('controller', $controllerName);
				}
			} else {
				$this->stdout('Bake Aborted.');
			}
		} else {
			$this->bakeController($controllerName, $uses, $helpers, $components, $actions);
			exit();
		}
	}

	function __bakeActions($controllerName, $admin, $wannaUseSession) {
		$currentModelName = $this->__modelName($controllerName);
		$modelKey = Inflector::underscore($currentModelName);
		$singularName = $this->__singularName($currentModelName);
		$singularHumanName = $this->__singularHumanName($currentModelName);
		$pluralHumanName = $this->__pluralHumanName($controllerName);


		if(!class_exists($currentModelName)) {
			$this->stdout('You must have a model for this class to build scaffold methods. Please try again.');
			exit;
		}
		$modelObj = & new $currentModelName();
		$actions .= "\n";
		$actions .= "\tfunction {$admin}index() {\n";
		$actions .= "\t\t\$this->{$currentModelName}->recursive = 0;\n";
		$actions .= "\t\t\$this->set('{$controllerPath}', \$this->{$currentModelName}->findAll());\n";
		$actions .= "\t}\n";
		$actions .= "\n";
		$actions .= "\tfunction {$admin}view(\$id = null) {\n";
		$actions .= "\t\tif(!\$id) {\n";
		$actions .= "\t\t\treturn false;\n";
		$actions .= "\t\t}\n";
		$actions .= "\t\t\$this->set('".$singularName."', \$this->{$currentModelName}->read(null, \$id));\n";
		$actions .= "\t}\n";
		$actions .= "\n";

		$actions .= "\tfunction {$admin}add() {\n";
		$actions .= "\t\tif(empty(\$this->data)) {\n";

		foreach($modelObj->hasAndBelongsToMany as $associationName => $relation) {
			if(!empty($relation['className'])) {
				$otherModelName = $this->__modelName($relation['className']);
				$otherSingularName = $this->__singularName($associationName);
				$otherPluralName = $this->__pluralName($associationName);

				$actions .= "\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->{$otherModelName}->generateList());\n";
				$actions .= "\t\t\t\$this->set('selected_{$otherPluralName}', null);\n";
			}
		}
		foreach($modelObj->belongsTo as $associationName => $relation) {
			if(!empty($relation['className'])) {
				$otherModelName = $this->__modelName($relation['className']);
				$otherSingularName = $this->__singularName($associationName);
				$otherPluralName = $this->__pluralName($associationName);
				if($currentModelName != $otherModelName) {
					$actions .= "\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->{$otherModelName}->generateList());\n";
				} else {
					$actions .= "\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->generateList());\n";
				}
			}
		}
		$actions .= "\t\t\t\$this->render();\n";
		$actions .= "\t\t} else {\n";
		$actions .= "\t\t\t\$this->cleanUpFields();\n";
		$actions .= "\t\t\tif(\$this->{$currentModelName}->save(\$this->data)) {\n";
		if (strtolower($wannaUseSession) == 'y' || strtolower($wannaUseSession) == 'yes') {
		$actions .= "\t\t\t\t\$this->Session->setFlash('The ".Inflector::humanize($currentModelName)." has been saved');\n";
		$actions .= "\t\t\t\t\$this->redirect('/{$controllerPath}/index');\n";
		} else {
		$actions .= "\t\t\t\t\$this->flash('{$currentModelName} saved.', '/{$controllerPath}/index');\n";
		}
		$actions .= "\t\t\t} else {\n";
		if (strtolower($wannaUseSession) == 'y' || strtolower($wannaUseSession) == 'yes') {
		$actions .= "\t\t\t\t\$this->Session->setFlash('Please correct errors below.');\n";
		}

		foreach($modelObj->hasAndBelongsToMany as $associationName => $relation) {
			if(!empty($relation['className'])) {
				$otherModelName = $this->__modelName($relation['className']);
				$otherSingularName = $this->__singularName($associationName);
				$otherPluralName = $this->__pluralName($associationName);

				$actions .= "\t\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->{$otherModelName}->generateList());\n";
				$actions .= "\t\t\t\tif(empty(\$this->data['{$associationName}']['{$associationName}'])) { \$this->data['{$associationName}']['{$associationName}'] = null; }\n";
				$actions .= "\t\t\t\t\$this->set('selected_{$otherPluralName}', \$this->data['{$associationName}']['{$associationName}']);\n";
			}
		}
		foreach($modelObj->belongsTo as $associationName => $relation) {
			if(!empty($relation['className'])) {
				$otherModelName = $this->__modelName($relation['className']);
				$otherSingularName = $this->__singularName($associationName);
				$otherPluralName = $this->__pluralName($associationName);
				if($currentModelName != $otherModelName) {
					$actions .= "\t\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->{$otherModelName}->generateList());\n";
				} else {
					$actions .= "\t\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->generateList());\n";
				}
			}
		}
		$actions .= "\t\t\t}\n";
		$actions .= "\t\t}\n";
		$actions .= "\t}\n";
		$actions .= "\n";
		$actions .= "\tfunction {$admin}edit(\$id) {\n";
		$actions .= "\t\tif(empty(\$this->data)) {\n";
		$actions .= "\t\t\t\$this->data = \$this->{$currentModelName}->read(null, \$id);\n";

		foreach($modelObj->hasAndBelongsToMany as $associationName => $relation) {
			if(!empty($relation['className'])) {
				$otherModelName = $this->__modelName($relation['className']);
				$otherSingularName = $this->__singularName($associationName);
				$otherPluralName = $this->__pluralName($associationName);
				$otherModelKey = Inflector::underscore($otherModelName);
				$otherModelObj =& ClassRegistry::getObject($otherModelKey);
				$actions .= "\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->{$otherModelName}->generateList());\n";
				$actions .= "\t\t\tif(empty(\$this->data['{$associationName}']['{$associationName}'])) { \$this->data['{$associationName}']['{$associationName}'] = null; }\n";
				$actions .= "\t\t\t\$this->set('selected_{$otherPluralName}', \$this->__selectedArray(\$this->data['{$associationName}']['{$associationName}']));\n";
			}
		}
		foreach($modelObj->belongsTo as $associationName => $relation) {
			if(!empty($relation['className'])) {
				$otherModelName = $this->__modelName($relation['className']);
				$otherSingularName = $this->__singularName($associationName);
				$otherPluralName = $this->__pluralName($associationName);
				if($currentModelName != $otherModelName) {
					$actions .= "\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->{$otherModelName}->generateList());\n";
				} else {
					$actions .= "\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->generateList());\n";
				}
			}
		}
		$actions .= "\t\t} else {\n";
		$actions .= "\t\t\t\$this->cleanUpFields();\n";
		$actions .= "\t\t\tif(\$this->{$currentModelName}->save(\$this->data)) {\n";
		if (strtolower($wannaUseSession) == 'y' || strtolower($wannaUseSession) == 'yes') {
		$actions .= "\t\t\t\t\$this->Session->setFlash('The ".Inflector::humanize($currentModelName)." has been saved');\n";
		$actions .= "\t\t\t\t\$this->redirect('/{$controllerName}/index');\n";
		} else {
		$actions .= "\t\t\t\t\$this->flash('{$currentModelName} saved.', '/{$controllerName}/index');\n";
		}
		$actions .= "\t\t\t} else {\n";
		if (strtolower($wannaUseSession) == 'y' || strtolower($wannaUseSession) == 'yes') {
		$actions .= "\t\t\t\t\$this->Session->setFlash('Please correct errors below.');\n";
		}

		foreach($modelObj->hasAndBelongsToMany as $associationName => $relation) {
			if(!empty($relation['className'])) {
				$otherModelName = $this->__modelName($relation['className']);
				$otherSingularName = $this->__singularName($associationName);
				$otherPluralName = $this->__pluralName($associationName);

				$actions .= "\t\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->{$otherModelName}->generateList());\n";
				$actions .= "\t\t\t\tif(empty(\$this->data['{$associationName}']['{$associationName}'])) { \$this->data['{$associationName}']['{$associationName}'] = null; }\n";
				$actions .= "\t\t\t\t\$this->set('selected_{$otherPluralName}', \$this->data['{$associationName}']['{$associationName}']);\n";
			}
		}
		foreach($modelObj->belongsTo as $associationName => $relation) {
			if(!empty($relation['className'])) {
				$otherModelName = $this->__modelName($relation['className']);
				$otherSingularName = $this->__singularName($associationName);
				$otherPluralName = $this->__pluralName($associationName);
				if($currentModelName != $otherModelName) {
					$actions .= "\t\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->{$otherModelName}->generateList());\n";
				} else {
					$actions .= "\t\t\t\t\$this->set('{$otherPluralName}', \$this->{$currentModelName}->generateList());\n";
				}
			}
		}
		$actions .= "\t\t\t}\n";
		$actions .= "\t\t}\n";
		$actions .= "\t}\n";
		$actions .= "\n";
		$actions .= "\tfunction {$admin}delete(\$id = null) {\n";
		$actions .= "\t\tif(!\$id) {\n";
		$actions .= "\t\t\treturn false;\n";
		$actions .= "\t\t}\n";
		$actions .= "\t\tif(\$this->{$currentModelName}->del(\$id)) {\n";
		if (strtolower($wannaUseSession) == 'y' || strtolower($wannaUseSession) == 'yes') {
			$actions .= "\t\t\t\$this->Session->setFlash('The ".$this->__singularHumanName($currentModelName)." deleted: id '.\$id.'');\n";
			$actions .= "\t\t\t\$this->redirect('/{$controllerName}/index');\n";
		} else {
			$actions .= "\t\t\t\$this->flash('{$currentModelName} deleted: id '.\$id.'.', '/{$controllerPath}/index');\n";
		}
		$actions .= "\t\t}\n";
		$actions .= "\t}\n";
		$actions .= "\n";
		return $actions;
	}
/**
 * Action to create a Unit Test.
 *
 * @return Success
 */
	function doUnitTest() {
		if (is_dir('vendors'.DS.'simpletest') || is_dir(APP_PATH.'vendors'.DS.'simpletest')) {
			return true;
		}
		$unitTest = $this->getInput('Cake test suite not installed.  Do you want to bake unit test files anyway?', array('y','n'), 'y');
		$result = strtolower($unitTest) == 'y' || strtolower($unitTest) == 'yes';

		if ($result) {
			$this->stdout("\nYou can download the Cake test suite from http://cakeforge.org/frs/?group_id=62", true);
		}
		return $result;
	}
/**
 * Creates a database configuration file for Bake.
 *
 * @param string $host
 * @param string $login
 * @param string $password
 * @param string $database
 */
	function bakeDbConfig( $driver, $connect, $host, $login, $password, $database, $prefix) {
		$out = "<?php\n";
		$out .= "class DATABASE_CONFIG\n";
		$out .= "{\n";
		$out .= "\tvar \$default = array(\n";
		$out .= "\t\t'driver' => '{$driver}',\n";
		$out .= "\t\t'connect' => '{$connect}',\n";
		$out .= "\t\t'host' => '{$host}',\n";
		$out .= "\t\t'login' => '{$login}',\n";
		$out .= "\t\t'password' => '{$password}',\n";
		$out .= "\t\t'database' => '{$database}', \n";
		$out .= "\t\t'prefix' => '{$prefix}' \n";
		$out .= "\t);\n";
		$out .= "}\n";
		$out .= "?>";
		$filename = CONFIGS.'database.php';
		$this->createFile($filename, $out);
	}
/**
 * Assembles and writes a Model file.
 *
 * @param string $modelClassName
 * @param object $dbConnection
 * @param string $modelTableName
 * @param array $validate
 * @param array $modelAssociations
 */
	function bakeModel($modelClassName, $dbConnection, $modelTableName, $validate, $modelAssociations) {
		$out = "<?php\n";
		$out .= "class $modelClassName extends AppModel\n";
		$out .= "{\n";
		$out .= "\tvar \$name = '$modelClassName';\n";

		if ($dbConnection != 'default') {
			$out .= "\tvar \$useDbConfig = '$dbConnection';\n";
		}

		if ($modelTableName != null) {
			$out .= "\tvar \$useTable = '$modelTableName';\n";
		}

		if (count($validate)) {
			$out .= "\tvar \$validate = array(\n";
			$keys = array_keys($validate);
			for($i = 0; $i < count($validate); $i++) {
				$out .= "\t\t'" . $keys[$i] . "' => " . $validate[$keys[$i]] . ",\n";
			}
			$out .= "\t);\n";
		}
		$out .= "\n";

		if(count($modelAssociations['belongsTo']) || count($modelAssociations['hasOne']) || count($modelAssociations['hasMany']) || count($modelAssociations['hasAndBelongsToMany'])) {
			$out.= "\t//The Associations below have been created with all possible keys, those that are not needed can be removed\n";
			if(count($modelAssociations['belongsTo'])) {
				$out .= "\tvar \$belongsTo = array(\n";

				for($i = 0; $i < count($modelAssociations['belongsTo']); $i++) {
					$out .= "\t\t\t'{$modelAssociations['belongsTo'][$i]}' =>\n";
					$out .= "\t\t\t array('className' => '{$modelAssociations['belongsTo'][$i]}',\n";
					$out .= "\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t'foreignKey' => '',\n";
					$out .= "\t\t\t\t\t'counterCache' => ''),\n\n";
				}
				$out .= "\t);\n\n";
			}

			if(count($modelAssociations['hasOne'])) {
				$out .= "\tvar \$hasOne = array(\n";

				for($i = 0; $i < count($modelAssociations['hasOne']); $i++) {
					$out .= "\t\t\t'{$modelAssociations['hasOne'][$i]}' =>\n";
					$out .= "\t\t\t array('className' => '{$modelAssociations['hasOne'][$i]}',\n";
					$out .= "\t\t\t\t\t'foreignKey' => '',\n";
					$out .= "\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t'dependent' => ''),\n\n";
				}
				$out .= "\t);\n\n";
			}

			if(count($modelAssociations['hasMany'])) {
				$out .= "\tvar \$hasMany = array(\n";

				for($i = 0; $i < count($modelAssociations['hasMany']); $i++) {
					$out .= "\t\t\t'{$modelAssociations['hasMany'][$i]}' =>\n";
					$out .= "\t\t\t array('className' => '{$modelAssociations['hasMany'][$i]}',\n";
					$out .= "\t\t\t\t\t'foreignKey' => '',\n";
					$out .= "\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t'limit' => '',\n";
					$out .= "\t\t\t\t\t'offset' => '',\n";
					$out .= "\t\t\t\t\t'dependent' => '',\n";
					$out .= "\t\t\t\t\t'exclusive' => '',\n";
					$out .= "\t\t\t\t\t'finderSql' => '',\n";
					$out .= "\t\t\t\t\t'counterSql' => ''),\n\n";
				}
				$out .= "\t);\n\n";
			}

			if(count($modelAssociations['hasAndBelongsToMany'])) {
				$out .= "\tvar \$hasAndBelongsToMany = array(\n";

				for($i = 0; $i < count($modelAssociations['hasAndBelongsToMany']); $i++) {
					$out .= "\t\t\t'{$modelAssociations['hasAndBelongsToMany'][$i]}' =>\n";
					$out .= "\t\t\t array('className' => '{$modelAssociations['hasAndBelongsToMany'][$i]}',\n";
					$out .= "\t\t\t\t\t'joinTable' => '',\n";
					$out .= "\t\t\t\t\t'foreignKey' => '',\n";
					$out .= "\t\t\t\t\t'associationForeignKey' => '',\n";
					$out .= "\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t'limit' => '',\n";
					$out .= "\t\t\t\t\t'offset' => '',\n";
					$out .= "\t\t\t\t\t'uniq' => '',\n";
					$out .= "\t\t\t\t\t'finderQuery' => '',\n";
					$out .= "\t\t\t\t\t'deleteQuery' => '',\n";
					$out .= "\t\t\t\t\t'insertQuery' => ''),\n\n";
				}
				$out .= "\t);\n\n";
			}
		}
		$out .= "}\n";
		$out .= "?>";
		$filename = MODELS.Inflector::underscore($modelClassName) . '.php';
		$this->createFile($filename, $out);
	}
/**
 * Assembles and writes a View file.
 *
 * @param string $controllerName
 * @param string $actionName
 * @param string $content
 */
	function bakeView($controllerName, $actionName, $content = '') {
		$out = "<h2>$actionName</h2>\n";
		$out .= $content;
		if(!file_exists(VIEWS.$this->__controllerPath($controllerName))) {
			mkdir(VIEWS.$this->__controllerPath($controllerName));
		}
		$filename = VIEWS . $this->__controllerPath($controllerName) . DS . Inflector::underscore($actionName) . '.thtml';
		$this->createFile($filename, $out);
	}
/**
 * Assembles and writes a Controller file.
 *
 * @param string $controllerName
 * @param array $uses
 * @param array $helpers
 * @param array $components
 * @param string $actions
 */
	function bakeController($controllerName, $uses, $helpers, $components, $actions = '') {
		$out = "<?php\n";
		$out .= "class $controllerName" . "Controller extends AppController\n";
		$out .= "{\n";
		$out .= "\t//var \$scaffold;\n";
		$out .= "\tvar \$name = '$controllerName';\n";

		if (count($uses)) {
			$out .= "\tvar \$uses = array('" . $this->__modelName($controllerName) . "', ";

			foreach($uses as $use) {
				if ($use != $uses[count($uses) - 1]) {
					$out .= "'" . $this->__modelName($use) . "', ";
				} else {
					$out .= "'" . $this->__modelName($use) . "'";
				}
			}
			$out .= ");\n";
		}

			$out .= "\tvar \$helpers = array('Html', 'Form' ";
			if (count($helpers)) {
				foreach($helpers as $help) {
					if ($help != $helpers[count($helpers) - 1]) {
						$out .= ", '" . Inflector::camelize($help) . "'";
					} else {
						$out .= ", '" . Inflector::camelize($help) . "'";
					}
				}
			}
			$out .= ");\n";

		if (count($components)) {
			$out .= "\tvar \$components = array(";

			foreach($components as $comp) {
				if ($comp != $components[count($components) - 1]) {
					$out .= "'" . Inflector::camelize($comp) . "', ";
				} else {
					$out .= "'" . Inflector::camelize($comp) . "'";
				}
			}
			$out .= ");\n";
		}

		$out .= $actions;
		$out .= "}\n";
		$out .= "?>";
		$filename = CONTROLLERS . $this->__controllerPath($controllerName) . '_controller.php';
		$this->createFile($filename, $out);
	}
/**
 * Assembles and writes a unit test file.
 *
 * @param string $type One of "model", and "controller".
 * @param string $className
 */
	function bakeUnitTest($type, $className) {
		$out = '<?php '."\n\n";
		$error = false;
		switch ($type) {
			case 'model':
				$out .= "class {$className}TestCase extends UnitTestCase\n{\n";
				$out .= "\tvar \$object = null;\n\n";
				$out .= "\tfunction setUp()\n\t{\n\t\t\$this->object = new {$className}();\n";
				$out .= "\t}\n\n\tfunction tearDown()\n\t{\n\t\tunset(\$this->object);\n\t}\n";
				$out .= "\n\t/*\n\tfunction testMe()\n\t{\n";
				$out .= "\t\t\$result = \$this->object->doSomething();\n";
				$out .= "\t\t\$expected = 1;\n";
				$out .= "\t\t\$this->assertEquals(\$result, \$expected);\n\t}\n\t*/\n}";
				$path = MODEL_TESTS;
				$filename = $this->__singularName($className).'.test.php';
			break;
			case 'controller':
				$out .= "class {$className}ControllerTestCase extends UnitTestCase\n{\n";
				$out .= "\tvar \$object = null;\n\n";
				$out .= "\tfunction setUp()\n\t{\n\t\t\$this->object = new {$className}();\n";
				$out .= "\t}\n\n\tfunction tearDown()\n\t{\n\t\tunset(\$this->object);\n\t}\n";
				$out .= "\n\t/*\n\tfunction testMe()\n\t{\n";
				$out .= "\t\t\$result = \$this->object->doSomething();\n";
				$out .= "\t\t\$expected = 1;\n";
				$out .= "\t\t\$this->assertEquals(\$result, \$expected);\n\t}\n\t*/\n}";
				$path = CONTROLLER_TESTS;
				$filename = $this->__pluralName($className.'Controller').'.test.php';
			break;
			default:
				$error = true;
			break;
		}
		$out .= "\n?>";

		if (!$error) {
			$this->stdout("Baking unit test for $className...");
			$path = explode(DS, $path);
			foreach($path as $i => $val) {
				if ($val == '' || $val == '../') {
					unset($path[$i]);
				}
			}
			$path = implode(DS, $path);
			echo $path;
			$unixPath = DS;
			if (strpos(PHP_OS, 'WIN') === 0){
				$unixPath = null;
			}
			if (!is_dir($unixPath.$path)) {
				$create = $this->getInput("Unit test directory does not exist.  Create it?", array('y','n'), 'y');
				if (low($create) == 'y' || low($create) == 'yes') {
					$build = array();

					foreach(explode(DS, $path) as $i => $dir) {
						$build[] = $dir;
						if (!is_dir($unixPath.implode(DS, $build))) {
							mkdir($unixPath.implode(DS, $build));
						}
					}
				}
			}
			$this->createFile($unixPath.$path.DS.$filename, $out);
		}
	}
/**
 * Prompts the user for input, and returns it.
 *
 * @param string $prompt Prompt text.
 * @param mixed $options Array or string of options.
 * @param string $default Default input value.
 * @return Either the default value, or the user-provided input.
 */
	function getInput($prompt, $options = null, $default = null) {
		if (!is_array($options)) {
			$print_options = '';
		} else {
			$print_options = '(' . implode('/', $options) . ')';
		}

		if($default == null) {
			$this->stdout('');
			$this->stdout($prompt . " $print_options \n" . '> ', false);
		} else {
			$this->stdout('');
			$this->stdout($prompt . " $print_options \n" . "[$default] > ", false);
		}
		$result = trim(fgets($this->stdin));

		if($default != null && empty($result)) {
			return $default;
		} else {
			return $result;
		}
	}
/**
 * Outputs to the stdout filehandle.
 *
 * @param string $string String to output.
 * @param boolean $newline If true, the outputs gets an added newline.
 */
	function stdout($string, $newline = true) {
		if ($newline) {
			fwrite($this->stdout, $string . "\n");
		} else {
			fwrite($this->stdout, $string);
		}
	}
/**
 * Outputs to the stderr filehandle.
 *
 * @param string $string Error text to output.
 */
	function stderr($string) {
		fwrite($this->stderr, $string);
	}
/**
 * Outputs a series of minus characters to the standard output, acts as a visual separator.
 *
 */
	function hr() {
		$this->stdout('---------------------------------------------------------------');
	}
/**
 * Creates a file at given path.
 *
 * @param string $path		Where to put the file.
 * @param string $contents Content to put in the file.
 * @return Success
 */
	function createFile ($path, $contents) {
		//$shortPath = str_replace(ROOT, null, $path);
		//$shortPath = str_replace('../', '', $shortPath);
		//$shortPath = str_replace('//', '/', $shortPath);
		$shortPath = $path;
		echo "\nCreating file $shortPath\n";
		$path = str_replace('//', '/', $path);
		if (is_file($path) && $this->interactive === true) {
			fwrite($this->stdout, "File {$shortPath} exists, overwrite? (y/n/q):");
			$key = trim(fgets($this->stdin));

			if ($key=='q') {
				fwrite($this->stdout, "Quitting.\n");
				exit;
			} elseif ($key == 'a') {
				$this->dont_ask = true;
			} elseif ($key == 'y') {
			} else {
				fwrite($this->stdout, "Skip   {$shortPath}\n");
				return false;
			}
		}

		if ($f = fopen($path, 'w')) {
			fwrite($f, $contents);
			fclose($f);
			fwrite($this->stdout, "Wrote   {$shortPath}\n");
			return true;
		} else {
			fwrite($this->stderr, "Error! Couldn't open {$shortPath} for writing.\n");
			return false;
		}
	}
/**
 * Takes an array of database fields, and generates an HTML form for a View.
 * This is an extraction from the Scaffold functionality.
 *
 * @param array $fields
 * @param boolean $readOnly
 * @return Generated HTML and PHP.
 */
	function generateFields( $fields, $readOnly = false ) {
		$strFormFields = '';
		foreach( $fields as $field) {

			if(isset( $field['type'])) {
				if(!isset($field['required'])) {
					$field['required'] = false;
				}

				if(!isset( $field['errorMsg'])) {
					$field['errorMsg'] = null;
				}

				if(!isset( $field['htmlOptions'])) {
					$field['htmlOptions'] = array();
				}

				if( $readOnly ) {
					$field['htmlOptions']['READONLY'] = "readonly";
				}

				switch( $field['type'] ) {
					case "input" :
						if(!isset( $field['size'])) {
							$field['size'] = 60;
						}
						$strFormFields = $strFormFields.$this->generateInputDiv( $field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], $field['size'], $field['htmlOptions'] );
					break;
					case "checkbox" :
						$strFormFields = $strFormFields.$this->generateCheckboxDiv( $field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], $field['htmlOptions'] );
					break;
					case "select";
					case "selectMultiple";
						if( "selectMultiple" == $field['type'] ) {
							$field['selectAttr']['multiple'] = 'multiple';
							$field['selectAttr']['class'] = 'selectMultiple';
						}
						if(!isset( $field['selected'])) {
							$field['selected'] = null;
						}
						if(!isset( $field['selectAttr'])) {
							$field['selectAttr'] = null;
						}
						if(!isset( $field['optionsAttr'])) {
							$field['optionsAttr'] = null;
						}
						if($readOnly) {
							$field['selectAttr']['DISABLED'] = true;
						}
						if(!isset( $field['options'])) {
							$field['options'] = null;
						}
						$this->__modelAlias = null;
						if(isset($field['foreignKey'])) {
							$modelKey = Inflector::underscore($this->__modelClass);
							$modelObj =& ClassRegistry::getObject($modelKey);
							foreach($modelObj->belongsTo as $associationName =>$value) {
								if($field['model'] == $value['className']) {
									$this->__modelAlias = $this->__modelName($associationName);
									break;
								}
							}
						}
						$strFormFields = $strFormFields.$this->generateSelectDiv( $field['tagName'], $field['prompt'], $field['options'], $field['selected'], $field['selectAttr'], $field['optionsAttr'], $field['required'], $field['errorMsg'] );
					break;
					case "area";
						if(!isset( $field['rows'])) {
							$field['rows'] = 10;
						}
						if(!isset( $field['cols'])) {
							$field['cols'] = 60;
						}
						$strFormFields = $strFormFields.$this->generateAreaDiv( $field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], $field['cols'], $field['rows'], $field['htmlOptions'] );
					break;
					case "fieldset";
						$strFieldsetFields = $this->generateFields( $field['fields'] );
						$strFieldSet = sprintf( '
						<fieldset><legend>%s</legend><div class="notes"><h4>%s</h4><p class="last">%s</p></div>%s</fieldset>',
						$field['legend'], $field['noteHeading'], $field['note'], $strFieldsetFields );
						$strFormFields = $strFormFields.$strFieldSet;
					break;
					case "hidden";
						$currentModelName = $field['model'];
						//$strFormFields = $strFormFields . $this->Html->hiddenTag( $field['tagName']);
					break;
					case "date":
						if (!isset($field['selected'])) {
							$field['selected'] = null;
						}
						$strFormFields = $strFormFields . $this->generateDate($field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], null, $field['htmlOptions'], $field['selected']);
					break;
					case "datetime":
						if (!isset($field['selected'])) {
							$field['selected'] = null;
						}
						$strFormFields = $strFormFields . $this->generateDateTime($field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], null, $field['htmlOptions'], $field['selected']);
					break;
					case "time":
						if (!isset($field['selected'])) {
							$field['selected'] = null;
						}
						$strFormFields = $strFormFields . $this->generateTime($field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], null, $field['htmlOptions'], $field['selected']);
					break;
					default:
					break;
				}
			}
		}
		return $strFormFields;
	}
/**
 * Generates PHP code for a View file that makes a textarea.
 *
 * @param string $tagName
 * @param string $prompt
 * @param boolean $required
 * @param string $errorMsg
 * @param integer $cols
 * @param integer $rows
 * @param array $htmlOptions
 * @return Generated HTML and PHP.
 */
	function generateAreaDiv($tagName, $prompt, $required=false, $errorMsg=null, $cols=60, $rows=10,  $htmlOptions=null ) {
		$htmlAttributes = $htmlOptions;
		$htmlAttributes['cols'] = $cols;
		$htmlAttributes['rows'] = $rows;
		$str = "\t<?php echo \$html->textarea('{$tagName}', " . $this->attributesToArray($htmlAttributes) . ");?>\n";
		$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Please enter the {$prompt}.');?>\n";
		$strLabel = "\n\t<?php echo \$form->label( '{$tagName}', '{$prompt}' );?>\n";
		$divClass = "optional";

		if( $required ) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );
		return $this->divTag( $divClass, $divTagInside );
	}
/**
 * Generates PHP code for a View file that makes a checkbox, wrapped in a DIV.
 *
 * @param string $tagName
 * @param string $prompt
 * @param boolean $required
 * @param string $errorMsg
 * @param array $htmlOptions
 * @return Generated HTML and PHP.
 */
	function generateCheckboxDiv($tagName, $prompt, $required=false, $errorMsg=null, $htmlOptions=null ) {
		$htmlOptions['class'] = "inputCheckbox";

		$strLabel = "\n\t<?php echo \$html->checkbox('{$tagName}', null, " . $this->attributesToArray($htmlAttributes) . ");?>\n";
		$strLabel .= "\t<?php echo \$form->label('{$tagName}', '{$prompt}');?>\n";
		$str = "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Please check the {$prompt}.');?>\n";
		$divClass = "optional";

		if($required) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str);
		return $this->divTag( $divClass, $divTagInside );
	}
/**
 * Generates PHP code for a View file that makes a date-picker, wrapped in a DIV.
 *
 * @param string $tagName
 * @param string $prompt
 * @param boolean $required
 * @param string $errorMsg
 * @param integer $size
 * @param array $htmlOptions
 * @param string $selected
 * @return Generated HTML and PHP.
 */
	function generateDate($tagName, $prompt, $required=false, $errorMsg=null, $size=20, $htmlOptions=null, $selected=null ) {
		$str = "\t<?php echo \$html->dateTimeOptionTag('{$tagName}', 'MDY' , 'NONE', \$html->tagValue('{$tagName}'), " . $this->attributesToArray($htmlOptions) . ");?>\n";
		$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Please select the {$prompt}.');?>\n";
		$strLabel = "\n\t<?php echo \$form->label('{$tagName}', '{$prompt}');?>\n";
		$divClass = "optional";

		if($required) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );
		return $this->divTag( $divClass, $divTagInside );
	}
/**
 * Generates PHP code for a View file that makes a time-picker, wrapped in a DIV.
 *
 * @param string $tagName
 * @param string $prompt
 * @param boolean $required
 * @param string $errorMsg
 * @param integer $size
 * @param array $htmlOptions
 * @param string $selected
 * @return Generated HTML and PHP.
 */
	function generateTime($tagName, $prompt, $required = false, $errorMsg = null, $size = 20, $htmlOptions = null, $selected = null) {
		$str = "\n\t\<?php echo \$html->dateTimeOptionTag('{$tagName}', 'NONE', '24', \$html->tagValue('{$tagName}'), " . $this->attributesToArray($htmlOptions) . ");?>\n";
		$strLabel = "\n\t<?php echo \$form->label('{$tagName}', '{$prompt}');?>\n";
		$divClass = "optional";
		if ($required) {
			$divClass = "required";
		}
		$strError = "";
		$divTagInside = sprintf("%s %s %s", $strError, $strLabel, $str);
		return $this->divTag($divClass, $divTagInside);
	}
/**
 * EGenerates PHP code for a View file that makes a datetime-picker, wrapped in a DIV.
 *
 * @param string $tagName
 * @param string $prompt
 * @param boolean $required
 * @param string $errorMsg
 * @param integer $size
 * @param array $htmlOptions
 * @param string $selected
 * @return Generated HTML and PHP.
 */
	function generateDateTime($tagName, $prompt, $required=false, $errorMsg=null, $size=20, $htmlOptions=null, $selected = null ) {
		$str = "\t<?php echo \$html->dateTimeOptionTag('{$tagName}', 'MDY' , '12', \$html->tagValue('{$tagName}'), " . $this->attributesToArray($htmlOptions) . ");?>\n";
		$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Please select the {$prompt}.');?>\n";
		$strLabel = "\n\t<?php echo \$form->label('{$tagName}', '{$prompt}');?>\n";
		$divClass = "optional";

		if($required) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );
		return $this->divTag( $divClass, $divTagInside );
	}
/**
 * Generates PHP code for a View file that makes an INPUT field, wrapped in a DIV.
 *
 * @param string $tagName
 * @param string $prompt
 * @param boolean $required
 * @param string $errorMsg
 * @param integer $size
 * @param array $htmlOptions
 * @return Generated HTML and PHP.
 */
	function generateInputDiv($tagName, $prompt, $required=false, $errorMsg=null, $size=20, $htmlOptions=null ) {
		$htmlAttributes = $htmlOptions;
		$htmlAttributes['size'] = $size;
		$str = "\t<?php echo \$html->input('{$tagName}', " . $this->attributesToArray($htmlAttributes) . ");?>\n";
		$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Please enter the {$prompt}.');?>\n";
		 $strLabel = "\n\t<?php echo \$form->label('{$tagName}', '{$prompt}');?>\n";
		$divClass = "optional";

		if($required) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );
		return $this->divTag( $divClass, $divTagInside );
	}

/**
 * Generates PHP code for a View file that makes a SELECT box, wrapped in a DIV.
 *
 * @param string $tagName
 * @param string $prompt
 * @param array $options
 * @param string $selected
 * @param array $selectAttr
 * @param array $optionAttr
 * @param boolean $required
 * @param string $errorMsg
 * @return Generated HTML and PHP.
 */
	function generateSelectDiv($tagName, $prompt, $options, $selected=null, $selectAttr=null, $optionAttr=null, $required=false,  $errorMsg=null) {

		if($this->__modelAlias) {
			$pluralName = $this->__pluralName($this->__modelAlias);
		} else {
			$tagArray = explode('/', $tagName);
			$pluralName = $this->__pluralName($this->__modelNameFromKey($tagArray[1]));
		}

		if($selectAttr['multiple'] != 'multiple') {
			$str = "\t<?php echo \$html->selectTag('{$tagName}', " . "\${$pluralName}, \$html->tagValue('{$tagName}'), " . $this->attributesToArray($selectAttr) . ");?>\n";
			$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Please select the {$prompt}.') ?>\n";
		} else {
			$str = "\t<?php echo \$html->selectTag('{$tagName}', \${$pluralName}, \$selected_{$pluralName}, array('multiple' => 'multiple', 'class' => 'selectMultiple'));?>\n";
			$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Please select the {$prompt}.');?>\n";
		}
		$strLabel = "\n\t<?php echo \$form->label('{$tagName}', '{$prompt}');?>\n";
		$divClass = "optional";

		if($required) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );
		return $this->divTag( $divClass, $divTagInside );
	}
/**
 * Generates PHP code for a View file that makes a submit button, wrapped in a DIV.
 *
 * @param string $displayText
 * @param array $htmlOptions
 * @return Generated HTML.
 */
	function generateSubmitDiv($displayText, $htmlOptions = null) {
		$str = "\n\t<?php echo \$html->submit('{$displayText}');?>\n";
		$divTagInside = sprintf( "%s", $str );
		return $this->divTag( 'submit', $divTagInside);
	}
/**
 * Returns the text wrapped in an HTML P tag, followed by a newline.
 *
 * @param string $class
 * @param string $text
 * @return Generated HTML.
 */
	function pTag($class, $text) {
		return sprintf( TAG_P_CLASS, $class, $text ) . "\n";
	}
/**
 * Returns the text wrapped in an HTML DIV, followed by a newline.
 *
 * @param string $class
 * @param string $text
 * @return Generated HTML.
 */
	function divTag($class, $text) {
		return sprintf( TAG_DIV, $class, $text ) . "\n";
	}
/**
 * Parses the HTML attributes array, which is a common data structure in View files.
 * Returns PHP code for initializing this array in a View file.
 *
 * @param array $htmlAttributes
 * @return Generated PHP code.
 */
	function attributesToArray($htmlAttributes) {
		if (is_array($htmlAttributes)) {
			$keys = array_keys($htmlAttributes);
			$vals = array_values($htmlAttributes);
			$out = "array(";

			for($i = 0; $i < count($htmlAttributes); $i++) {
				//don't put vars in quotes
				if(substr($vals[$i], 0, 1) != '$') {
					$out .= "'{$keys[$i]}' => '{$vals[$i]}', ";
				} else {
					$out .= "'{$keys[$i]}' => {$vals[$i]}, ";
				}
			}
			//Chop off last comma
			if(substr($out, -2, 1) == ',') {
				$out = substr($out, 0, strlen($out) - 2);
			}
			$out .= ")";
			return $out;
		} else {
			return 'array()';
		}
	}
/**
 * Outputs usage text on the standard output.
 *
 */
	function help() {
		$this->stdout('CakePHP Bake:');
		$this->hr();
		$this->stdout('The Bake script generates controllers, views and models for your application.');
		$this->stdout('If run with no command line arguments, Bake guides the user through the class');
		$this->stdout('creation process. You can customize the generation process by telling Bake');
		$this->stdout('where different parts of your application are using command line arguments.');
		$this->stdout('');
		$this->hr('');
		$this->stdout('usage: php bake.php [command] [path...]');
		$this->stdout('');
		$this->stdout('commands:');
		$this->stdout('   -app [path...] Absolute path to Cake\'s app Folder.');
		$this->stdout('   -core [path...] Absolute path to Cake\'s cake Folder.');
		$this->stdout('   -help Shows this help message.');
		$this->stdout('   -project [path...]  Generates a new app folder in the path supplied.');
		$this->stdout('   -root [path...] Absolute path to Cake\'s \app\webroot Folder.');
		$this->stdout('');
	}
/**
 * Checks that given project path does not already exist, and
 * finds the app directory in it. Then it calls __buildDirLayout() with that information.
 *
 * @param string $projectPath
 */
	function project($projectPath = null) {
		if($projectPath != '') {
			while ($this->__checkPath($projectPath) === true) {
				$projectPath = $this->getInput('Directory exists please choose another name:');
				$this->__buildDirLayout(null, null);
				exit();
			}
		} else {
			while ($projectPath == '') {
				$projectPath = $this->getInput("What is the full path for this app including the app directory name?\nExample: ".ROOT."myapp", null, ROOT.'myapp');

				if ($projectPath == '') {
					$this->stdout('The directory path you supplied was empty. Please try again.');
				}
			}
		}
		while ($this->__checkPath($projectPath) === true || $projectPath == '') {
				$projectPath = $this->getInput('Directory path exists please choose another:');
			while ($projectPath == '') {
				$projectPath = $this->getInput('The directory path you supplied was empty. Please try again.');
			}
		}
		$parentPath = explode(DS, $projectPath);
		$count = count($parentPath);
		$appName = $parentPath[$count - 1];
		$this->__buildDirLayout($projectPath, $appName);
		exit();
	}
/**
 * Returns true if given path is a directory.
 *
 * @param string $projectPath
 * @return True if given path is a directory.
 */
	function __checkPath($projectPath) {
		if(is_dir($projectPath)) {
			return true;
		} else {
			return false;
		}
	}
/**
 * Looks for a skeleton template of a Cake application,
 * and if not found asks the user for a path. When there is a path
 * this method will make a deep copy of the skeleton to the project directory.
 * A default home page will be added, and the tmp file storage will be chmod'ed to 0777.
 *
 * @param string $projectPath
 * @param string $appName
 */
	function __buildDirLayout($projectPath, $appName) {
		$skel = '';
		if($this->__checkPath(CAKE_CORE_INCLUDE_PATH.DS.'cake'.DS.'scripts'.DS.'templates'.DS.'skel') === true) {
			$skel = CAKE_CORE_INCLUDE_PATH.DS.'cake'.DS.'scripts'.DS.'templates'.DS.'skel';
		} else {

			while ($skel == '') {
				$skel = $this->getInput("What is the full path for the cake install app directory?\nExample: ", null, ROOT.'myapp'.DS);

				if ($skel == '') {
					$this->stdout('The directory path you supplied was empty. Please try again.');
				} else {
					while ($this->__checkPath($skel) === false) {
						$skel = $this->getInput('Directory path does not exist please choose another:');
					}
				}
			}
		}
		$this->stdout('');
		$this->hr();
		$this->stdout("Skel Directory: $skel");
		$this->stdout("Will be copied to:");
		$this->stdout("New App Directory: $projectPath");
		$this->hr();
		$looksGood = $this->getInput('Look okay?', array('y', 'n', 'q'), 'y');

		if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes') {
			$verboseOuptut = $this->getInput('Do you want verbose output?', array('y', 'n'), 'n');
			$verbose = false;

			if (strtolower($verboseOuptut) == 'y' || strtolower($verboseOuptut) == 'yes') {
				$verbose = true;
			}
			$this->copydirr($skel, $projectPath, 0755, $verbose);
			$this->hr();
			$this->stdout('Created: '.$projectPath);
			$this->hr();
			$this->stdout('Creating welcome page');
			$this->hr();
			$this->__defaultHome($projectPath, $appName);
			$this->stdout('Welcome page created');
			if(chmodr($projectPath.DS.'tmp', 0777) === false) {
				$this->stdout('Could not set permissions on '. $projectPath.DS.'tmp'.DS.'*');
				$this->stdout('You must manually check that these directories can be wrote to by the server');
			}
			return;
		} elseif (strtolower($looksGood) == 'q' || strtolower($looksGood) == 'quit') {
			$this->stdout('Bake Aborted.');
		} else {
			$this->project();
		}
	}
/**
 * Recursive directory copy.
 *
 * @param string $fromDir
 * @param string $toDir
 * @param octal $chmod
 * @param boolean	 $verbose
 * @return Success.
 */
	function copydirr($fromDir, $toDir, $chmod = 0755, $verbose = false) {
		$errors=array();
		$messages=array();

		if (!is_dir($toDir)) {
			uses('folder');
			$folder = new Folder();
			$folder->mkdirr($toDir, 0755);
		}

		if (!is_writable($toDir)) {
			$errors[]='target '.$toDir.' is not writable';
		}

		if (!is_dir($fromDir)) {
			$errors[]='source '.$fromDir.' is not a directory';
		}

		if (!empty($errors)) {
			if ($verbose) {
				foreach($errors as $err) {
					$this->stdout('Error: '.$err);
				}
			}
			return false;
		}
		$exceptions=array('.','..','.svn');
		$handle = opendir($fromDir);

		while (false!==($item = readdir($handle))) {
			if (!in_array($item,$exceptions)) {
				$from = str_replace('//','/',$fromDir.'/'.$item);
				$to = str_replace('//','/',$toDir.'/'.$item);
				if (is_file($from)) {
					if (@copy($from, $to)) {
						chmod($to, $chmod);
						touch($to, filemtime($from));
						$messages[]='File copied from '.$from.' to '.$to;
					} else {
						$errors[]='cannot copy file from '.$from.' to '.$to;
					}
				}

				if (is_dir($from)) {
					if (@mkdir($to)) {
						chmod($to,$chmod);
						$messages[]='Directory created: '.$to;
					} else {
						$errors[]='cannot create directory '.$to;
					}
					$this->copydirr($from,$to,$chmod,$verbose);
				}
			}
		}
		closedir($handle);

		if ($verbose) {
			foreach($errors as $err) {
				$this->stdout('Error: '.$err);
			}
			foreach($messages as $msg) {
				$this->stdout($msg);
			}
		}
		return true;
	}

	function __addAdminRoute($name){
		$file = file_get_contents(CONFIGS.'core.php');
		if (preg_match('%([/\\t\\x20]*define\\(\'CAKE_ADMIN\',[\\t\\x20\'a-z]*\\);)%', $file, $match)) {
			$result = str_replace($match[0], 'define(\'CAKE_ADMIN\', \''.$name.'\');', $file);

			if(file_put_contents(CONFIGS.'core.php', $result)){
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
/**
 * Outputs an ASCII art banner to standard output.
 *
 */
	function welcome()
	{
		$this->stdout('');
		$this->stdout(' ___  __  _  _  ___  __  _  _  __      __   __  _  _  ___ ');
		$this->stdout('|    |__| |_/  |__  |__] |__| |__]    |__] |__| |_/  |__ ');
		$this->stdout('|___ |  | | \_ |___ |    |  | |       |__] |  | | \_ |___ ');
		$this->hr();
		$this->stdout('');
	}
/**
 * Writes a file with a default home page to the project.
 *
 * @param string $dir
 * @param string $app
 */
	function __defaultHome($dir, $app) {
		$path = $dir.DS.'views'.DS.'pages'.DS;
		include(CAKE_CORE_INCLUDE_PATH.DS.'cake'.DS.'scripts'.DS.'templates'.DS.'views'.DS.'home.thtml');
		$this->createFile($path.'home.thtml', $output);
	}
/**
 * creates the proper pluralize controller for the url
 *
 * @param string $name
 * @return string $name
 */
	function __controllerPath($name) {
		return low(Inflector::tableize($name));
	}
/**
 * creates the proper pluralize controller class name.
 *
 * @param string $name
 * @return string $name
 */
	function __controllerName($name) {
		return Inflector::pluralize(Inflector::camelize($name));
	}
/**
 * creates the proper singular model name.
 *
 * @param string $name
 * @return string $name
 */
	function __modelName($name) {
		return Inflector::camelize(Inflector::singularize($name));
	}
/**
 * creates the proper singular model key for associations.
 *
 * @param string $name
 * @return string $name
 */
	function __modelKey($name) {
		return Inflector::underscore(Inflector::singularize($name)).'_id';
	}
/**
 * creates the proper model name from a foreign key.
 *
 * @param string $key
 * @return string $name
 */
	function __modelNameFromKey($key) {
		$name = str_replace('_id', '',$key);
		return $this->__modelName($name);
	}
/**
 * creates the singular name for use in views.
 *
 * @param string $name
 * @return string $name
 */
	function __singularName($name) {
		return low(Inflector::underscore(Inflector::singularize($name)));
	}
/**
 * creates the plural name for views.
 *
 * @param string $name
 * @return string $name
 */
	function __pluralName($name) {
		return low(Inflector::underscore(Inflector::pluralize($name)));
	}
/**
 * creates the singular human name used in views
 *
 * @param string $name
 * @return string $name
 */
	function __singularHumanName($name) {
		return Inflector::humanize(Inflector::underscore(Inflector::singularize($name)));
	}
/**
 * creates the plural humna name used in views
 *
 * @param string $name
 * @return string $name
 */
	function __pluralHumanName($name) {
		return Inflector::humanize(Inflector::underscore(Inflector::pluralize($name)));
	}

}
?>
