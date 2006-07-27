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

	$app = 'app';
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
			break;
		}
	}

	if (strlen($app) && $app[0] == DS) {
		$cnt = substr_count($root, DS);
		$app = str_repeat('..' . DS, $cnt) . $app;
	}
	define ('ROOT', $root.DS);
	define ('APP_DIR', $app);
	define ('DEBUG', 1);;
	define('CAKE_CORE_INCLUDE_PATH', ROOT);

	if(function_exists('ini_set')) {
		ini_set('include_path',ini_get('include_path').
													PATH_SEPARATOR.CAKE_CORE_INCLUDE_PATH.DS.
													PATH_SEPARATOR.CORE_PATH.DS.
													PATH_SEPARATOR.ROOT.DS.APP_DIR.DS.
													PATH_SEPARATOR.APP_DIR.DS.
													PATH_SEPARATOR.APP_PATH);
		define('APP_PATH', null);
		define('CORE_PATH', null);
	} else {
		define('APP_PATH', ROOT . DS . APP_DIR . DS);
		define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
	}

	require_once (ROOT.'cake'.DS.'basics.php');
	require_once (ROOT.'cake'.DS.'config'.DS.'paths.php');
	require_once (ROOT.'cake'.DS.'dispatcher.php');
	require_once (ROOT.'cake'.DS.'scripts'.DS.'templates'.DS.'skel'.DS.'config'.DS.'core.php');
	uses ('inflector', 'model'.DS.'model');
	require_once (ROOT.'cake'.DS.'app_model.php');
	require_once (ROOT.'cake'.DS.'app_controller.php');
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
	var $lowCtrl = null;
/**
 * If true, Bake will ask for permission to perform actions.
 *
 * @var boolean
 */
	var $interactive = false;
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
		$driver = 'mysql';
		$connect = 'mysql_connect';
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

		$this->stdout('');
		$this->hr();
		$this->stdout('The following database configuration will be created:');
		$this->hr();
		$this->stdout("Host:       $host");
		$this->stdout("User:       $login");
		$this->stdout("Pass:       " . str_repeat('*', strlen($password)));
		$this->stdout("Database:   $database");
		$this->hr();
		$looksGood = $this->getInput('Look okay?', array('y', 'n'), 'y');

		if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes') {
			$this->bakeDbConfig($host, $login, $password, $database);
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
		$modelName = '';
		$db =& ConnectionManager::getDataSource($dbConnection);
		$usePrefix = empty($db->config['prefix']) ? '' : $db->config['prefix'];
		if ($usePrefix) {
			$tables = array();
			foreach ($db->listSources() as $table) {
				if (! strncmp($table, $usePrefix, strlen($usePrefix))) {
					$tables[] = substr($table, strlen($usePrefix));
				}
			}
		} else {
			$tables = $db->listSources();
		}

		$inflect = new Inflector();
		$this->stdout('Possible models based on your current database:');

		for ($i = 0; $i < count($tables); $i++) {
			$this->stdout($i + 1 . ". " . $inflect->camelize($inflect->singularize($tables[$i])));
		}

		while ($modelName == '') {
			$modelName = $this->getInput('Enter a number from the list above, or type in the name of another model.');

			if ($modelName == '' || intval($modelName) > $i) {
				$this->stdout('Error:');
				$this->stdout("The model name you supplied was empty, or the number \nyou selected was not an option. Please try again.");
				$modelName = '';
			}
		}

		if (intval($modelName) > 0 && intval($modelName) <= $i ) {
			$modelClassName = $inflect->camelize($inflect->singularize($tables[$modelName - 1]));
			$modelTableName = $tables[intval($modelName) - 1];
		} else {
			$modelClassName = $inflect->camelize($modelName);
			$this->stdout("\nGiven your model named '$modelClassName', Cake would expect a database table named '" . $inflect->pluralize($modelName) . "'.");
			$tableIsGood = $this->getInput('Is this correct?', array('y','n'), 'y');

			if (strtolower($tableIsGood) == 'n' || strtolower($tableIsGood) == 'no') {
				$modelTableName = $this->getInput('What is the name of the table (enter "null" to use NO table)?');
			}
		}

		$wannaDoValidation = $this->getInput('Would you like to supply validation criteria for the fields in your model?', array('y','n'), 'y');
		$validate = array();
		$tempModel = new Model(false, $modelTableName);
		$modelFields = $db->describe($tempModel);

		if (array_search($modelTableName, $tables) !== false && (strtolower($wannaDoValidation) == 'y' || strtolower($wannaDoValidation) == 'yes')) {
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

		$modelTableName == null ? $modelTableName = $inflect->pluralize($modelName) : $modelTableName = $modelTableName;
		$wannaDoAssoc = $this->getInput('Would you like to define model associations (hasMany, hasOne, belongsTo, etc.)?', array('y','n'), 'y');

		if((strtolower($wannaDoAssoc) == 'y' || strtolower($wannaDoAssoc) == 'yes')) {
			$this->stdout('One moment while I try to detect any associations...');
			//Look for belongsTo
			foreach($modelFields as $field) {
				$offset = strpos($field['name'], '_id');

				if($offset !== false) {
					$belongsToClasses[] = $inflect->camelize(substr($field['name'], 0, $offset));
				}
			}
			//Look for hasOne and hasMany and hasAndBelongsToMany
			foreach($tables as $table) {
				$tempModelOthers = new Model(false, $table);
				$modelFieldsTemp = $db->describe($tempModelOthers);

				foreach($modelFieldsTemp as $field) {
					if($field['name'] == $inflect->singularize($modelTableName).'_id') {
						$hasOneClasses[] = $inflect->camelize($inflect->singularize($table));
						$hasManyClasses[] = $inflect->camelize($inflect->singularize($table));
					}
				}
				$offset = strpos($table, $modelTableName . '_');

				if($offset !== false) {
					$offset = strlen($modelTableName . '_');
					$hasAndBelongsToManyClasses[] = $inflect->camelize($inflect->singularize(substr($table, $offset)));
				}
				$offset = strpos($table, '_' . $modelTableName);

				if ($offset !== false) {
					$hasAndBelongsToManyClasses[] = $inflect->camelize($inflect->singularize(substr($table, 0, $offset)));
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
						$response = $this->getInput("$modelClassName belongsTo {$belongsToClasses[$i]}?", array('y','n'), 'y');

						if($response == 'y') {
							$modelAssociations['belongsTo'][] = $belongsToClasses[$i];
						}
					}
				}

				if(count($hasOneClasses)) {
					for($i = 0; $i < count($hasOneClasses); $i++) {
						$response = $this->getInput("$modelClassName hasOne {$hasOneClasses[$i]}?", array('y','n'), 'y');

						if($response == 'y') {
							$modelAssociations['hasOne'][] = $hasOneClasses[$i];
						}
					}
				}

				if(count($hasManyClasses)) {
					for($i = 0; $i < count($hasManyClasses); $i++) {
						$response = $this->getInput("$modelClassName hasMany {$hasManyClasses[$i]}?", array('y','n'), 'y');

						if($response == 'y') {
							$modelAssociations['hasMany'][] = $hasManyClasses[$i];
						}
					}
				}

				if(count($hasAndBelongsToManyClasses)) {
					for($i = 0; $i < count($hasAndBelongsToManyClasses); $i++) {
						$response = $this->getInput("$modelClassName hasAndBelongsToMany {$hasAndBelongsToManyClasses[$i]}?", array('y','n'), 'y');

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
				$this->stdout("Association '$modelClassName {$assocs[$assocType]} $assocClassName' defined.");
				$wannaDoMoreAssoc = $this->getInput('Define another association?', array('y','n'), 'y');
			}
		}
		$this->stdout('');
		$this->hr();
		$this->stdout('The following model will be created:');
		$this->hr();
		$this->stdout("Model Name:    $modelClassName");
		$this->stdout("DB Connection: " . ($usingDefault ? 'default' : $dbConnection));
		$this->stdout("Model Table:   " . $modelTableName);
		$this->stdout("Validation:    " . print_r($validate, true));

		if(count($belongsToClasses) || count($hasOneClasses) || count($hasManyClasses) || count($hasAndBelongsToManyClasses)) {
			$this->stdout("Associations:");

			if(count($modelAssociations['belongsTo'])) {
				for($i = 0; $i < count($modelAssociations['belongsTo']); $i++) {
					$this->stdout("            $modelClassName belongsTo {$modelAssociations['belongsTo'][$i]}");
				}
			}

			if(count($modelAssociations['hasOne'])) {
				for($i = 0; $i < count($modelAssociations['hasOne']); $i++) {
					$this->stdout("            $modelClassName hasOne	{$modelAssociations['hasOne'][$i]}");
				}
			}

			if(count($modelAssociations['hasMany'])) {
				for($i = 0; $i < count($modelAssociations['hasMany']); $i++) {
					$this->stdout("            $modelClassName hasMany   {$modelAssociations['hasMany'][$i]}");
				}
			}

			if(count($modelAssociations['hasAndBelongsToMany'])) {
				for($i = 0; $i < count($modelAssociations['hasAndBelongsToMany']); $i++) {
					$this->stdout("            $modelClassName hasAndBelongsToMany {$modelAssociations['hasAndBelongsToMany'][$i]}");
				}
			}
		}
		$this->hr();
		$looksGood = $this->getInput('Look okay?', array('y','n'), 'y');

		if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes') {
			if ($modelTableName == $inflect->underscore($inflect->pluralize($modelClassName))) {
				// set it to null...
				// putting $useTable in the model
				// is unnecessary.
				$modelTableName = null;
			}
			$this->bakeModel($modelClassName, $dbConnection, $modelTableName, $validate, $modelAssociations);

			if ($this->doUnitTest()) {
				$this->bakeUnitTest('model', $modelClassName);
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
		$wannaDoScaffold = 'y';
		$controllerName = '';
		$inflect = new Inflector();

		while ($controllerName == '') {
			$controllerName = $this->getInput('Controller Name? (plural)');

			if ($controllerName == '') {
				$this->stdout('The controller name you supplied was empty. Please try again.');
			}
		}
		$controllerName = $inflect->underscore($controllerName);
		$this->lowCtrl = $controllerName;
		$doItInteractive = $this->getInput("Would you like bake to build your views interactively?\nWarning: Choosing no will overwrite {$controllerClassName} views if it exist.", array('y','n'), 'y');

		if (strtolower($doItInteractive) == 'y' || strtolower($doItInteractive) == 'yes') {
			$this->interactive = true;
			$wannaDoScaffold = $this->getInput("Would you like to create some scaffolded views (index, add, view, edit) for this controller?\nNOTE: Before doing so, you'll need to create your controller and model classes (including associated models).", array('y','n'), 'n');
		}

		if (strtolower($wannaDoScaffold) == 'y' || strtolower($wannaDoScaffold) == 'yes') {
			$file = CONTROLLERS . $controllerName . '_controller.php';

			if(!file_exists($file)) {
				$this->stdout('');
				$this->stdout("The file '$file' could not be found.\nIn order to scaffold, you'll need to first create the controller. ");
				$this->stdout('');
				die();
			} else {
				require_once(CONTROLLERS . $controllerName . '_controller.php');
				$controller = $inflect->camelize($controllerName . '_controller');
				$temp = new $controller();

				if(!in_array('Form', $temp->helpers)) {
					$temp->helpers[] = 'Form';
				}
				loadModels();
				$temp->constructClasses();
				$fieldNames = $temp->generateFieldNames(null, false);
				uses('view'.DS.'helpers'.DS.'html', 'view'.DS.'helpers'.DS.'form');
				$this->Html = new HtmlHelper();
				$this->Html->tags = $this->Html->loadConfig();

				//-------------------------[INDEX]-------------------------//
				if(!empty($temp->{$temp->modelClass}->alias)) {
					foreach ($temp->{$temp->modelClass}->alias as $key => $value) {
						$alias[] = $key;
					}
				}
				$indexView .= "<h1>List " . ucwords($inflect->humanize($inflect->pluralize($temp->modelKey))) . "</h1>\n\n";
				$indexView .= "<table cellpadding=\"0\">\n";
				$indexView .= "<tr>\n";

				foreach ($fieldNames as $fieldName) {
					$indexView .= "\t<th>".$fieldName['prompt']."</th>\n";
				}
				$indexView .= "\t<th>Actions</th>\n";
				$indexView .= "</tr>\n";
				$indexView .= "<?php foreach (\${$this->lowCtrl} as \$row): ?>\n";
				$indexView .= "<tr>\n";
				$count = 0;

				foreach($fieldNames as $field => $value) {
					if(isset($value['foreignKey'])) {
						$otherModelKey = $inflect->underscore($value['modelKey']);
						$otherControllerName = $value['controller'];
						$otherModelObject =& ClassRegistry::getObject($otherModelKey);

						if(is_object($otherModelObject)) {
							$indexView .= "\t<td><?php echo \$html->link(\$row['" . $alias[$count] ."']['" . $otherModelObject->getDisplayField() ."'], '/" . $inflect->pluralize($inflect->underscore(strtolower($alias[$count]))) ."/view/' .\$row['{$alias[$count]}']['{$otherModelObject->primaryKey}']);?></td>\n";
						} else {
							$indexView .= "\t<td><?php echo \$row['" . $alias[$count] ."']['" . $field ."'] ?></td>\n";
						}
						$count++;
					} else {
						$indexView .= "\t<td><?php echo \$row['{$temp->modelClass}']['{$field}'] ?></td>\n";
					}
				}
				$id = $temp->{$temp->modelClass}->primaryKey;
				$indexView .= "\t<td>\n";
				$indexView .= "\t\t<?php echo \$html->link('View','/$temp->viewPath/view/' . \$row['{$temp->modelClass}']['$id'])?>\n";
				$indexView .= "\t\t<?php echo \$html->link('Edit','/$temp->viewPath/edit/' . \$row['{$temp->modelClass}']['$id'])?>\n";
				$indexView .= "\t\t<?php echo \$html->link('Delete','/$temp->viewPath/delete/' . \$row['{$temp->modelClass}']['$id'], null, 'Are you sure you want to delete: id ' . \$row['{$temp->modelClass}']['$id'])?>\n";
				$indexView .= "\t</td>\n";
				$indexView .= "</tr>\n";
				$indexView .= "<?php endforeach; ?>\n";
				$indexView .= "</table>\n\n";
				$indexView .= "<ul class=\"actions\">\n";
				$indexView .= "\t<li><?php echo \$html->link('New $temp->modelClass', '/$temp->viewPath/add'); ?></li>\n";
				$indexView .= "</ul>\n";

				//-------------------------[VIEW]-------------------------//
				$modelName = $temp->modelClass;
				$modelKey = $inflect->underscore($modelName);
				$objModel =& ClassRegistry::getObject($modelKey);
				$viewView .= "<h1>View " . ucwords($inflect->humanize($inflect->pluralize($temp->modelKey))) . "</h1>\n\n";
				$count = 0;
				$viewView .= "<dl>\n";
				foreach($fieldNames as $field => $value) {
					$viewView .= "\t<dt>" . $value['prompt'] . "</dt>\n";

					if(isset($value['foreignKey'])) {
						$otherModelObject =& ClassRegistry::getObject($inflect->underscore($objModel->tableToModel[$value['table']]));
						$displayField = $otherModelObject->getDisplayField();
						$viewView .= "\t<dd><?php echo \$html->link(\${$this->lowCtrl}['{$alias[$count]}']['{$displayField}'], '/" . $inflect->underscore($value['controller']) . "/view/' . \${$this->lowCtrl}['{$objModel->tableToModel[$objModel->table]}']['{$field}'])?></dd>\n";
						$count++;
					} else {
						$viewView .= "\t<dd><?php echo \${$this->lowCtrl}['{$objModel->tableToModel[$objModel->table]}']['{$field}']?></dd>\n";
					}
				}
				$viewView .= "</dl>\n";
				$viewView .= "<ul class=\"actions\">\n";
				$viewView .= "\t<li><?php echo \$html->link('Edit " . $inflect->humanize($objModel->name) . "',   '/{$temp->viewPath}/edit/' . \${$this->lowCtrl}['{$objModel->tableToModel[$objModel->table]}']['$id']) ?> </li>\n";
				$viewView .= "\t<li><?php echo \$html->link('Delete " . $inflect->humanize($objModel->name) . "', '/{$temp->viewPath}/delete/' . \${$this->lowCtrl}['{$objModel->tableToModel[$objModel->table]}']['$id'], null, 'Are you sure you want to delete: id ' . \${$this->lowCtrl}['{$objModel->tableToModel[$objModel->table]}']['$id'] . '?') ?> </li>\n";
				$viewView .= "\t<li><?php echo \$html->link('List " . $inflect->humanize($inflect->pluralize($objModel->name)) ."',   '/{$temp->viewPath}/index') ?> </li>\n";
				$viewView .= "\t<li><?php echo \$html->link('New " . $inflect->humanize($objModel->name) . "',	'/{$temp->viewPath}/add') ?> </li>\n";

				foreach( $fieldNames as $field => $value ) {
					if( isset( $value['foreignKey'] ) ) {
						$viewView .= "\t<li> <?php echo \$html->link( 'List " . $inflect->humanize($value['controller']) . "', '/" . $inflect->underscore($value['controller']) . "/index/')?> </li>\n";
					}
				}
				$viewView .= "</ul>\n\n";

				foreach ($objModel->hasOne as $association => $relation) {
					$model = $relation['className'];
					$otherModelName = $objModel->tableToModel[$objModel->{$model}->table];
					$controller = $inflect->pluralize($model);
					$new = true;
					$viewView .= "<div class='related'><h2>Related " . $inflect->humanize($association) . "</h2>\n";
					$viewView .= "<?php if(!empty(\${$this->lowCtrl}['{$association}'])): ?>\n";
					$viewView .= "<dl>\n";
					$viewView .= "\t<?php foreach(\${$this->lowCtrl}['{$association}'] as \$field => \$value): ?>\n";
					$viewView .= "\t\t<dt><?php echo \$field ?></dt>\n";
					$viewView .= "\t\t<dd><?php echo \$value ?></dd>\n";
					$viewView .= "\t<?php endforeach; ?>\n";
					$viewView .= "</dl>\n";
					$viewView .= "<?php endif; ?>\n";
					$viewView .= "<ul class=\"actions\">\n";
					$viewView .= "\t<li><?php echo \$html->link('New " . $inflect->humanize($association) . "', '/" .$inflect->underscore($controller)."/add/' . \${$this->lowCtrl}['{$association}']['" . $objModel->{$model}->primaryKey . "'])?> </li>\n";
					$viewView .= "</ul>\n</div>\n";
				}
				$relations = array_merge($objModel->hasMany, $objModel->hasAndBelongsToMany);

				foreach($relations as $association => $relation) {
					$model = $relation['className'];
					$associationModel = new $model();
					$count = 0;
					$otherModelName = $inflect->singularize($model);
					$controller = $inflect->pluralize($model);
					$viewView .= "\n<div class='related'>\n<h2>Related " . $inflect->humanize($inflect->pluralize($association)) . "</h2>\n";
					$viewView .= "<?php if(!empty(\${$this->lowCtrl}['{$association}']['0'])):?>\n";
					$viewView .= "<table cellpadding=\"0\">\n";
					$viewView .= "<tr>\n";
					$viewView .= "<?php foreach(\${$this->lowCtrl}['{$association}']['0'] as \$column => \$value): ?>\n";
					$viewView .= "<th><?php echo \$column?></th>\n";
					$viewView .= "<?php endforeach; ?>\n";
					$viewView .= "<th>Actions</th>\n";
					$viewView .= "</tr>\n";
					$viewView .= "<?php foreach(\${$this->lowCtrl}['{$association}'] as \$row):?>\n";
					$viewView .= "<tr>\n";
					$viewView .= "\t<?php foreach(\$row as \$column => \$value):?>\n";
					$viewView .= "\t\t<td><?php echo \$value?></td>\n";
					$viewView .= "\t<?php endforeach;?>\n";
					$viewView .= "\t<td>\n";
					$viewView .= "\t\t<?php echo \$html->link('View', '/" . $inflect->underscore($controller) . "/view/' . \$row['{$associationModel->primaryKey}'])?>\n";
					$viewView .= "\t\t<?php echo \$html->link('Edit', '/" . $inflect->underscore($controller) . "/edit/' . \$row['{$associationModel->primaryKey}'])?>\n";
					$viewView .= "\t\t<?php echo \$html->link('Delete', '/" . $inflect->underscore($controller) . "/delete/' . \$row['{$associationModel->primaryKey}'], null, 'Are you sure you want to delete: id ' . \$row[\$this->controller->{$modelName}->{$association}->primaryKey] . '?')?>\n";
					$viewView .= "\t</td>\n";
					$viewView .= "</tr>\n";
					$viewView .= "<?php endforeach; ?>\n";
					$viewView .= "</table>\n";
					$viewView .= "<?php endif; ?>\n\n";
					$viewView .= "<ul class=\"actions\">\n";
					$viewView .= "<li><?php echo \$html->link('New " . $inflect->humanize($association) . "', '/" . $inflect->underscore($controller) . "/add/')?></li>\n";
					$viewView .= "</ul>\n</div>\n";
				}
				//-------------------------[ADD]-------------------------//
				$addView .= "<h1>New " . $inflect->humanize($inflect->pluralize($temp->modelKey)) . "</h1>\n";
				$fields .= "<form action=\"<?php echo \$html->url('/{$temp->viewPath}/add'); ?>\" method=\"post\">\n";
				$fields .= $this->generateFields($temp->generateFieldNames(null, true));
				$fields .= $this->generateSubmitDiv('Add');
				$addView .= $fields;
				$addView .= "</form>\n";
				$addView .= "<ul class=\"actions\">\n";
				$addView .= "<li><?php echo \$html->link('List " . $temp->viewPath . "', '/{$temp->viewPath}/index')?></li>\n";
				$addView .= "</ul>\n";

				//-------------------------[EDIT]-------------------------//
				$editView .= "<h1>Edit " . $inflect->humanize($inflect->pluralize($temp->modelKey)) . "</h1>\n";
				$editView .= "<form action=\"<?php echo \$html->url('/{$temp->viewPath}/edit/'.\$html->tagValue('{$objModel->name}/{$id}')); ?>\" method=\"post\">\n";
				$fields = $this->generateFields($temp->generateFieldNames(null, true));
				$fields .= "<?php echo \$html->hidden('{$objModel->name}/{$id}')?>\n";
				$fields .= $this->generateSubmitDiv('Save');
				$editView .= $fields;
				$editView .= "</form>\n";
				$editView .= "<ul class=\"actions\">\n";
				$editView .= "\t<li><?php echo \$html->link('List " . $temp->viewPath . "', '/{$temp->viewPath}/index')?></li>\n";
				$editView .= "</ul>\n";
				//------------------------------------------------------------------------------------//
				if(!file_exists(VIEWS.strtolower($controllerName))) {
					mkdir(VIEWS.strtolower($controllerName));
				}

				$filename = VIEWS . strtolower($controllerName) . DS . 'index.thtml';
				$this->createFile($filename, $indexView);
				$filename = VIEWS . strtolower($controllerName) . DS . 'view.thtml';
				$this->createFile($filename, $viewView);
				$filename = VIEWS . strtolower($controllerName) . DS . 'add.thtml';
				$this->createFile($filename, $addView);
				$filename = VIEWS . strtolower($controllerName) . DS . 'edit.thtml';
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
			$this->stdout("Path:            app/views/" . strtolower($controllerName) . DS . $inflect->underscore($actionName) . '.thtml');
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
		$controllerName = '';
		$uses = array();
		$helpers = array();
		$components = array();
		$wannaDoScaffolding = 'y';
		while ($controllerName == '') {
			$controllerName = $this->getInput('Controller name? Remember that Cake controller names are plural.');

			if ($controllerName == '') {
				$this->stdout('The controller name you supplied was empty. Please try again.');
			}
		}

		$inflect = new Inflector();
		$controllerClassName = $inflect->camelize($controllerName);
		$doItInteractive = $this->getInput("Would you like bake to build your controller interactively?\nWarning: Choosing no will overwrite {$controllerClassName} controller if it exist.", array('y','n'), 'y');

		if (strtolower($doItInteractive) == 'y' || strtolower($doItInteractive) == 'yes') {
			$this->interactive = true;
			$wannaDoUses = $this->getInput("Would you like this controller to use other models besides '" . $inflect->singularize($controllerClassName) .  "'?", array('y','n'), 'n');

			if (strtolower($wannaDoUses) == 'y' || strtolower($wannaDoUses) == 'yes') {
				$usesList = $this->getInput("Please provide a comma separated list of the classnames of other models you'd like to use.\nExample: 'Author, Article, Book'");
				$usesListTrimmed = str_replace(' ', '', $usesList);
				$uses = explode(',', $usesListTrimmed);
			}
			$wannaDoHelpers = $this->getInput("Would you like this controller to use other helpers besides HtmlHelper?", array('y','n'), 'n');

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
			$wannaDoScaffolding = $this->getInput("Would you like to include some basic class methods (index(), add(), view(), edit())?", array('y','n'), 'n');
		}

		if (strtolower($wannaDoScaffolding) == 'y' || strtolower($wannaDoScaffolding) == 'yes') {
			$controllerModel = $inflect->camelize($inflect->singularize($controllerClassName));
			$this->lowCtrl = $inflect->underscore($controllerName);
			loadModels();

			if(!class_exists($controllerModel)) {
				$this->stdout('You must have a model for this class to build scaffold methods. Please try again.');
				exit;
			}
			$tempModel = new $controllerModel();
			$actions .= "\n";
			$actions .= "\tfunction index() {\n";
			$actions .= "\t\t\$this->{$controllerModel}->recursive = 0;\n";
			$actions .= "\t\t\$this->set('{$this->lowCtrl}', \$this->{$controllerModel}->findAll());\n";
			$actions .= "\t}\n";
			$actions .= "\n";
			$actions .= "\tfunction add() {\n";
			$actions .= "\t\tif(empty(\$this->data)) {\n";

			foreach($tempModel->hasAndBelongsToMany as $association => $relation) {
				if(!empty($relation['className'])) {
					$model = $relation['className'];
					$lowerName = strtolower($association);
					$actions .= "\t\t\t\$this->set('{$lowerName}Array', \$this->{$controllerModel}->{$model}->generateList());\n";
					$actions .= "\t\t\t\$this->set('selected{$model}', null);\n";
				}
			}
			foreach($tempModel->belongsTo as $association => $relation) {
				if(!empty($relation['className'])) {
					$model = $relation['className'];
					$lowerName = strtolower(substr( $relation['foreignKey'], 0, strpos( $relation['foreignKey'], "_id" ) ));
					$actions .= "\t\t\t\$this->set('{$lowerName}Array', \$this->{$controllerModel}->{$model}->generateList());\n";
				}
			}
			$actions .= "\t\t\t\$this->render();\n";
			$actions .= "\t\t} else {\n";
			$actions .= "\t\t\t\$this->cleanUpFields();\n";
			$actions .= "\t\t\tif(\$this->{$controllerModel}->save(\$this->data)) {\n";
			$actions .= "\t\t\t\tif(is_object(\$this->Session)) {\n";
			$actions .= "\t\t\t\t\t\$this->Session->setFlash('The ".$inflect->humanize($controllerModel)." has been saved');\n";
			$actions .= "\t\t\t\t\t\$this->redirect('/{$this->lowCtrl}/index');\n";
			$actions .= "\t\t\t\t} else {\n";
			$actions .= "\t\t\t\t\t\$this->flash('{$controllerModel} saved.', '/{$this->lowCtrl}/index');\n";
			$actions .= "\t\t\t\t}\n";
			$actions .= "\t\t\t} else {\n";
			$actions .= "\t\t\t\tif(is_object(\$this->Session)) {\n";
			$actions .= "\t\t\t\t\t\$this->Session->setFlash('Please correct errors below.');\n";
			$actions .= "\t\t\t\t}\n";

			foreach($tempModel->hasAndBelongsToMany as $association => $relation) {
				if(!empty($relation['className'])) {
					$model = $relation['className'];
					$associationModel = new $model();
					$lowerName = strtolower($association);
					$actions .= "\t\t\t\t\${$lowerName} = array();\n";
					$actions .= "\t\t\t\t\$this->set('{$lowerName}Array', \$this->{$controllerModel}->{$model}->generateList());\n";
					$actions .= "\t\t\t\tif(!empty(\$data['{$model}']['{$model}'])) {\n";
					$actions .= "\t\t\t\t\tforeach(\$data['{$model}']['{$model}'] as \$var) {\n";
					$actions .= "\t\t\t\t\t\t\${$lowerName}[\$var] = \$var;\n";
					$actions .= "\t\t\t\t\t}\n";
					$actions .= "\t\t\t\t}\n";
					$actions .= "\t\t\t\t\$this->set('selected{$model}', \${$lowerName});\n";
				}
			}
			foreach($tempModel->belongsTo as $association => $relation) {
				if(!empty($relation['className'])) {
					$model = $relation['className'];
					$lowerName = strtolower(substr( $relation['foreignKey'], 0, strpos( $relation['foreignKey'], "_id" ) ));
					$actions .= "\t\t\t\t\$this->set('{$lowerName}Array', \$this->{$controllerModel}->{$model}->generateList());\n";
				}
			}
			$actions .= "\t\t\t}\n";
			$actions .= "\t\t}\n";
			$actions .= "\t}\n";
			$actions .= "\n";
			$actions .= "\tfunction edit(\$id) {\n";
			$actions .= "\t\tif(empty(\$this->data)) {\n";
			$actions .= "\t\t\t\$this->data = \$this->{$controllerModel}->read(null, \$id);\n";

			foreach($tempModel->hasAndBelongsToMany as $association => $relation) {
				if(!empty($relation['className'])) {
					$model = $relation['className'];
					$associationModel = new $model();
					$lowerName = strtolower($association);
					$actions .= "\t\t\t\${$lowerName} = array();\n";
					$actions .= "\t\t\t\$this->set('{$lowerName}Array', \$this->{$controllerModel}->{$model}->generateList());\n";
					$actions .= "\t\t\tif(!empty(\$this->data['{$model}'])) {\n";
					$actions .= "\t\t\t\tforeach(\$this->data['{$model}'] as \$var) {\n";
					$actions .= "\t\t\t\t\t\${$lowerName}[\$var['{$associationModel->primaryKey}']] = \$var['{$associationModel->primaryKey}'];\n";
					$actions .= "\t\t\t\t}\n";
					$actions .= "\t\t\t}\n";
					$actions .= "\t\t\t\$this->set('selected{$model}', \${$lowerName});\n";
				}
			}
			foreach($tempModel->belongsTo as $association => $relation) {
				if(!empty($relation['className'])) {
					$model = $relation['className'];
					$lowerName = strtolower(substr( $relation['foreignKey'], 0, strpos( $relation['foreignKey'], "_id" ) ));
					$actions .= "\t\t\t\$this->set('{$lowerName}Array', \$this->{$controllerModel}->{$model}->generateList());\n";
				}
			}
			$actions .= "\t\t} else {\n";
			$actions .= "\t\t\t\$this->cleanUpFields();\n";
			$actions .= "\t\t\tif(\$this->{$controllerModel}->save(\$this->data)) {\n";
			$actions .= "\t\t\t\tif(is_object(\$this->Session)) {\n";
			$actions .= "\t\t\t\t\t\$this->Session->setFlash('The ".$inflect->humanize($controllerModel)." has been saved');\n";
			$actions .= "\t\t\t\t\t\$this->redirect('/{$this->lowCtrl}/index');\n";
			$actions .= "\t\t\t\t} else {\n";
			$actions .= "\t\t\t\t\t\$this->flash('{$controllerModel} saved.', '/{$this->lowCtrl}/index');\n";
			$actions .= "\t\t\t\t}\n";
			$actions .= "\t\t\t} else {\n";
			$actions .= "\t\t\t\tif(is_object(\$this->Session)) {\n";
			$actions .= "\t\t\t\t\t\$this->Session->setFlash('Please correct errors below.');\n";
			$actions .= "\t\t\t\t}\n";

			foreach($tempModel->hasAndBelongsToMany as $association => $relation) {
				if(!empty($relation['className'])) {
					$model = $relation['className'];
					$associationModel = new $model();
					$lowerName = strtolower($association);
					$actions .= "\t\t\t\t\${$lowerName} = null;\n";
					$actions .= "\t\t\t\t\$this->set('{$lowerName}Array', \$this->{$controllerModel}->{$model}->generateList());\n";
					$actions .= "\t\t\t\tif(!empty(\$data['{$model}']['{$model}'])) {\n";
					$actions .= "\t\t\t\t\tforeach(\$data['{$model}']['{$model}'] as \$var) {\n";
					$actions .= "\t\t\t\t\t\t\${$lowerName}[\$var] = \$var;\n";
					$actions .= "\t\t\t\t\t}\n";
					$actions .= "\t\t\t\t}\n";
					$actions .= "\t\t\t\t\$this->set('selected{$model}', \${$lowerName});\n";
				}
			}
			foreach($tempModel->belongsTo as $association => $relation) {
				if(!empty($relation['className'])) {
					$model = $relation['className'];
					$lowerName = strtolower(substr( $relation['foreignKey'], 0, strpos( $relation['foreignKey'], "_id" ) ));
					$actions .= "\t\t\t\t\$this->set('{$lowerName}Array', \$this->{$controllerModel}->{$model}->generateList());\n";
				}
			}
			$actions .= "\t\t\t}\n";
			$actions .= "\t\t}\n";
			$actions .= "\t}\n";
			$actions .= "\n";
			$actions .= "\tfunction view(\$id) {\n";
			$actions .= "\t\t\$this->set('{$this->lowCtrl}', \$this->{$controllerModel}->read(null, \$id));\n";
			$actions .= "\t}\n";
			$actions .= "\n";
			$actions .= "\tfunction delete(\$id) {\n";
			$actions .= "\t\tif(\$this->{$controllerModel}->del(\$id)) {\n";
			$actions .= "\t\t\tif(is_object(\$this->Session)) {\n";
			$actions .= "\t\t\t\t\$this->Session->setFlash('The ".$inflect->humanize($controllerModel).": '.\$id.' has been deleted');\n";
			$actions .= "\t\t\t\t\$this->redirect('/{$this->lowCtrl}/index');\n";
			$actions .= "\t\t\t} else {\n";
			$actions .= "\t\t\t\t\$this->flash('{$controllerModel} '.\$id.' has been deleted', '/{$this->lowCtrl}/index');\n";
			$actions .= "\t\t\t}\n";
			$actions .= "\t\t}\n";
			$actions .= "\t}\n";
			$actions .= "\n";
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
				$this->bakeController($controllerClassName, $uses, $helpers, $components, $actions);

				if ($this->doUnitTest()) {
					$this->bakeUnitTest('controller', $controllerClassName);
				}
			} else {
				$this->stdout('Bake Aborted.');
			}
		} else {
			$this->bakeController($controllerClassName, $uses, $helpers, $components, $actions);
			exit();
		}
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
	function bakeDbConfig($host, $login, $password, $database) {
		$out = "<?php\n";
		$out .= "class DATABASE_CONFIG\n";
		$out .= "{\n";
		$out .= "\tvar \$default = array(\n";
		$out .= "\t\t'driver' => 'mysql',\n";
		$out .= "\t\t'connect' => 'mysql_connect',\n";
		$out .= "\t\t'host' => '$host',\n";
		$out .= "\t\t'login' => '$login',\n";
		$out .= "\t\t'password' => '$password',\n";
		$out .= "\t\t'database' => '$database' \n";
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
					$out .= "\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t'foreignKey' => '',\n";
					$out .= "\t\t\t\t\t'dependent' => ''),\n\n";
				}
				$out .= "\t);\n\n";
			}

			if(count($modelAssociations['hasMany'])) {
				$out .= "\tvar \$hasMany = array(\n";

				for($i = 0; $i < count($modelAssociations['hasMany']); $i++) {
					$out .= "\t\t\t'{$modelAssociations['hasMany'][$i]}' =>\n";
					$out .= "\t\t\t array('className' => '{$modelAssociations['hasMany'][$i]}',\n";
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
		$inflect = new Inflector();
		$filename = MODELS.$inflect->underscore($modelClassName) . '.php';
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
		$out = "<h1>$actionName</h1>\n";
		$out .= $content;
		$inflect = new Inflector();

		if(!file_exists(VIEWS.strtolower($controllerName))) {
			mkdir(VIEWS.strtolower($controllerName));
		}
		$filename = VIEWS . strtolower($controllerName) . DS . $inflect->underscore($actionName) . '.thtml';
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
		$inflect = new Inflector();
		$out = "<?php\n";
		$out .= "class $controllerName" . "Controller extends AppController\n";
		$out .= "{\n";
		$out .= "\t//var \$scaffold;\n";
		$out .= "\tvar \$name = '$controllerName';\n";

		if (count($uses)) {
			$out .= "\tvar \$uses = array('" . $inflect->singularize($controllerName) . "', ";

			foreach($uses as $use) {
				if ($use != $uses[count($uses) - 1]) {
					$out .= "'" . ucfirst($use) . "', ";
				} else {
					$out .= "'" . ucfirst($use) . "'";
				}
			}
			$out .= ");\n";
		}

		if (count($helpers)) {
			$out .= "\tvar \$helpers = array('Html', ";

			foreach($helpers as $help) {
				if ($help != $helpers[count($helpers) - 1]) {
					$out .= "'" . ucfirst($help) . "', ";
				} else {
					$out .= "'" . ucfirst($help) . "'";
				}
			}
			$out .= ");\n";
		}

		if (count($components)) {
			$out .= "\tvar \$components = array(";

			foreach($components as $comp) {
				if ($comp != $components[count($components) - 1]) {
					$out .= "'" . ucfirst($comp) . "', ";
				} else {
					$out .= "'" . ucfirst($comp) . "'";
				}
			}
			$out .= ");\n";
		}

		$out .= $actions;
		$out .= "}\n";
		$out .= "?>";
		$filename = CONTROLLERS . $inflect->underscore($controllerName) . '_controller.php';
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
				$out .= 'loadModelTest();'."\n\n";
				$out .= "class {$className}TestCase extends UnitTestCase\n{\n";
				$out .= "\tvar \$object = null;\n\n";
				$out .= "\tfunction setUp()\n\t{\n\t\t\$this->object = new {$className}();\n";
				$out .= "\t}\n\n\tfunction tearDown()\n\t{\n\t\tunset(\$this->object);\n\t}\n";
				$out .= "\n\t/*\n\tfunction testMe()\n\t{\n";
				$out .= "\t\t\$result = \$this->object->doSomething();\n";
				$out .= "\t\t\$expected = 1;\n";
				$out .= "\t\t\$this->assertEquals(\$result, \$expected);\n\t}\n\t*/\n}";
				$path = MODEL_TESTS;
				$filename = $inflect->underscore($className).'.test.php';
			break;
			case 'controller':
				$out .= 'loadControllerTest();'."\n\n";
				$out .= "class {$className}ControllerTestCase extends UnitTestCase\n{\n";
				$out .= "\tvar \$object = null;\n\n";
				$out .= "\tfunction setUp()\n\t{\n\t\t\$this->object = new {$className}();\n";
				$out .= "\t}\n\n\tfunction tearDown()\n\t{\n\t\tunset(\$this->object);\n\t}\n";
				$out .= "\n\t/*\n\tfunction testMe()\n\t{\n";
				$out .= "\t\t\$result = \$this->object->doSomething();\n";
				$out .= "\t\t\$expected = 1;\n";
				$out .= "\t\t\$this->assertEquals(\$result, \$expected);\n\t}\n\t*/\n}";
				$path = CONTROLLER_TESTS;
				$filename = $inflect->underscore($className.'Controller').'.test.php';
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
				if ($val == '') {
					unset($path[$i]);
				}
			}
			$path = implode(DS, $path);
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
		$shortPath = str_replace(ROOT, null, $path);
		$shortPath = str_replace('../', '', $shortPath);
		$shortPath = str_replace('//', '/', $shortPath);

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
		foreach( $fields as $field ) {
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
						//$strFormFields = $strFormFields . $this->Html->hiddenTag( $field['tagName']);
					break;
					case "date":
						if( !isset( $field['selected'])) {
							$field['selected'] = null;
						}
						$strFormFields = $strFormFields.$this->generateDate( $field['tagName'], $field['prompt'], null, null, null, null, $field['selected']);
					break;
					case "datetime":
						if( !isset( $field['selected'])) {
							$field['selected'] = null;
						}
						$strFormFields = $strFormFields.$this->generateDateTime( $field['tagName'], $field['prompt'], '','','', '', $field['selected']);
					break;
					case "time":
						if( !isset( $field['selected'])) {
							$field['selected'] = null;
						}
						$strFormFields = $strFormFields.$this->generateTime( $field['tagName'], $field['prompt'], '','','', '', $field['selected']);
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
		$htmlOptions['id'] = strtolower(str_replace('/', '_',$tagName));
		$htmlAttributes = $htmlOptions;
		$htmlAttributes['cols'] = $cols;
		$htmlAttributes['rows'] = $rows;
		$tagNameArray = explode('/', $tagName);
		$str = "\t<?php echo \$html->textarea('{$tagName}', " . $this->attributesToArray($htmlAttributes) . ") ?>\n";
		$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
		$strLabel = "\n\t" . $this->labelTag( $tagName, $prompt );
		$divClass = "optional";

		if( $required ) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.

		if( $this->isFieldError( $tagName ) )
		{
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
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
		$htmlOptions['id'] = strtolower(str_replace('/', '_',$tagName));
		$tagNameArray = explode('/', $tagName);
		$str = "\t<?php echo \$html->checkbox('{$tagName}', null, " . $this->attributesToArray($htmlAttributes) . ")?>\n";
		$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
		$strLabel = "\n\t" . $this->labelTag( $tagName, $prompt );
		$divClass = "optional";

		if($required) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.

		if($this->isFieldError($tagName)) {
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );
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
		$htmlOptions['id'] = strtolower(str_replace('/', '_',$tagName));
		$tagNameArray = explode('/', $tagName);
		$str = "\t<?php echo \$html->dateTimeOptionTag('{$tagName}', 'MDY' , 'NONE', \$html->tagValue('{$tagName}'), " . $this->attributesToArray($htmlOptions) . ")?>\n";
		$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
		$strLabel = "\n\t" . $this->labelTag( $tagName, $prompt );
		$divClass = "optional";

		if($required) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.

		if($this->isFieldError($tagName)) {
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );
		$requiredDiv = $this->divTag( $divClass, $divTagInside );
		return $this->divTag("date", $requiredDiv);
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
		$htmlOptions['id']=strtolower(str_replace('/', '_', $tagName));
		$str = $this->Html->dateTimeOptionTag($tagName, 'NONE', '24', $selected, $htmlOptions);
		$strLabel = $this->labelTag($tagName, $prompt);
		$divClass = "optional";
		if ($required) {
			$divClass = "required";
		}
		$strError = "";

		if ($this->isFieldError($tagName)) {
			$strError = $this->pTag('error', $errorMsg);
			$divClass = sprintf("%s error", $divClass);
		}
		$divTagInside = sprintf("%s %s %s", $strError, $strLabel, $str);
		$requiredDiv = $this->divTag($divClass, $divTagInside);
		return $this->divTag("time", $requiredDiv);
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
		$htmlOptions['id'] = strtolower(str_replace('/', '_',$tagName));
		$tagNameArray = explode('/', $tagName);
		$str = "\t<?php echo \$html->dateTimeOptionTag('{$tagName}', 'MDY' , '12', \$html->tagValue('{$tagName}'), " . $this->attributesToArray($htmlOptions) . ")?>\n";
		$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
		$strLabel = "\n\t" . $this->labelTag( $tagName, $prompt );
		$divClass = "optional";

		if($required) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.

		if($this->isFieldError($tagName)) {
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
		$divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );
		$requiredDiv = $this->divTag( $divClass, $divTagInside );
		return $this->divTag("date", $requiredDiv);
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
		$htmlOptions['id'] = strtolower(str_replace('/', '_', $tagName));
		$htmlAttributes = $htmlOptions;
		$htmlAttributes['size'] = $size;
		$tagNameArray = explode('/', $tagName);
		$str = "\t<?php echo \$html->input('{$tagName}', " . $this->attributesToArray($htmlAttributes) . ") ?>\n";
		$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
		$strLabel = "\n\t" . $this->labelTag( $tagName, $prompt );
		$divClass = "optional";

		if($required) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.

		if($this->isFieldError($tagName)) {
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
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
		$selectAttr['id'] = strtolower(str_replace('/', '_',$tagName));
		$tagNameArray = explode('/', $tagName);
		$inflect = new Inflector();
		$model = str_replace('_id', '', $tagNameArray[1]);
		$properModel = $inflect->camelize($model);
		$controllerPath = strtolower(substr($inflect->pluralize($properModel), 0, 1)) . substr($inflect->pluralize($properModel), 1);
		$actionPath = strtolower(substr($properModel, 0, 1)) . substr($properModel, 1) . 'List';
		$path = "/$controllerPath/$actionPath";
		$lowerName = strtolower($tagNameArray[0]);

		if($selectAttr['multiple'] != 'multiple') {
			$str = "\t<?php echo \$html->selectTag('{$tagName}', " . "\${$model}Array, \$html->tagValue('{$tagName}'), " . $this->attributesToArray($selectAttr) . ") ?>\n";
			$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
		} else {
			$str = "\t<?php echo \$html->selectTag('{$tagName}', \${$lowerName}Array, \$selected{$tagNameArray[0]}, array('multiple' => 'multiple', 'class' => 'selectMultiple', 'id' => '{$lowerName}_{$lowerName}', )) ?>\n";
			$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
		}
		$strLabel = "\n\t" . $this->labelTag( $tagName, $prompt );
		$divClass = "optional";

		if($required) {
			$divClass = "required";
		}
		$strError = "";// initialize the error to empty.

		if($this->isFieldError($tagName)) {
			// if it was an error that occured, then add the error message, and append " error" to the div tag.
			$strError = $this->pTag( 'error', $errorMsg );
			$divClass = sprintf( "%s error", $divClass );
		}
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
		return $this->divTag( 'submit', $this->Html->submit( $displayText, $htmlOptions) );
	}
/**
 * Returns HTML for a LABEL form element.
 *
 * @param string $tagName
 * @param string $text
 * @return Generated HTML.
 */
	function labelTag( $tagName, $text ) {
		return sprintf( TAG_LABEL, strtolower(str_replace('/', '_',$tagName)), $text ) . "\n";
	}

/**
 * Tests given field for validity, and returns true if there are errors.
 *
 * @param string $field
 * @return Success.
 */
	function isFieldError($field ) {
		$error = 1;
		$this->Html->setFormTag( $field );
		if( $error == $this->Html->tagIsInvalid( $this->Html->model, $this->Html->field) ) {
			return true;
		} else {
			return false;
		}
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
			if(substr($out, -3, 1) == ',') {
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
	function project($projectPath) {
		if($projectPath != '') {
			while ($this->__checkPath($projectPath) === true) {
				$projectPath = $this->getInput('Directory exists please choose another name:');
				$this->__buildDirLayout($projectPath);
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
		if($this->__checkPath(ROOT.'cake'.DS.'scripts'.DS.'templates'.DS.'skel') === true) {
			$skel = ROOT.'cake'.DS.'scripts'.DS.'templates'.DS.'skel';
		} else {

			while ($skel == '') {
				$skel = $this->getInput("What is the full path for the cake install app directory?\nExample: ", null, ROOT.'app'.DS);

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
		$this->stdout("New App Direcotry: $projectPath");
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
			$this->project($projectPath);
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
		include(ROOT.'cake'.DS.'scripts'.DS.'templates'.DS.'views'.DS.'home.thtml');
		$this->createFile($path.'home.thtml', $output);
	}
}
?>