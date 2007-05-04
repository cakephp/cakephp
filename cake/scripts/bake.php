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
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007,	Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.scripts.bake
 * @since			CakePHP(tm) v 0.10.0.1232
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Bake is a command-line code generation utility for automating programmer chores.
 *
 * @package		cake
 * @subpackage	cake.cake.scripts
 */
class BakeScript extends CakeScript {
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
	function initialize() {
		$this->welcome();
	}
/**
 * Main-loop method.
 *
 */
	function main() {

		$this->out('');
		$this->out('');
		$this->out('Baking...');
		$this->hr();
		$this->out('Name: '. APP_DIR);
		$this->out('Path: '. ROOT.DS.APP_DIR);
		$this->hr();

		if(!file_exists(CONFIGS.'database.php')) {
			$this->out('');
			$this->out('Your database configuration was not found. Take a moment to create one:');
			$this->doDbConfig();
		}
		require_once (CONFIGS.'database.php');

		$this->out('[M]odel');
		$this->out('[C]ontroller');
		$this->out('[V]iew');
		$invalidSelection = true;

		while ($invalidSelection) {
			$classToBake = strtoupper($this->in('What would you like to Bake?', array('M', 'V', 'C')));
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
					$this->out('You have made an invalid selection. Please choose a type of class to Bake by entering M, V, or C.');
			}
		}
	}
/**
 * Database configuration setup.
 *
 */
	function doDbConfig() {
		$this->hr();
		$this->out('Database Configuration:');
		$this->hr();

		$driver = '';

		while ($driver == '') {
			$driver = $this->in('What database driver would you like to use?', array('mysql','mysqli','mssql','sqlite','postgres', 'odbc'), 'mysql');
			if ($driver == '') {
				$this->out('The database driver supplied was empty. Please supply a database driver.');
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
			$this->out('The connection parameter could not be set.');
			break;
		}

		$host = '';

		while ($host == '') {
			$host = $this->in('What is the hostname for the database server?', null, 'localhost');
			if ($host == '') {
				$this->out('The host name you supplied was empty. Please supply a hostname.');
			}
		}
		$login = '';

		while ($login == '') {
			$login = $this->in('What is the database username?', null, 'root');

			if ($login == '') {
				$this->out('The database username you supplied was empty. Please try again.');
			}
		}
		$password = '';
		$blankPassword = false;

		while ($password == '' && $blankPassword == false) {
			$password = $this->in('What is the database password?');
			if ($password == '') {
				$blank = $this->in('The password you supplied was empty. Use an empty password?', array('y', 'n'), 'n');
				if($blank == 'y')
				{
					$blankPassword = true;
				}
			}
		}
		$database = '';

		while ($database == '') {
			$database = $this->in('What is the name of the database you will be using?', null, 'cake');

			if ($database == '')  {
				$this->out('The database name you supplied was empty. Please try again.');
			}
		}

		$prefix = '';

		while ($prefix == '') {
			$prefix = $this->in('Enter a table prefix?', null, 'n');
		}
		if(low($prefix) == 'n') {
			$prefix = '';
		}

		$this->out('');
		$this->hr();
		$this->out('The following database configuration will be created:');
		$this->hr();
		$this->out("Driver:        $driver");
		$this->out("Connection:    $connect");
		$this->out("Host:          $host");
		$this->out("User:          $login");
		$this->out("Pass:          " . str_repeat('*', strlen($password)));
		$this->out("Database:      $database");
		$this->out("Table prefix:  $prefix");
		$this->hr();
		$looksGood = $this->in('Look okay?', array('y', 'n'), 'y');

		if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
			$this->bakeDbConfig($driver, $connect, $host, $login, $password, $database, $prefix);
		} else {
			$this->out('Bake Aborted.');
		}
	}
/**
 * Action to create a Model.
 *
 */
	function doModel()
	{
		$this->hr();
		$this->out('Model Bake:');
		$this->hr();
		$this->interactive = true;

		$useTable = null;
		$primaryKey = 'id';
		$validate = array();
		$associations = array();
		/*$usingDefault = $this->in('Will your model be using a database connection setting other than the default?');
		if (low($usingDefault) == 'y' || low($usingDefault) == 'yes')
		{
			$useDbConfig = $this->in('Please provide the name of the connection you wish to use.');
		}*/
		$useDbConfig = 'default';
		$this->__doList($useDbConfig);


		$enteredModel = '';

		while ($enteredModel == '') {
			$enteredModel = $this->in('Enter a number from the list above, or type in the name of another model.');

			if ($enteredModel == '' || intval($enteredModel) > count($this->__modelNames)) {
				$this->out('Error:');
				$this->out("The model name you supplied was empty, or the number \nyou selected was not an option. Please try again.");
				$enteredModel = '';
			}
		}

		if (intval($enteredModel) > 0 && intval($enteredModel) <= count($this->__modelNames)) {
			$currentModelName = $this->__modelNames[intval($enteredModel) - 1];
		} else {
			$currentModelName = $enteredModel;
		}

		$db =& ConnectionManager::getDataSource($useDbConfig);

		$useTable = Inflector::tableize($currentModelName);
		$fullTableName = $db->fullTableName($useTable, false);
		if(array_search($useTable, $this->__tables) === false) {
			$this->out("\nGiven your model named '$currentModelName', Cake would expect a database table named '" . $fullTableName . "'.");
			$tableIsGood = $this->in('do you want to use this table?', array('y','n'), 'y');
		}

		if (low($tableIsGood) == 'n' || low($tableIsGood) == 'no') {
			$useTable = $this->in('What is the name of the table (enter "null" to use NO table)?');
		}
		$tableIsGood = false;
		while($tableIsGood == false && low($useTable) != 'null') {
			if (is_array($this->__tables) && !in_array($useTable, $this->__tables)) {
				$fullTableName = $db->fullTableName($useTable, false);
				$this->out($fullTableName . ' does not exist.');
				$useTable = $this->in('What is the name of the table (enter "null" to use NO table)?');
				$tableIsGood = false;
			} else {
				$tableIsGood = true;
			}
		}
		$wannaDoValidation = $this->in('Would you like to supply validation criteria for the fields in your model?', array('y','n'), 'y');

		if(in_array($useTable, $this->__tables)) {
			loadModel();
			$tempModel = new Model(false, $useTable);
			$modelFields = $db->describe($tempModel);
			if(isset($modelFields[0]['name']) && $modelFields[0]['name'] != 'id') {
				$primaryKey = $this->in('What is the primaryKey?', null, $modelFields[0]['name']);
			}
		}
		$validate = array();

		if (array_search($useTable, $this->__tables) !== false && (low($wannaDoValidation) == 'y' || low($wannaDoValidation) == 'yes')) {
			foreach($modelFields as $field) {
				$this->out('');
				$prompt = 'Name: ' . $field['name'] . "\n";
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

				if($field['null'] == 1 || $field['name'] == $primaryKey || $field['name'] == 'created' || $field['name'] == 'modified') {
					$validation = $this->in($prompt, null, '5');
				} else {
					$validation = $this->in($prompt, null, '1');
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

		$wannaDoAssoc = $this->in('Would you like to define model associations (hasMany, hasOne, belongsTo, etc.)?', array('y','n'), 'y');

		if((low($wannaDoAssoc) == 'y' || low($wannaDoAssoc) == 'yes')) {
			$this->out('One moment while I try to detect any associations...');
			$possibleKeys = array();
			//Look for belongsTo
			$i = 0;
			foreach($modelFields as $field) {
				$offset = strpos($field['name'], '_id');
				if($field['name'] != $primaryKey && $offset !== false) {
					$tmpModelName = $this->__modelNameFromKey($field['name']);
					$associations['belongsTo'][$i]['alias'] = $tmpModelName;
					$associations['belongsTo'][$i]['className'] = $tmpModelName;
					$associations['belongsTo'][$i]['foreignKey'] = $field['name'];
					$i++;
				}
			}
			//Look for hasOne and hasMany and hasAndBelongsToMany
			$i = 0;
			$j = 0;
			foreach($this->__tables as $otherTable) {
				$tempOtherModel = & new Model(false, $otherTable);
				$modelFieldsTemp = $db->describe($tempOtherModel);
				foreach($modelFieldsTemp as $field) {
					if($field['type'] == 'integer' || $field['type'] == 'string') {
						$possibleKeys[$otherTable][] = $field['name'];
					}
					if($field['name'] != $primaryKey && $field['name'] == $this->__modelKey($currentModelName)) {
						$tmpModelName = $this->__modelName($otherTable);
						$associations['hasOne'][$j]['alias'] = $tmpModelName;
						$associations['hasOne'][$j]['className'] = $tmpModelName;
						$associations['hasOne'][$j]['foreignKey'] = $field['name'];

						$associations['hasMany'][$j]['alias'] = $tmpModelName;
						$associations['hasMany'][$j]['className'] = $tmpModelName;
						$associations['hasMany'][$j]['foreignKey'] = $field['name'];
						$j++;
					}
				}
				$offset = strpos($otherTable, $useTable . '_');
				if($offset !== false) {
					$offset = strlen($useTable . '_');
					$tmpModelName = $this->__modelName(substr($otherTable, $offset));
					$associations['hasAndBelongsToMany'][$i]['alias'] = $tmpModelName;
					$associations['hasAndBelongsToMany'][$i]['className'] = $tmpModelName;
					$associations['hasAndBelongsToMany'][$i]['foreignKey'] = $this->__modelKey($currentModelName);
					$associations['hasAndBelongsToMany'][$i]['associationForeignKey'] = $this->__modelKey($tmpModelName);
					$associations['hasAndBelongsToMany'][$i]['joinTable'] = $otherTable;
					$i++;
				}
				$offset = strpos($otherTable, '_' . $useTable);
				if ($offset !== false) {
					$tmpModelName = $this->__modelName(substr($otherTable, 0, $offset));
					$associations['hasAndBelongsToMany'][$i]['alias'] = $tmpModelName;
					$associations['hasAndBelongsToMany'][$i]['className'] = $tmpModelName;
					$associations['hasAndBelongsToMany'][$i]['foreignKey'] = $this->__modelKey($currentModelName);
					$associations['hasAndBelongsToMany'][$i]['associationForeignKey'] = $this->__modelKey($tmpModelName);
					$associations['hasAndBelongsToMany'][$i]['joinTable'] = $otherTable;
					$i++;
				}
			}
			$this->out('Done.');
			$this->hr();
			//if none found...
			if(empty($associations)) {
				$this->out('None found.');
			} else {
				$this->out('Please confirm the following associations:');
				$this->hr();
				if(!empty($associations['belongsTo'])) {
					$count = count($associations['belongsTo']);
					for($i = 0; $i < $count; $i++) {
						if($currentModelName == $associations['belongsTo'][$i]['alias']) {
							$response = $this->in("{$currentModelName} belongsTo {$associations['belongsTo'][$i]['alias']}\nThis looks like a self join. Do you want to specify an alternate association alias?", array('y','n'), 'y');
							if('y' == low($response) || 'yes' == low($response)) {
								$associations['belongsTo'][$i]['alias'] = $this->in("So what is the alias?", null, $associations['belongsTo'][$i]['alias']);
							}
							if($currentModelName != $associations['belongsTo'][$i]['alias']) {
								$response = $this->in("$currentModelName belongsTo {$associations['belongsTo'][$i]['alias']}?", array('y','n'), 'y');
							} else {
								$response = 'n';
							}
						} else {
							$response = $this->in("$currentModelName belongsTo {$associations['belongsTo'][$i]['alias']}?", array('y','n'), 'y');
						}
						if('n' == low($response) || 'no' == low($response)) {
							unset($associations['belongsTo'][$i]);
						}
					}
					$associations['belongsTo'] = array_merge($associations['belongsTo']);
				}

				if(!empty($associations['hasOne'])) {
					$count = count($associations['hasOne']);
					for($i = 0; $i < $count; $i++) {
						if($currentModelName == $associations['hasOne'][$i]['alias']) {
							$response = $this->in("{$currentModelName} hasOne {$associations['hasOne'][$i]['alias']}\nThis looks like a self join. Do you want to specify an alternate association alias?", array('y','n'), 'y');
							if('y' == low($response) || 'yes' == low($response)) {
								$associations['hasOne'][$i]['alias'] = $this->in("So what is the alias?", null, $associations['hasOne'][$i]['alias']);
							}
							if($currentModelName != $associations['hasOne'][$i]['alias']) {
								$response = $this->in("$currentModelName hasOne {$associations['hasOne'][$i]['alias']}?", array('y','n'), 'y');
							} else {
								$response = 'n';
							}
						} else {
							$response = $this->in("$currentModelName hasOne {$associations['hasOne'][$i]['alias']}?", array('y','n'), 'y');
						}
						if('n' == low($response) || 'no' == low($response)) {
							unset($associations['hasOne'][$i]);
						}
					}
					$associations['hasOne'] = array_merge($associations['hasOne']);
				}

				if(!empty($associations['hasMany'])) {
					$count = count($associations['hasMany']);
					for($i = 0; $i < $count; $i++) {
						if($currentModelName == $associations['hasMany'][$i]['alias']) {
							$response = $this->in("{$currentModelName} hasMany {$associations['hasMany'][$i]['alias']}\nThis looks like a self join. Do you want to specify an alternate association alias?", array('y','n'), 'y');
							if('y' == low($response) || 'yes' == low($response)) {
								$associations['hasMany'][$i]['alias'] = $this->in("So what is the alias?", null, $associations['hasMany'][$i]['alias']);
							}
							if($currentModelName != $associations['hasMany'][$i]['alias']) {
								$response = $this->in("$currentModelName hasMany {$associations['hasMany'][$i]['alias']}?", array('y','n'), 'y');
							} else {
								$response = 'n';
							}
						} else {
							$response = $this->in("$currentModelName hasMany {$associations['hasMany'][$i]['alias']}?", array('y','n'), 'y');
						}
						if('n' == low($response) || 'no' == low($response)) {
							unset($associations['hasMany'][$i]);
						}
					}
					$associations['hasMany'] = array_merge($associations['hasMany']);
				}

				if(!empty($associations['hasAndBelongsToMany'])) {
					$count = count($associations['hasAndBelongsToMany']);
					for($i = 0; $i < $count; $i++) {
						if($currentModelName == $associations['hasAndBelongsToMany'][$i]['alias']) {
							$response = $this->in("{$currentModelName} hasAndBelongsToMany {$associations['hasAndBelongsToMany'][$i]['alias']}\nThis looks like a self join. Do you want to specify an alternate association alias?", array('y','n'), 'y');
							if('y' == low($response) || 'yes' == low($response)) {
								$associations['hasAndBelongsToMany'][$i]['alias'] = $this->in("So what is the alias?", null, $associations['hasAndBelongsToMany'][$i]['alias']);
							}
							if($currentModelName != $associations['hasAndBelongsToMany'][$i]['alias']) {
								$response = $this->in("$currentModelName hasAndBelongsToMany {$associations['hasAndBelongsToMany'][$i]['alias']}?", array('y','n'), 'y');
							} else {
								$response = 'n';
							}
						} else {
							$response = $this->in("$currentModelName hasAndBelongsToMany {$associations['hasAndBelongsToMany'][$i]['alias']}?", array('y','n'), 'y');
						}
						if('n' == low($response) || 'no' == low($response)) {
							unset($associations['hasAndBelongsToMany'][$i]);
						}
					}
					$associations['hasAndBelongsToMany'] = array_merge($associations['hasAndBelongsToMany']);
				}
			}
			$wannaDoMoreAssoc = $this->in('Would you like to define some additional model associations?', array('y','n'), 'n');

			while((low($wannaDoMoreAssoc) == 'y' || low($wannaDoMoreAssoc) == 'yes')) {
				$assocs = array(1=>'belongsTo', 2=>'hasOne', 3=>'hasMany', 4=>'hasAndBelongsToMany');
				$bad = true;
				while($bad) {
					$this->out('What is the association type?');
					$prompt = "1- belongsTo\n";
					$prompt .= "2- hasOne\n";
					$prompt .= "3- hasMany\n";
					$prompt .= "4- hasAndBelongsToMany\n";
					$assocType = intval($this->in($prompt, null, null));

					if(intval($assocType) < 1 || intval($assocType) > 4) {
						$this->out('The selection you entered was invalid. Please enter a number between 1 and 4.');
					} else {
						$bad = false;
					}
				}
				$this->out('For the following options be very careful to match your setup exactly. Any spelling mistakes will cause errors.');
				$this->hr();
				$associationName = $this->in('What is the name of this association?');
				$className = $this->in('What className will '.$associationName.' use?', null, $associationName );
				$suggestedForeignKey = null;
				if($assocType == '1') {
					$showKeys = $possibleKeys[$useTable];
					$suggestedForeignKey = $this->__modelKey($associationName);
				} else {
					$otherTable = Inflector::tableize($className);
					if(in_array($otherTable, $this->__tables)) {
						if($assocType < '4') {
							$showKeys = $possibleKeys[$otherTable];
						} else {
							$showKeys = null;
						}
					} else {
						$otherTable = $this->in('What is the table for this class?');
						$showKeys = $possibleKeys[$otherTable];
					}
					$suggestedForeignKey = $this->__modelKey($currentModelName);
				}
				if(!empty($showKeys)) {
					$this->out('A helpful List of possible keys');
					for ($i = 0; $i < count($showKeys); $i++) {
						$this->out($i + 1 . ". " . $showKeys[$i]);
					}
					$foreignKey = $this->in('What is the foreignKey? Choose a number.');
					if (intval($foreignKey) > 0 && intval($foreignKey) <= $i ) {
						$foreignKey = $showKeys[intval($foreignKey) - 1];
					}
				}
				if(!isset($foreignKey)) {
					$foreignKey = $this->in('What is the foreignKey? Specify your own.', null, $suggestedForeignKey);
				}
				if($assocType == '4') {
					$associationForeignKey = $this->in('What is the associationForeignKey?', null, $this->__modelKey($currentModelName));
					$joinTable = $this->in('What is the joinTable?');
				}
				$associations[$assocs[$assocType]] = array_values($associations[$assocs[$assocType]]);
				$count = count($associations[$assocs[$assocType]]);
				$i = ($count > 0) ? $count : 0;
				$associations[$assocs[$assocType]][$i]['alias'] = $associationName;
				$associations[$assocs[$assocType]][$i]['className'] = $className;
				$associations[$assocs[$assocType]][$i]['foreignKey'] = $foreignKey;
				if($assocType == '4') {
					$associations[$assocs[$assocType]][$i]['associationForeignKey'] = $associationForeignKey;
					$associations[$assocs[$assocType]][$i]['joinTable'] = $joinTable;
				}
				$wannaDoMoreAssoc = $this->in('Define another association?', array('y','n'), 'y');
			}
		}
		$this->out('');
		$this->hr();
		$this->out('The following model will be created:');
		$this->hr();
		$this->out("Model Name:    $currentModelName");
		$this->out("DB Connection: " . ($usingDefault ? 'default' : $useDbConfig));
		$this->out("DB Table:   " . $fullTableName);
		if($primaryKey != 'id') {
			$this->out("Primary Key:   " . $primaryKey);
		}
		$this->out("Validation:    " . print_r($validate, true));

		if(!empty($associations)) {
			$this->out("Associations:");

			if(count($associations['belongsTo'])) {
				for($i = 0; $i < count($associations['belongsTo']); $i++) {
					$this->out("            $currentModelName belongsTo {$associations['belongsTo'][$i]['alias']}");
				}
			}

			if(count($associations['hasOne'])) {
				for($i = 0; $i < count($associations['hasOne']); $i++) {
					$this->out("            $currentModelName hasOne	{$associations['hasOne'][$i]['alias']}");
				}
			}

			if(count($associations['hasMany'])) {
				for($i = 0; $i < count($associations['hasMany']); $i++) {
					$this->out("            $currentModelName hasMany   {$associations['hasMany'][$i]['alias']}");
				}
			}

			if(count($associations['hasAndBelongsToMany'])) {
				for($i = 0; $i < count($associations['hasAndBelongsToMany']); $i++) {
					$this->out("            $currentModelName hasAndBelongsToMany {$associations['hasAndBelongsToMany'][$i]['alias']}");
				}
			}
		}
		$this->hr();
		$looksGood = $this->in('Look okay?', array('y','n'), 'y');

		if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
			if ($useTable == Inflector::tableize($currentModelName)) {
				// set it to null...
				// putting $useTable in the model
				// is unnecessary.
				$useTable = null;
			}
			$this->bakeModel($currentModelName, $useDbConfig, $useTable, $primaryKey, $validate, $associations);

			if ($this->doUnitTest()) {
				$this->bakeUnitTest('model', $currentModelName);
			}
		} else {
			$this->out('Bake Aborted.');
		}
	}
/**
 * Action to create a View.
 *
 */
	function doView() {
		$this->hr();
		$this->out('View Bake:');
		$this->hr();
		$uses = array();
		$wannaUseSession = 'y';
		$wannaDoScaffold = 'y';


		$useDbConfig = 'default';
		$this->__doList($useDbConfig, 'Controllers');

		$enteredController = '';

		while ($enteredController == '') {
			$enteredController = $this->in('Enter a number from the list above, or type in the name of another controller.');

			if ($enteredController == '' || intval($enteredController) > count($this->__controllerNames)) {
				$this->out('Error:');
				$this->out("The Controller name you supplied was empty, or the number \nyou selected was not an option. Please try again.");
				$enteredController = '';
			}
		}

		if (intval($enteredController) > 0 && intval($enteredController) <= count($this->__controllerNames) ) {
			$controllerName = $this->__controllerNames[intval($enteredController) - 1];
		} else {
			$controllerName = Inflector::camelize($enteredController);
		}

		$controllerPath = low(Inflector::underscore($controllerName));

		$doItInteractive = $this->in("Would you like bake to build your views interactively?\nWarning: Choosing no will overwrite {$controllerClassName} views if it exist.", array('y','n'), 'y');

		if (low($doItInteractive) == 'y' || low($doItInteractive) == 'yes') {
			$this->interactive = true;
			$wannaDoScaffold = $this->in("Would you like to create some scaffolded views (index, add, view, edit) for this controller?\nNOTE: Before doing so, you'll need to create your controller and model classes (including associated models).", array('y','n'), 'n');
		}

		$admin = null;
		$admin_url = null;
		if (low($wannaDoScaffold) == 'y' || low($wannaDoScaffold) == 'yes') {
			$wannaDoAdmin = $this->in("Would you like to create the views for admin routing?", array('y','n'), 'y');
		}

		if ((low($wannaDoAdmin) == 'y' || low($wannaDoAdmin) == 'yes')) {
			require(CONFIGS.'core.php');
			if(defined('CAKE_ADMIN')) {
				$admin = CAKE_ADMIN . '_';
				$admin_url = '/'.CAKE_ADMIN;
			} else {
				$adminRoute = '';
				$this->out('You need to enable CAKE_ADMIN in /app/config/core.php to use admin routing.');
				$this->out('What would you like the admin route to be?');
				$this->out('Example: www.example.com/admin/controller');
				while ($adminRoute == '') {
					$adminRoute = $this->in("What would you like the admin route to be?", null, 'admin');
				}
				if($this->__addAdminRoute($adminRoute) !== true){
					$this->out('Unable to write to /app/config/core.php.');
					$this->out('You need to enable CAKE_ADMIN in /app/config/core.php to use admin routing.');
					exit();
				} else {
					$admin = $adminRoute . '_';
					$admin_url = '/'.$adminRoute;
				}
			}
		}
		if (low($wannaDoScaffold) == 'y' || low($wannaDoScaffold) == 'yes') {
			$file = CONTROLLERS . $controllerPath . '_controller.php';

			if(!file_exists($file)) {
				$shortPath = str_replace(ROOT, null, $file);
				$shortPath = str_replace('../', '', $shortPath);
				$shortPath = str_replace('//', '/', $shortPath);
				$this->out('');
				$this->out("The file '$shortPath' could not be found.\nIn order to scaffold, you'll need to first create the controller. ");
				$this->out('');
				die();
			} else {
				uses('controller'.DS.'controller');
				loadController($controllerName);
				//loadModels();
				if($admin) {
					$this->__bakeViews($controllerName, $controllerPath, $admin, $admin_url);
				}
				$this->__bakeViews($controllerName, $controllerPath, null, null);

				$this->hr();
				$this->out('');
				$this->out('View Scaffolding Complete.'."\n");
			}
		} else {
			$actionName = '';

			while ($actionName == '') {
				$actionName = $this->in('Action Name? (use camelCased function name)');

				if ($actionName == '') {
					$this->out('The action name you supplied was empty. Please try again.');
				}
			}
			$this->out('');
			$this->hr();
			$this->out('The following view will be created:');
			$this->hr();
			$this->out("Controller Name: $controllerName");
			$this->out("Action Name:     $actionName");
			$this->out("Path:            app/views/" . $controllerPath . DS . Inflector::underscore($actionName) . '.ctp');
			$this->hr();
			$looksGood = $this->in('Look okay?', array('y','n'), 'y');

			if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
				$this->bakeView($controllerName, $actionName);
			} else {
				$this->out('Bake Aborted.');
			}
		}
	}

	function __bakeViews($controllerName, $controllerPath, $admin= null, $admin_url = null) {
		$controllerClassName = $controllerName.'Controller';
		$controllerObj = & new $controllerClassName();

		if(!in_array('Html', $controllerObj->helpers)) {
			$controllerObj->helpers[] = 'Html';
		}
		if(!in_array('Form', $controllerObj->helpers)) {
			$controllerObj->helpers[] = 'Form';
		}

		$controllerObj->constructClasses();
		$currentModelName = $controllerObj->modelClass;
		$this->__modelClass = $currentModelName;
		$modelKey = $controllerObj->modelKey;
		$modelObj =& ClassRegistry::getObject($modelKey);
		$singularName = $this->__singularName($currentModelName);
		$pluralName = $this->__pluralName($currentModelName);
		$singularHumanName = $this->__singularHumanName($currentModelName);
		$pluralHumanName = $this->__pluralHumanName($controllerName);

		$fieldNames = $controllerObj->generateFieldNames(null, false);

		//-------------------------[INDEX]-------------------------//
		$indexView = null;
		$indexView .= "<div class=\"{$pluralName}\">\n";
		$indexView .= "<h2>List " . $pluralHumanName . "</h2>\n\n";
		$indexView .= "<table cellpadding=\"0\" cellspacing=\"0\">\n";
		$indexView .= "\t<tr>\n";
		foreach ($fieldNames as $fieldName) {
			$indexView .= "\t\t<th><?php echo \$paginator->sort('{$fieldName['name']}');?></th>\n";
		}
		$indexView .= "\t\t<th>Actions</th>\n";
		$indexView .= "\t</tr>\n";
		$indexView .= "<?php foreach (\${$pluralName} as \${$singularName}): ?>\n";
		$indexView .= "\t<tr>\n";
		$count = 0;
		foreach($fieldNames as $field => $value) {
			if(isset($value['foreignKey'])) {
				$otherModelName = $this->__modelName($value['model']);
				$otherModelKey = Inflector::underscore($value['modelKey']);
				$otherModelObj =& ClassRegistry::getObject($otherModelKey);
				$otherControllerName = $this->__controllerName($value['modelKey']);
				$otherControllerPath = $this->__controllerPath($otherControllerName);
				if(is_object($otherModelObj)) {
					$displayField = $otherModelObj->getDisplayField();
					$indexView .= "\t\t<td><?php echo \$html->link(\$".$singularName."['{$otherModelName}']['{$displayField}'], array('controller'=> '{$otherControllerPath}', 'action'=>'view', \$".$singularName."['{$otherModelName}']['{$otherModelObj->primaryKey}'])); ?></td>\n";
				} else {
					$indexView .= "\t\t<td><?php echo \$".$singularName."['{$modelObj->name}']['{$field}']; ?></td>\n";
				}
				$count++;
			} else {
				$indexView .= "\t\t<td><?php echo \$".$singularName."['{$modelObj->name}']['{$field}']; ?></td>\n";
			}
		}
		$indexView .= "\t\t<td class=\"actions\">\n";
		$indexView .= "\t\t\t<?php echo \$html->link('View', array('action'=>'view', \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}'])); ?>\n";
		$indexView .= "\t\t\t<?php echo \$html->link('Edit', array('action'=>'edit', \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}'])); ?>\n";
		$indexView .= "\t\t\t<?php echo \$html->link('Delete', array('action'=>'delete', \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}']), null, 'Are you sure you want to delete #' . \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}']); ?>\n";
		$indexView .= "\t\t</td>\n";
		$indexView .= "\t</tr>\n";
		$indexView .= "<?php endforeach; ?>\n";
		$indexView .= "</table>\n\n";
		$indexView .= "</div>\n";
		$indexView .= "<div class=\"paging\">\n";
		$indexView .= "<?php echo \$paginator->prev('<< previous', array(), null, array('class'=>'disabled'));?>\n";
		$indexView .= "|\n";
		$indexView .= "<?php echo \$paginator->next('next >>', array(), null, array('class'=>'disabled'));?>\n";
		$indexView .= "</div>\n";
		$indexView .= "<div class=\"actions\">\n";
		$indexView .= "\t<ul>\n";
		$indexView .= "\t\t<li><?php echo \$html->link('New {$singularHumanName}', array('action'=>'add')); ?></li>\n";
		$indexView .= "\t</ul>\n";
		$indexView .= "</div>";

		//-------------------------[VIEW]-------------------------//
		$viewView = null;
		$viewView .= "<div class=\"{$singularName}\">\n";
		$viewView .= "<h2>View " . $singularHumanName . "</h2>\n\n";
		$viewView .= "\t<dl>\n";
		$count = 0;
		foreach($fieldNames as $field => $value) {
			$viewView .= "\t\t<dt>" . $value['label'] . "</dt>\n";
			if(isset($value['foreignKey'])) {
				$otherModelName = $this->__modelName($value['model']);
				$otherModelKey = Inflector::underscore($value['modelKey']);
				$otherModelObj =& ClassRegistry::getObject($value['modelKey']);
				$otherControllerName = $this->__controllerName($value['modelKey']);
				$otherControllerPath = $this->__controllerPath($otherControllerName);
				$displayField = $otherModelObj->getDisplayField();
				$viewView .= "\t\t<dd>&nbsp;<?php echo \$html->link(\$".$singularName."['{$otherModelName}']['{$displayField}'], array('controller'=> '{$otherControllerPath}', 'action'=>'view', \$".$singularName."['{$otherModelName}']['{$otherModelObj->primaryKey}'])); ?></dd>\n";
				$count++;
			} else {
				$viewView .= "\t\t<dd>&nbsp;<?php echo \$".$singularName."['{$modelObj->name}']['{$field}']?></dd>\n";
			}
		}
		$viewView .= "\t</dl>\n";
		$viewView .= "</div>\n";
		$viewView .= "<div class=\"actions\">\n";
		$viewView .= "\t<ul>\n";
		$viewView .= "\t\t<li><?php echo \$html->link('Edit " . $singularHumanName . "',   array('action'=>'edit', \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}'])); ?> </li>\n";
		$viewView .= "\t\t<li><?php echo \$html->link('Delete " . $singularHumanName . "', array('action'=>'delete', \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}']), null, 'Are you sure you want to delete #' . \$".$singularName."['{$modelObj->name}']['{$modelObj->primaryKey}'] . '?'); ?> </li>\n";
		$viewView .= "\t\t<li><?php echo \$html->link('List " . $pluralHumanName ."', array('action'=>'index')); ?> </li>\n";
		$viewView .= "\t\t<li><?php echo \$html->link('New " . $singularHumanName . "',	array('action'=>'add')); ?> </li>\n";
		foreach( $fieldNames as $field => $value ) {
			if( isset( $value['foreignKey'] ) ) {
				$otherModelName = $this->__modelName($value['modelKey']);
				if($otherModelName != $currentModelName) {
					$otherControllerName = $this->__controllerName($otherModelName);
					$otherControllerPath = $this->__controllerPath($otherControllerName);
					$otherSingularHumanName = $this->__singularHumanName($value['controller']);
					$otherPluralHumanName = $this->__pluralHumanName($value['controller']);
					$viewView .= "\t\t<li><?php echo \$html->link('List " . $otherSingularHumanName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'index')); ?> </li>\n";
					$viewView .= "\t\t<li><?php echo \$html->link('New " . $otherPluralHumanName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'add')); ?> </li>\n";
				}
			}
		}
		$viewView .= "\t</ul>\n\n";
		$viewView .= "</div>\n";

		foreach ($modelObj->hasOne as $associationName => $relation) {
			$new = true;
			$otherModelName = $this->__modelName($relation['className']);
			$otherControllerName = $this->__controllerName($otherModelName);
			$otherControllerPath = $this->__controllerPath($otherControllerName);
			$otherSingularName = $this->__singularName($associationName);
			$otherPluralHumanName = $this->__pluralHumanName($associationName);
			$otherSingularHumanName = $this->__singularHumanName($associationName);
			$otherModelKey = Inflector::underscore($relation['className']);
			$otherModelObj =& ClassRegistry::getObject($otherModelKey);

			$viewView .= "<div class=\"related\">\n";
			$viewView .= "<h3>Related " . $otherPluralHumanName . "</h3>\n";
			$viewView .= "<?php if(!empty(\${$singularName}['{$associationName}'])): ?>\n";
			$viewView .= "\t<dl>\n";
			foreach($otherModelObj->_tableInfo->value as $column) {
				$viewView .= "\t\t<dt>".Inflector::humanize($column['name'])."</dt>\n";
				$viewView .= "\t\t<dd>&nbsp;<?php echo \${$singularName}['{$associationName}']['{$column['name']}'] ?></dd>\n";
			}
			$viewView .= "\t</dl>\n";
			$viewView .= "<?php endif; ?>\n";
			$viewView .= "\t<div class=\"actions\">\n";
			$viewView .= "\t\t<ul>\n";
			$viewView .= "\t\t\t<li><?php echo \$html->link('Edit " . $otherSingularHumanName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'edit', \$".$singularName."['{$associationName}']['" . $modelObj->{$otherModelName}->primaryKey . "']));?></li>\n";
			$viewView .= "\t\t</ul>\n";
			$viewView .= "\t</div>\n";
			$viewView .= "</div>\n";
		}

		$relations = array_merge($modelObj->hasMany, $modelObj->hasAndBelongsToMany);
		foreach($relations as $associationName => $relation) {
			$otherModelName = $associationName;
			$otherControllerName = $this->__controllerName($relation['className']);
			$otherControllerPath = $this->__controllerPath($otherControllerName);
			$otherSingularName = $this->__singularName($associationName);
			$otherPluralHumanName = $this->__pluralHumanName($associationName);
			$otherSingularHumanName = $this->__singularHumanName($associationName);
			$otherModelKey = Inflector::underscore($relation['className']);
			$otherModelObj =& ClassRegistry::getObject($otherModelKey);

			$viewView .= "<div class=\"related\">\n";
			$viewView .= "<h3>Related " . $otherPluralHumanName . "</h3>\n";
			$viewView .= "<?php if(!empty(\${$singularName}['{$associationName}'])):?>\n";
			$viewView .= "<table cellpadding=\"0\" cellspacing=\"0\">\n";
			$viewView .= "\t<tr>\n";
			foreach($otherModelObj->_tableInfo->value as $column) {
				$viewView .= "\t\t<th>".Inflector::humanize($column['name'])."</th>\n";
			}
			$viewView .= "\t\t<th>Actions</th>\n";
			$viewView .= "\t</tr>\n";
			$viewView .= "<?php foreach(\${$singularName}['{$associationName}'] as \$".$otherSingularName."):?>\n";
			$viewView .= "\t<tr>\n";
			foreach($otherModelObj->_tableInfo->value as $column) {
			$viewView .= "\t\t<td><?php echo \${$otherSingularName}['{$column['name']}'];?></td>\n";
			}
			$viewView .= "\t\t<td class=\"actions\">\n";
			$viewView .= "\t\t\t<?php echo \$html->link('View', array('controller'=> '{$otherControllerPath}', 'action'=>'view', \$".$otherSingularName."['{$otherModelObj->primaryKey}'])); ?>\n";
			$viewView .= "\t\t\t<?php echo \$html->link('Edit', array('controller'=> '{$otherControllerPath}', 'action'=>'edit', \$".$otherSingularName."['{$otherModelObj->primaryKey}'])); ?>\n";
			$viewView .= "\t\t\t<?php echo \$html->link('Delete', array('controller'=> '{$otherControllerPath}', 'action'=>'delete', \$".$otherSingularName."['{$otherModelObj->primaryKey}']), null, 'Are you sure you want to delete #' . \$".$otherSingularName."['{$otherModelObj->primaryKey}'] . '?'); ?>\n";
			$viewView .= "\t\t</td>\n";
			$viewView .= "\t</tr>\n";
			$viewView .= "<?php endforeach; ?>\n";
			$viewView .= "</table>\n";
			$viewView .= "<?php endif; ?>\n\n";
			$viewView .= "\t<div class=\"actions\">\n";
			$viewView .= "\t\t<ul>\n";
			$viewView .= "\t\t\t<li><?php echo \$html->link('New " . $otherSingularHumanName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'add'));?> </li>\n";
			$viewView .= "\t\t</ul>\n";
			$viewView .= "\t</div>\n";
			$viewView .= "</div>\n";
		}
		$fields = $controllerObj->generateFieldNames(null, true);
		//-------------------------[EDIT]-------------------------//
		$editView = null;
		$editView .= "<div class=\"".$singularName."\">\n";
		$editView .= "<h2>Edit " . $singularHumanName . "</h2>\n";
		$editView .= "\t<?php echo \$form->create('{$currentModelName}');?>\n";
		$editView .= $this->inputs($fields);
		$editView .= "\t\t<?php echo \$form->submit('Update');?>\n";
		$editView .= "\t</form>\n";
		$editView .= "</div>\n";
		$editView .= "<div class=\"actions\">\n";
		$editView .= "\t<ul>\n";
		$editView .= "\t\t<li><?php echo \$html->link('Delete', array('action'=>'delete', \$html->tagValue('{$modelObj->name}/{$modelObj->primaryKey}')), null, 'Are you sure you want to delete #' . \$html->tagValue('{$modelObj->name}/{$modelObj->primaryKey}')); ?>\n";
		$editView .= "\t\t<li><?php echo \$html->link('List {$pluralHumanName}', array('action'=>'index')); ?></li>\n";
		foreach ($modelObj->belongsTo as $associationName => $relation) {
			$otherModelName = $this->__modelName($relation['className']);
			if($otherModelName != $currentModelName) {
				$otherControllerName = $this->__controllerName($otherModelName);
				$otherControllerPath = $this->__controllerPath($otherControllerName);
				$otherSingularName = $this->__singularName($associationName);
				$otherPluralName = $this->__pluralHumanName($associationName);
				$editView .= "\t\t<li><?php echo \$html->link('View " . $otherPluralName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'view')); ?></li>\n";
				$editView .= "\t\t<li><?php echo \$html->link('Add " . $otherPluralName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'add')); ?></li>\n";
			}
		}
		$editView .= "\t</ul>\n";
		$editView .= "</div>\n";
		//-------------------------[ADD]-------------------------//
		unset($fields[$modelObj->primaryKey]);
		$addView = null;
		$addView .= "<div class=\"".low($singularName)."\">\n";
		$addView .= "<h2>New " . $singularHumanName . "</h2>\n";
		$addView .= "\t<?php echo \$form->create('{$currentModelName}');?>\n";
		$addView .= $this->inputs($fields);
		$addView .= "\t\t<?php echo \$form->submit('Add');?>\n";
		$addView .= "\t</form>\n";
		$addView .= "</div>\n";
		$addView .= "<div class=\"actions\">\n";
		$addView .= "\t<ul>\n";
		$addView .= "\t\t<li><?php echo \$html->link('List {$pluralHumanName}', array('action'=>'index')); ?></li>\n";
		foreach ($modelObj->belongsTo as $associationName => $relation) {
			$otherModelName = $this->__modelName($relation['className']);
			if($otherModelName != $currentModelName) {
				$otherControllerName = $this->__controllerName($otherModelName);
				$otherControllerPath = $this->__controllerPath($otherControllerName);
				$otherSingularName = $this->__singularName($associationName);
				$otherPluralName = $this->__pluralHumanName($associationName);
				$addView .= "\t\t<li><?php echo \$html->link('View " . $otherPluralName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'view'));?></li>\n";
				$addView .= "\t\t<li><?php echo \$html->link('Add " . $otherPluralName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'add')); ?></li>\n";
			}
		}
		$addView .= "\t</ul>\n";
		$addView .= "</div>\n";

		//------------------------------------------------------------------------------------//

		if(!file_exists(VIEWS.$controllerPath)) {
			mkdir(VIEWS.$controllerPath);
		}
		$filename = VIEWS . $controllerPath . DS .  $admin . 'index.ctp';
		$this->createFile($filename, $indexView);
		$filename = VIEWS . $controllerPath . DS . $admin . 'view.ctp';
		$this->createFile($filename, $viewView);
		$filename = VIEWS . $controllerPath . DS . $admin . 'add.ctp';
		$this->createFile($filename, $addView);
		$filename = VIEWS . $controllerPath . DS . $admin . 'edit.ctp';
		$this->createFile($filename, $editView);
	}

/**
 * returns the fields to be display in the baked forms.
 *
 * @access private
 * @param array $fields
 */
	function inputs($fields = array()) {

		foreach($fields as $name => $options) {
			if(isset($options['tagName'])){
				$tag = explode('/', $options['tagName']);
				$tagName = $tag[1];
				unset($options['tagName']);
			}
			$formOptions = array();

			if(isset($options['type'])){
				$type = $options['type'];
				unset($options['type']);
				//$formOptions['type'] = "'type' => '{$type}'";
			}

			if(isset($options['class']) && $options['class'] == 'required'){
				$class = $options['class'];
				unset($options['class']);
				$formOptions['class'] = "'class' => '{$class}'";
			}

			if(isset($options['options'])){
				unset($formOptions['type']);
				$fieldOptions = $this->__pluralName($options['model']);
				unset($options['options']);
				$formOptions['options'] = "'options' => \${$fieldOptions}";
				if(isset($options['multiple'])){
					$formOptions['multiple'] = "'multiple' => 'multiple'";
					$tagName = $tagName.'/'.$tagName;
				}
			}
			if(isset($options['size'])){
				$size = $options['size'];
				unset($options['size']);
				//$formOptions['size'] = "'size' => '{$size}'";
			}
			if(isset($options['cols'])){
				$cols = $options['cols'];
				unset($options['cols']);
				//$formOptions['cols'] = "'cols' => '{$cols}'";
			}
			if(isset($options['rows'])){
				$rows = $options['rows'];
				unset($options['rows']);
				//$formOptions['rows'] = "'rows' => '{$rows}'";
			}


			if(!empty($formOptions)) {
				$formOptions = ", array(".join(', ', $formOptions).")";
			} else {
				$formOptions = null;
			}

			$displayFields .= "\t\t<?php echo \$form->input('{$tagName}'{$formOptions});?>\n";
		}
		return $displayFields;
	}

/**
 * Action to create a Controller.
 *
 */
	function doController() {
		$this->hr();
		$this->out('Controller Bake:');
		$this->hr();
		$uses = array();
		$helpers = array();
		$components = array();
		$wannaUseSession = 'y';
		$wannaDoScaffolding = 'y';

		$useDbConfig = 'default';
		$this->__doList($useDbConfig, 'Controllers');

		$enteredController = '';

		while ($enteredController == '') {
			$enteredController = $this->in('Enter a number from the list above, or type in the name of another controller.');

			if ($enteredController == '' || intval($enteredController) > count($this->__controllerNames)) {
				$this->out('Error:');
				$this->out("The Controller name you supplied was empty, or the number \nyou selected was not an option. Please try again.");
				$enteredController = '';
			}
		}

		if (intval($enteredController) > 0 && intval($enteredController) <= count($this->__controllerNames) ) {
			$controllerName = $this->__controllerNames[intval($enteredController) - 1];
		} else {
			$controllerName = Inflector::camelize($enteredController);
		}

		$controllerPath = low(Inflector::underscore($controllerName));

		$doItInteractive = $this->in("Would you like bake to build your controller interactively?\nWarning: Choosing no will overwrite {$controllerClassName} controller if it exist.", array('y','n'), 'y');

		if (low($doItInteractive) == 'y' || low($doItInteractive) == 'yes') {
			$this->interactive = true;

			$wannaUseScaffold = $this->in("Would you like to use scaffolding?", array('y','n'), 'y');

			if (low($wannaUseScaffold) == 'n' || low($wannaUseScaffold) == 'no') {

				$wannaDoScaffolding = $this->in("Would you like to include some basic class methods (index(), add(), view(), edit())?", array('y','n'), 'n');

				if (low($wannaDoScaffolding) == 'y' || low($wannaDoScaffolding) == 'yes') {
					$wannaDoAdmin = $this->in("Would you like to create the methods for admin routing?", array('y','n'), 'n');
				}

				$wannaDoUses = $this->in("Would you like this controller to use other models besides '" . $this->__modelName($controllerName) .  "'?", array('y','n'), 'n');

				if (low($wannaDoUses) == 'y' || low($wannaDoUses) == 'yes') {
					$usesList = $this->in("Please provide a comma separated list of the classnames of other models you'd like to use.\nExample: 'Author, Article, Book'");
					$usesListTrimmed = str_replace(' ', '', $usesList);
					$uses = explode(',', $usesListTrimmed);
				}
				$wannaDoHelpers = $this->in("Would you like this controller to use other helpers besides HtmlHelper and FormHelper?", array('y','n'), 'n');

				if (low($wannaDoHelpers) == 'y' || low($wannaDoHelpers) == 'yes') {
					$helpersList = $this->in("Please provide a comma separated list of the other helper names you'd like to use.\nExample: 'Ajax, Javascript, Time'");
					$helpersListTrimmed = str_replace(' ', '', $helpersList);
					$helpers = explode(',', $helpersListTrimmed);
				}
				$wannaDoComponents = $this->in("Would you like this controller to use any components?", array('y','n'), 'n');

				if (low($wannaDoComponents) == 'y' || low($wannaDoComponents) == 'yes') {
					$componentsList = $this->in("Please provide a comma separated list of the component names you'd like to use.\nExample: 'Acl, MyNiftyHelper'");
					$componentsListTrimmed = str_replace(' ', '', $componentsList);
					$components = explode(',', $componentsListTrimmed);
				}

				$wannaUseSession = $this->in("Would you like to use Sessions?", array('y','n'), 'y');
			} else {
				$wannaDoScaffolding = 'n';
			}
		} else {
			$wannaDoScaffolding = $this->in("Would you like to include some basic class methods (index(), add(), view(), edit())?", array('y','n'), 'y');

			if (low($wannaDoScaffolding) == 'y' || low($wannaDoScaffolding) == 'yes') {
				$wannaDoAdmin = $this->in("Would you like to create the methods for admin routing?", array('y','n'), 'y');
			}
		}

		$admin = null;
		$admin_url = null;
		if ((low($wannaDoAdmin) == 'y' || low($wannaDoAdmin) == 'yes')) {
			require(CONFIGS.'core.php');
			if(defined('CAKE_ADMIN')) {
				$admin = CAKE_ADMIN.'_';
				$admin_url = '/'.CAKE_ADMIN;
			} else {
				$adminRoute = '';
				$this->out('You need to enable CAKE_ADMIN in /app/config/core.php to use admin routing.');
				$this->out('What would you like the admin route to be?');
				$this->out('Example: www.example.com/admin/controller');
				while ($adminRoute == '') {
					$adminRoute = $this->in("What would you like the admin route to be?", null, 'admin');
				}
				if($this->__addAdminRoute($adminRoute) !== true){
					$this->out('Unable to write to /app/config/core.php.');
					$this->out('You need to enable CAKE_ADMIN in /app/config/core.php to use admin routing.');
					exit();
				} else {
					$admin = $adminRoute . '_';
					$admin_url = '/'.$adminRoute;
				}
			}
		}

		if (low($wannaDoScaffolding) == 'y' || low($wannaDoScaffolding) == 'yes') {
			//loadModels();
			$actions = $this->__bakeActions($controllerName, null, null, $wannaUseSession);
			if($admin) {
				$actions .= $this->__bakeActions($controllerName, $admin, $admin_url, $wannaUseSession);
			}
		}

		if($this->interactive === true) {
			$this->out('');
			$this->hr();
			$this->out('The following controller will be created:');
			$this->hr();
			$this->out("Controller Name:	$controllerName");
			if (low($wannaUseScaffold) == 'y' || low($wannaUseScaffold) == 'yes') {
				$this->out("			var \$scaffold;");
			}
			if(count($uses)) {
				$this->out("Uses:            ", false);

				foreach($uses as $use) {
					if ($use != $uses[count($uses) - 1]) {
						$this->out(ucfirst($use) . ", ", false);
					} else {
						$this->out(ucfirst($use));
					}
				}
			}

			if(count($helpers)) {
				$this->out("Helpers:			", false);

				foreach($helpers as $help) {
					if ($help != $helpers[count($helpers) - 1]) {
						$this->out(ucfirst($help) . ", ", false);
					} else {
						$this->out(ucfirst($help));
					}
				}
			}

			if(count($components)) {
				$this->out("Components:            ", false);

				foreach($components as $comp) {
					if ($comp != $components[count($components) - 1]) {
						$this->out(ucfirst($comp) . ", ", false);
					} else {
						$this->out(ucfirst($comp));
					}
				}
			}
			$this->hr();
			$looksGood = $this->in('Look okay?', array('y','n'), 'y');

			if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
				$this->bakeController($controllerName, $uses, $helpers, $components, $actions, $wannaUseScaffold);

				if ($this->doUnitTest()) {
					$this->bakeUnitTest('controller', $controllerName);
				}
			} else {
				$this->out('Bake Aborted.');
			}
		} else {
			$this->bakeController($controllerName, $uses, $helpers, $components, $actions, $wannaUseScaffold);
			if ($this->doUnitTest()) {
				$this->bakeUnitTest('controller', $controllerName);
			}
			exit();
		}
	}

	function __bakeActions($controllerName, $admin = null, $admin_url = null, $wannaUseSession = 'y') {
		$currentModelName = $this->__modelName($controllerName);
		if(!loadModel($currentModelName)) {
			$this->out('You must have a model for this class to build scaffold methods. Please try again.');
			exit;
		}
		$modelObj =& new $currentModelName();
		$controllerPath = $this->__controllerPath($controllerName);
		$pluralName = $this->__pluralName($currentModelName);
		$singularName = $this->__singularName($currentModelName);
		$singularHumanName = $this->__singularHumanName($currentModelName);
		$pluralHumanName = $this->__pluralHumanName($controllerName);
		$actions .= "\n";
		$actions .= "\tfunction {$admin}index() {\n";
		$actions .= "\t\t\$this->{$currentModelName}->recursive = 0;\n";
		$actions .= "\t\t\$this->set('{$pluralName}', \$this->paginate());\n";
		$actions .= "\t}\n";
		$actions .= "\n";
		$actions .= "\tfunction {$admin}view(\$id = null) {\n";
		$actions .= "\t\tif(!\$id) {\n";
		if (low($wannaUseSession) == 'y' || low($wannaUseSession) == 'yes') {
		$actions .= "\t\t\t\$this->Session->setFlash('Invalid {$singularHumanName}.');\n";
		$actions .= "\t\t\t\$this->redirect(array('action'=>'index'), null, true);\n";
		} else {
		$actions .= "\t\t\t\$this->flash('Invalid {$singularHumanName}', array('action'=>'index'));\n";
		}
		$actions .= "\t\t}\n";
		$actions .= "\t\t\$this->set('".$singularName."', \$this->{$currentModelName}->read(null, \$id));\n";
		$actions .= "\t}\n";
		$actions .= "\n";

		/* ADD ACTION */
		$compact = array();
		$actions .= "\tfunction {$admin}add() {\n";
		$actions .= "\t\tif(!empty(\$this->data)) {\n";
		$actions .= "\t\t\t\$this->cleanUpFields();\n";
		$actions .= "\t\t\t\$this->{$currentModelName}->create();\n";
		$actions .= "\t\t\tif(\$this->{$currentModelName}->save(\$this->data)) {\n";
		if (low($wannaUseSession) == 'y' || low($wannaUseSession) == 'yes') {
		$actions .= "\t\t\t\t\$this->Session->setFlash('The ".$singularHumanName." has been saved');\n";
		$actions .= "\t\t\t\t\$this->redirect(array('action'=>'index'), null, true);\n";
		} else {
		$actions .= "\t\t\t\t\$this->flash('{$currentModelName} saved.', array('action'=>'index'));\n";
		$actions .= "\t\t\t\texit();\n";
		}
		$actions .= "\t\t\t} else {\n";
		if (low($wannaUseSession) == 'y' || low($wannaUseSession) == 'yes') {
			$actions .= "\t\t\t\t\$this->Session->setFlash('The {$singularHumanName} could not be saved. Please, try again.');\n";
		}
		$actions .= "\t\t\t}\n";
		$actions .= "\t\t}\n";
		foreach($modelObj->hasAndBelongsToMany as $associationName => $relation) {
			if(!empty($associationName)) {
				$habtmModelName = $this->__modelName($associationName);
				$habtmSingularName = $this->__singularName($associationName);
				$habtmPluralName = $this->__pluralName($associationName);
				$actions .= "\t\t\${$habtmPluralName} = \$this->{$currentModelName}->{$habtmModelName}->generateList();\n";
				$compact[] = "'{$habtmPluralName}'";
			}
		}
		foreach($modelObj->belongsTo as $associationName => $relation) {
			if(!empty($associationName)) {
				$belongsToModelName = $this->__modelName($associationName);
				$belongsToPluralName = $this->__pluralName($associationName);
				$actions .= "\t\t\${$belongsToPluralName} = \$this->{$currentModelName}->{$belongsToModelName}->generateList();\n";
				$compact[] = "'{$belongsToPluralName}'";
			}
		}
		if(!empty($compact)) {
			$actions .= "\t\t\$this->set(compact(".join(', ', $compact)."));\n";
		}
		$actions .= "\t}\n";
		$actions .= "\n";

		/* EDIT ACTION */
		$compact = array();
		$actions .= "\tfunction {$admin}edit(\$id = null) {\n";
		$actions .= "\t\tif(!\$id && empty(\$this->data)) {\n";
		if (low($wannaUseSession) == 'y' || low($wannaUseSession) == 'yes') {
			$actions .= "\t\t\t\$this->Session->setFlash('Invalid {$singularHumanName}');\n";
			$actions .= "\t\t\t\$this->redirect(array('action'=>'index'), null, true);\n";
		} else {
			$actions .= "\t\t\t\$this->flash('Invalid {$singularHumanName}', array('action'=>'index'));\n";
			$actions .= "\t\t\texit();\n";
		}
		$actions .= "\t\t}\n";
		$actions .= "\t\tif(!empty(\$this->data)) {\n";
		$actions .= "\t\t\t\$this->cleanUpFields();\n";
		$actions .= "\t\t\tif(\$this->{$currentModelName}->save(\$this->data)) {\n";
		if (low($wannaUseSession) == 'y' || low($wannaUseSession) == 'yes') {
			$actions .= "\t\t\t\t\$this->Session->setFlash('The ".$singularHumanName." saved');\n";
			$actions .= "\t\t\t\t\$this->redirect(array('action'=>'index'), null, true);\n";
		} else {
			$actions .= "\t\t\t\t\$this->flash('The ".$singularHumanName." saved.', array('action'=>'index'));\n";
			$actions .= "\t\t\t\texit();\n";
		}
		$actions .= "\t\t\t} else {\n";
		if (low($wannaUseSession) == 'y' || low($wannaUseSession) == 'yes') {
			$actions .= "\t\t\t\t\$this->Session->setFlash('The {$singularHumanName} could not be saved. Please, try again.');\n";
		}
		$actions .= "\t\t\t}\n";
		$actions .= "\t\t}\n";
		$actions .= "\t\tif(empty(\$this->data)) {\n";
		$actions .= "\t\t\t\$this->data = \$this->{$currentModelName}->read(null, \$id);\n";
		$actions .= "\t\t}\n";

		foreach($modelObj->hasAndBelongsToMany as $associationName => $relation) {
			if(!empty($associationName)) {
				$habtmModelName = $this->__modelName($associationName);
				$habtmSingularName = $this->__singularName($associationName);
				$habtmPluralName = $this->__pluralName($associationName);
				$actions .= "\t\t\${$habtmPluralName} = \$this->{$currentModelName}->{$habtmModelName}->generateList();\n";
				$compact[] = "'{$habtmPluralName}'";
			}
		}
		foreach($modelObj->belongsTo as $associationName => $relation) {
			if(!empty($associationName)) {
				$belongsToModelName = $this->__modelName($associationName);
				$belongsToPluralName = $this->__pluralName($associationName);
				$actions .= "\t\t\${$belongsToPluralName} = \$this->{$currentModelName}->{$belongsToModelName}->generateList();\n";
				$compact[] = "'{$belongsToPluralName}'";
			}
		}
		if(!empty($compact)) {
			$actions .= "\t\t\$this->set(compact(".join(',', $compact)."));\n";
		}
		$actions .= "\t}\n";
		$actions .= "\n";
		$actions .= "\tfunction {$admin}delete(\$id = null) {\n";
		$actions .= "\t\tif(!\$id) {\n";
		if (low($wannaUseSession) == 'y' || low($wannaUseSession) == 'yes') {
		$actions .= "\t\t\t\$this->Session->setFlash('Invalid id for {$singularHumanName}');\n";
		$actions .= "\t\t\t\$this->redirect(array('action'=>'index'), null, true);\n";
		} else {
		$actions .= "\t\t\t\$this->flash('Invalid {$singularHumanName}', array('action'=>'index'));\n";
		}
		$actions .= "\t\t}\n";
		$actions .= "\t\tif(\$this->{$currentModelName}->del(\$id)) {\n";
		if (low($wannaUseSession) == 'y' || low($wannaUseSession) == 'yes') {
			$actions .= "\t\t\t\$this->Session->setFlash('".$singularHumanName." #'.\$id.' deleted');\n";
			$actions .= "\t\t\t\$this->redirect(array('action'=>'index'), null, true);\n";
		} else {
			$actions .= "\t\t\t\$this->flash('".$singularHumanName." #'.\$id.' deleted', array('action'=>'index'));\n";
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
		if (is_dir(VENDORS.'simpletest') || is_dir(ROOT.DS.APP_DIR.DS.'vendors'.DS.'simpletest')) {
			return true;
		}
		$unitTest = $this->in('Cake test suite not installed.  Do you want to bake unit test files anyway?', array('y','n'), 'y');
		$result = low($unitTest) == 'y' || low($unitTest) == 'yes';

		if ($result) {
			$this->out("\nYou can download the Cake test suite from http://cakeforge.org/projects/testsuite/", true);
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
		$out .= "class DATABASE_CONFIG {\n\n";
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
 * @param string $name
 * @param object $useDbConfig
 * @param string $useTable
 * @param string $primaryKey
 * @param array $validate
 * @param array $associations
 */
	function bakeModel($name, $useDbConfig = 'default', $useTable = null, $primaryKey = 'id', $validate=array(), $associations=array()) {
		$out = "<?php\n";
		$out .= "class {$name} extends AppModel {\n\n";
		$out .= "\tvar \$name = '{$name}';\n";

		if ($useDbConfig != 'default') {
			$out .= "\tvar \$useDbConfig = '$useDbConfig';\n";
		}

		if ($useTable != null) {
			$out .= "\tvar \$useTable = '$useTable';\n";
		}

		if ($primaryKey != 'id') {
			$out .= "\tvar \$primaryKey = '$primaryKey';\n";
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

		if(!empty($associations)) {
			$out.= "\t//The Associations below have been created with all possible keys, those that are not needed can be removed\n";
			if(!empty($associations['belongsTo'])) {
				$out .= "\tvar \$belongsTo = array(\n";

				for($i = 0; $i < count($associations['belongsTo']); $i++) {
					$out .= "\t\t\t'{$associations['belongsTo'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['belongsTo'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'foreignKey' => '{$associations['belongsTo'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'counterCache' => ''";
					$out .= "),\n";
				}
				$out .= "\t);\n\n";
			}

			if(!empty($associations['hasOne'])) {
				$out .= "\tvar \$hasOne = array(\n";

				for($i = 0; $i < count($associations['hasOne']); $i++) {
					$out .= "\t\t\t'{$associations['hasOne'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['hasOne'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'foreignKey' => '{$associations['hasOne'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'dependent' => ''";
					$out .= "),\n";
				}
				$out .= "\t);\n\n";
			}

			if(!empty($associations['hasMany'])) {
				$out .= "\tvar \$hasMany = array(\n";

				for($i = 0; $i < count($associations['hasMany']); $i++) {
					$out .= "\t\t\t'{$associations['hasMany'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['hasMany'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'foreignKey' => '{$associations['hasMany'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'limit' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'offset' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'dependent' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'exclusive' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'finderQuery' => '',\n";
					$out .= "\t\t\t\t\t\t\t\t'counterQuery' => ''";
					$out .= "),\n";
				}
				$out .= "\t);\n\n";
			}

			if(!empty($associations['hasAndBelongsToMany'])) {
				$out .= "\tvar \$hasAndBelongsToMany = array(\n";

				for($i = 0; $i < count($associations['hasAndBelongsToMany']); $i++) {
					$out .= "\t\t\t'{$associations['hasAndBelongsToMany'][$i]['alias']}' => ";
					$out .= "array('className' => '{$associations['hasAndBelongsToMany'][$i]['className']}',\n";
					$out .= "\t\t\t\t\t\t'joinTable' => '{$associations['hasAndBelongsToMany'][$i]['joinTable']}',\n";
					$out .= "\t\t\t\t\t\t'foreignKey' => '{$associations['hasAndBelongsToMany'][$i]['foreignKey']}',\n";
					$out .= "\t\t\t\t\t\t'associationForeignKey' => '{$associations['hasAndBelongsToMany'][$i]['associationForeignKey']}',\n";
					$out .= "\t\t\t\t\t\t'conditions' => '',\n";
					$out .= "\t\t\t\t\t\t'fields' => '',\n";
					$out .= "\t\t\t\t\t\t'order' => '',\n";
					$out .= "\t\t\t\t\t\t'limit' => '',\n";
					$out .= "\t\t\t\t\t\t'offset' => '',\n";
					$out .= "\t\t\t\t\t\t'unique' => '',\n";
					$out .= "\t\t\t\t\t\t'finderQuery' => '',\n";
					$out .= "\t\t\t\t\t\t'deleteQuery' => '',\n";
					$out .= "\t\t\t\t\t\t'insertQuery' => ''";
					$out .= "),\n";
				}
				$out .= "\t);\n\n";
			}
		}
		$out .= "}\n";
		$out .= "?>";
		$filename = MODELS.Inflector::underscore($name) . '.php';
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
		$out = "<h2>{$actionName}</h2>\n";
		$out .= $content;
		if(!file_exists(VIEWS.$this->__controllerPath($controllerName))) {
			mkdir(VIEWS.$this->__controllerPath($controllerName));
		}
		$filename = VIEWS . $this->__controllerPath($controllerName) . DS . Inflector::underscore($actionName) . '.ctp';
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
	function bakeController($controllerName, $uses, $helpers, $components, $actions = '', $wannaUseScaffold = 'y') {
		$out = "<?php\n";
		$out .= "class $controllerName" . "Controller extends AppController {\n\n";
		$out .= "\tvar \$name = '$controllerName';\n";
		if(low($wannaUseScaffold) == 'y' || low($wannaUseScaffold) == 'yes') {
		$out .= "\tvar \$scaffold;\n";
		} else {

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
				$out .= "loadModel('$className');\n\n";
				$out .= "class {$className}TestCase extends UnitTestCase {\n";
				$out .= "\tvar \$object = null;\n\n";
				$out .= "\tfunction setUp() {\n\t\t\$this->object = new {$className}();\n";
				$out .= "\t}\n\n\tfunction tearDown() {\n\t\tunset(\$this->object);\n\t}\n";
				$out .= "\n\t/*\n\tfunction testMe() {\n";
				$out .= "\t\t\$result = \$this->object->doSomething();\n";
				$out .= "\t\t\$expected = 1;\n";
				$out .= "\t\t\$this->assertEqual(\$result, \$expected);\n\t}\n\t*/\n}";
				$path = MODEL_TESTS;
				$filename = $this->__singularName($className).'.test.php';
			break;
			case 'controller':
				$out .= "loadController('$className');\n\n";
				$out .= "class {$className}ControllerTestCase extends UnitTestCase {\n";
				$out .= "\tvar \$object = null;\n\n";
				$out .= "\tfunction setUp() {\n\t\t\$this->object = new {$className}Controller();\n";
				$out .= "\t}\n\n\tfunction tearDown() {\n\t\tunset(\$this->object);\n\t}\n";
				$out .= "\n\t/*\n\tfunction testMe() {\n";
				$out .= "\t\t\$result = \$this->object->doSomething();\n";
				$out .= "\t\t\$expected = 1;\n";
				$out .= "\t\t\$this->assertEqual(\$result, \$expected);\n\t}\n\t*/\n}";
				$path = CONTROLLER_TESTS;
				$filename = $this->__pluralName($className).'_controller.test.php';
			break;
			default:
				$error = true;
			break;
		}
		$out .= "\n?>";

		if (!$error) {
			$this->out("Baking unit test for $className...");
			$path = explode(DS, $path);
			foreach($path as $i => $val) {
				if ($val == '' || $val == '../') {
					unset($path[$i]);
				}
			}
			$path = implode(DS, $path);
			$unixPath = DS;
			if (strpos(PHP_OS, 'WIN') === 0){
				$unixPath = null;
			}
			if (!is_dir($unixPath.$path)) {
				$create = $this->in("Unit test directory does not exist.  Create it?", array('y','n'), 'y');
				if (low($create) == 'y' || low($create) == 'yes') {
					$build = array();

					foreach(explode(DS, $path) as $i => $dir) {
						$build[] = $dir;
						if (!is_dir($unixPath.implode(DS, $build))) {
							mkdir($unixPath.implode(DS, $build));
						}
					}
				} else {
					return false;
				}
			}
			$this->createFile($unixPath.$path.DS.$filename, $out);
		}
	}
/**
 * Outputs usage text on the standard output.
 *
 */
	function help() {
		$this->out('CakePHP Bake:');
		$this->hr();
		$this->out('The Bake script generates controllers, views and models for your application.');
		$this->out('If run with no command line arguments, Bake guides the user through the class');
		$this->out('creation process. You can customize the generation process by telling Bake');
		$this->out('where different parts of your application are using command line arguments.');
		$this->out('');
		$this->hr('');
		$this->out('usage: php bake.php [command] [path...]');
		$this->out('');
		$this->out('commands:');
		$this->out('   -app [path...] Absolute path to Cake\'s app Folder.');
		$this->out('   -core [path...] Absolute path to Cake\'s cake Folder.');
		$this->out('   -help Shows this help message.');
		$this->out('   -project [path...]  Generates a new app folder in the path supplied.');
		$this->out('   -root [path...] Absolute path to Cake\'s \app\webroot Folder.');
		$this->out('');
	}
/**
 * Checks that given project path does not already exist, and
 * finds the app directory in it. Then it calls __buildDirLayout() with that information.
 *
 * @param string $projectPath
 */
	function project($projectPath = null) {
		if($projectPath != '') {
			while ($this->__checkPath($projectPath) === true && $this->__checkPath(CONFIGS) === true) {
				$response = $this->in('Bake -app in '.$projectPath, array('y','n'), 'y');
				if(low($response) == 'y') {
					$this->main();
					exit();
				} else {
					$projectPath = $this->in("What is the full path for this app including the app directory name?\nExample: ".ROOT.DS."myapp", null, ROOT.DS.'myapp');
				}
			}
		} else {
			while ($projectPath == '') {
				$projectPath = $this->in("What is the full path for this app including the app directory name?\nExample: ".ROOT.DS."myapp", null, ROOT.DS.'myapp');

				if ($projectPath == '') {
					$this->out('The directory path you supplied was empty. Please try again.');
				}
			}
		}
		while ($newPath != 'y' && ($this->__checkPath($projectPath) === true || $projectPath == '')) {
				$newPath = $this->in('Directory '.$projectPath.'  exists. Overwrite (y) or insert a new path', null, 'y');
				if($newPath != 'y') {
					$projectPath = $newPath;
				}
			while ($projectPath == '') {
				$projectPath = $this->in('The directory path you supplied was empty. Please try again.');
			}
		}
		$parentPath = explode(DS, $projectPath);
		$count = count($parentPath);
		$appName = $parentPath[$count - 1];
		if($appName == '') {
			$appName = $parentPath[$count - 2];
		}
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
				$skel = $this->in("What is the full path for the cake install app directory?\nExample: ", null, ROOT.'myapp'.DS);

				if ($skel == '') {
					$this->out('The directory path you supplied was empty. Please try again.');
				} else {
					while ($this->__checkPath($skel) === false) {
						$skel = $this->in('Directory path does not exist please choose another:');
					}
				}
			}
		}
		$this->out('');
		$this->hr();
		$this->out("Skel Directory: $skel");
		$this->out("Will be copied to:");
		$this->out("New App Directory: $projectPath");
		$this->hr();
		$looksGood = $this->in('Look okay?', array('y', 'n', 'q'), 'y');

		if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
			$verboseOuptut = $this->in('Do you want verbose output?', array('y', 'n'), 'n');
			$verbose = false;

			if (low($verboseOuptut) == 'y' || low($verboseOuptut) == 'yes') {
				$verbose = true;
			}
			$this->__copydirr($skel, $projectPath, 0755, $verbose);
			$this->hr();
			$this->out('Created: '.$projectPath);
			$this->hr();
			$this->out('Creating welcome page');
			$this->hr();
			$this->__defaultHome($projectPath, $appName);
			$this->out('Welcome page created');
			if($this->__generateHash() === true ){
				$this->out('Random hash key created for CAKE_SESSION_STRING');
			} else {
				$this->out('Unable to generate random hash for CAKE_SESSION_STRING, please change this yourself in ' . CONFIGS . 'core.php');
			}
			if(chmodr($projectPath.DS.'tmp', 0777) === false) {
				$this->out('Could not set permissions on '. $projectPath.DS.'tmp'.DS.'*');
				$this->out('You must manually check that these directories can be wrote to by the server');
			}
			return;
		} elseif (low($looksGood) == 'q' || low($looksGood) == 'quit') {
			$this->out('Bake Aborted.');
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
	function __copydirr($fromDir, $toDir, $chmod = 0755, $verbose = false) {
		$errors=array();
		$messages=array();

		uses('folder');
		$folder = new Folder($toDir, true, 0755);

		if (!is_writable($toDir)) {
			$errors[]='target '.$toDir.' is not writable';
		}

		if (!is_dir($fromDir)) {
			$errors[]='source '.$fromDir.' is not a directory';
		}

		if (!empty($errors)) {
			if ($verbose) {
				foreach($errors as $err) {
					$this->out('Error: '.$err);
				}
			}
			return false;
		}
		$exceptions=array('.','..','.svn');
		$handle = opendir($fromDir);

		while (false!==($item = readdir($handle))) {
			if (!in_array($item,$exceptions)) {
				$from = $folder->addPathElement($fromDir, $item);
				$to = $folder->addPathElement($toDir, $item);
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
					$this->__copydirr($from,$to,$chmod,$verbose);
				}
			}
		}
		closedir($handle);

		if ($verbose) {
			foreach($errors as $err) {
				$this->out('Error: '.$err);
			}
			foreach($messages as $msg) {
				$this->out($msg);
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
		function __generateHash(){
		$file = file_get_contents(CONFIGS.'core.php');
		if (preg_match('/([\\t\\x20]*define\\(\\\'CAKE_SESSION_STRING\\\',[\\t\\x20\'A-z0-9]*\\);)/', $file, $match)) {
			uses('Security');
			$string = Security::generateAuthKey();
			$result = str_replace($match[0], 'define(\'CAKE_SESSION_STRING\', \''.$string.'\');', $file);

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
		$this->out('');
		$this->out(' ___  __  _  _  ___  __  _  _  __      __   __  _  _  ___ ');
		$this->out('|    |__| |_/  |__  |__] |__| |__]    |__] |__| |_/  |__ ');
		$this->out('|___ |  | | \_ |___ |    |  | |       |__] |  | | \_ |___ ');
		$this->hr();
		$this->out('');
	}
/**
 * Writes a file with a default home page to the project.
 *
 * @param string $dir
 * @param string $app
 */
	function __defaultHome($dir, $app) {
		$path = $dir.DS.'views'.DS.'pages'.DS;
		include(CAKE_CORE_INCLUDE_PATH.DS.'cake'.DS.'scripts'.DS.'templates'.DS.'views'.DS.'home.ctp');
		$this->createFile($path.'home.ctp', $output);
	}
/**
 * creates the proper pluralize controller for the url
 *
 * @param string $name
 * @return string $name
 */
	function __controllerPath($name) {
		return low(Inflector::underscore($name));
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
		return Inflector::variable(Inflector::singularize($name));
	}
/**
 * creates the plural name for views.
 *
 * @param string $name
 * @return string $name
 */
	function __pluralName($name) {
		return Inflector::variable(Inflector::pluralize($name));
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
/**
 * outputs the a list of possible models or controllers from database
 *
 * @param string $useDbConfig
 * @param string $type = Models or Controllers
 * @return output
 */
	function __doList($useDbConfig = 'default', $type = 'Models') {
		$db =& ConnectionManager::getDataSource($useDbConfig);
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
		$this->__tables = $tables;
		$this->out('Possible '.$type.' based on your current database:');
		$this->__controllerNames = array();
		$this->__modelNames = array();
		$count = count($tables);
		for ($i = 0; $i < $count; $i++) {
			if(low($type) == 'controllers') {
				$this->__controllerNames[] = $this->__controllerName($this->__modelName($tables[$i]));
				$this->out($i + 1 . ". " . $this->__controllerNames[$i]);
			} else {
				$this->__modelNames[] = $this->__modelName($tables[$i]);
				$this->out($i + 1 . ". " . $this->__modelNames[$i]);
			}
		}
	}

}
?>