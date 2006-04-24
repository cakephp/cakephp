#!/usr/bin/php -q
<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.scripts.bake
 * @since        CakePHP v 0.10.0.1232
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */
ini_set('display_errors', '1');
ini_set('error_reporting', '7');

define ('DS', DIRECTORY_SEPARATOR);
define ('ROOT', dirname(dirname(dirname(__FILE__))).DS);
define ('APP_DIR', 'app');
define ('APP_PATH', 'app'.DS);
define ('DEBUG', 1);
define ('CORE_PATH', null);

require_once (ROOT.'cake'.DS.'basics.php');
require_once (ROOT.'cake'.DS.'config'.DS.'paths.php');
require_once (ROOT.'cake'.DS.'dispatcher.php');
require_once (CONFIGS.'core.php');

uses ('inflector');
uses ('model'.DS.'model');
require_once (ROOT.'cake'.DS.'app_model.php');
require_once (ROOT.'cake'.DS.'app_controller.php');
uses ('neat_array');
uses ('model'.DS.'connection_manager');
uses ('controller'.DS.'controller');
uses ('session');
uses ('configure');
uses ('security');
uses(DS.'controller'.DS.'scaffold');

$pattyCake = new Bake();

$pattyCake->main();

class Bake {
	
	var $stdin;
	var $stdout;
	var $stderr;

	function __construct() 
	{	
	  $this->stdin 	= fopen('php://stdin', 	'r');
      $this->stdout = fopen('php://stdout', 'w');
      $this->stderr = fopen('php://stderr', 'w');
	}
	
	function Bake()
	{
		return $this->__construct();
	}
	
	function main()
	{
		$this->stdout('');
		$this->stdout('____ ____ _  _ ____ ___  _  _ ___     ___  ____ _  _ ____ ');
		$this->stdout('|    |__| |_/  |___ |__] |__| |__]    |__] |__| |_/  |___ ');
		$this->stdout('|___ |  | | \_ |___ |    |  | |       |__] |  | | \_ |___ ');
		$this->hr();

		if(!file_exists(CONFIGS.'database.php'))
		{
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
		
		while ($invalidSelection) 
		{
		
			$classToBake = strtoupper($this->getInput('Please select a class to Bake:', array('M', 'V', 'C')));
		
			switch($classToBake)
			{
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
	
	/*---- ----*/
	
	function doDbConfig()
	{
		$this->hr();
		$this->stdout('Database Configuration Bake:');
		$this->hr();
		
		$driver = 'mysql';
		$connect = 'mysql_pconnect';
		
		$host = '';
		
		while ($host == '') 
		{
			$host = $this->getInput('What is the hostname for the database server?', null, 'localhost');
			
			if ($host == '')
			{
				$this->stdout('The host name you supplied was empty. Please supply a hostname.');
			}
		}
		
		$login = '';
		
		while ($login == '') 
		{
			$login = $this->getInput('What is the database username?');
			
			if ($login == '')
			{
				$this->stdout('The database username you supplied was empty. Please try again.');
			}
		}
		
		$password = '';
		
		while ($password == '') 
		{
			$password = $this->getInput('What is the database password?');
			
			if ($password == '') 
			{
				$this->stdout('The password you supplied was empty. Please try again.');
			}
		}
		
		$database = '';
		
		while ($database == '') 
		{
			$database = $this->getInput('What is the name of the database you will be using?');
			
			if ($database == '')
			{
				$this->stdout('The database name you supplied was empty. Please try again.');
			}
		}
		
		$this->stdout('');
		$this->hr();
		$this->stdout('The following database configuration will be created:');
		$this->hr();
		$this->stdout("Host:      $host");
		$this->stdout("User:      $login");
		$this->stdout("Pass:      " . str_repeat('*', strlen($password)));
		$this->stdout("Database:  $database");	
		$this->hr();
		
		$looksGood = $this->getInput('Look okay?', array('y', 'n'), 'y');
		
		if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes')
		{
			$this->bakeDbConfig($host, $login, $password, $database);
		}
		else
		{
			$this->stdout('Bake Aborted.');
		}
	}

	function doModel()
	{	
		$this->hr();
		$this->stdout('Model Bake:');
		$this->hr();
		
		$dbConnection = 'default';
		
		/*$usingDefault = $this->getInput('Will your model be using a database connection setting other than the default?');
		if (strtolower($usingDefault) == 'y' || strtolower($usingDefault) == 'yes')
		{
			$dbConnection = $this->getInput('Please provide the name of the connection you wish to use.');
		}*/
		
		$modelName = '';
		
		$db =& ConnectionManager::getDataSource($dbConnection);	
		$tables = $db->listSources();
		$inflect = new Inflector();
		
		$this->stdout('Possible models based on your current database:');
		
		for ($i = 0; $i < count($tables); $i++)
		{
			$this->stdout($i + 1 . ". " . $inflect->camelize($inflect->singularize($tables[$i])));
		}
		
		while ($modelName == '') 
		{
			$modelName = $this->getInput('Enter a number from the list above, or type in the name of another model.');
			
			if ($modelName == '' || intval($modelName) > $i)
			{
				$this->stdout('Error:');
				$this->stdout("The model name you supplied was empty, or the number \nyou selected was not an option. Please try again.");
				$modelName = '';
			}
		}
		
		if (intval($modelName) > 0 && intval($modelName) <= $i ) 
		{
			$modelClassName = $inflect->camelize($inflect->singularize($tables[$modelName - 1]));
			$modelTableName = $tables[intval($modelName) - 1];
		}
		else 
		{
			$modelClassName = $inflect->camelize($modelName);
			
			$this->stdout("\nGiven your model named '$modelClassName', Cake would expect a database table named '" . $inflect->pluralize($modelName) . "'.");
			$tableIsGood = $this->getInput('Is this correct?', array('y','n'), 'y');
			
			if (strtolower($tableIsGood) == 'n' || strtolower($tableIsGood) == 'no')
			{
				$modelTableName = $this->getInput('What is the name of the table (enter "null" to use NO table)?');
			}
		}
	
		$wannaDoValidation = $this->getInput('Would you like to supply validation criteria for the fields in your model?', array('y','n'), 'y');
		$validate = array();
		
		$tempModel = new Model(false, $modelTableName);
		$modelFields = $db->describe($tempModel);
		
		if (array_search($modelTableName, $tables) !== false && (strtolower($wannaDoValidation) == 'y' || strtolower($wannaDoValidation) == 'yes'))
		{	
			foreach($modelFields as $field)
			{
				$this->stdout('');
				$prompt .= 	'Name: ' . $field['name'] . "\n";
				$prompt .= 	'Type: ' . $field['type'] . "\n";
				$prompt .= 	'---------------------------------------------------------------'."\n";
				$prompt .= 	'Please select one of the following validation options:'."\n";
				$prompt .= 	'---------------------------------------------------------------'."\n";
				$prompt .=	"1- VALID_NOT_EMPTY\n";
				$prompt .=	"2- VALID_EMAIL\n";
				$prompt .=	"3- VALID_NUMBER\n";
				$prompt .=	"4- VALID_YEAR\n";
				$prompt .=	"5- Do not do any validation on this field.\n\n";
				$prompt .=	"... or enter in a valid regex validation string.\n\n";
				
				if($field['name'] == 'id' || $field['name'] == 'created' || $field['name'] == 'modified')
				{
					$validation = $this->getInput($prompt, null, '5');
				}
				else 
				{
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
		
		$wannaDoAssoc = $this->getInput('Would you like define model associations (hasMany, hasOne, belongsTo, etc.)?', array('y','n'), 'y');
		if((strtolower($wannaDoAssoc) == 'y' || strtolower($wannaDoAssoc) == 'yes'))
		{
			$this->stdout('One moment while I try to detect any associations...');
			
			//Look for belongsTo
			foreach($modelFields as $field)
			{	
				$offset = strpos($field['name'], '_id');
				
				if($offset !== false)
				{
					$belongsToClasses[] = $inflect->camelize(substr($field['name'], 0, $offset));	
				}
			}
			
			//Look for hasOne and hasMany and hasAndBelongsToMany
			foreach($tables as $table)
			{
				$tempModelOthers = new Model(false, $table);
				$modelFieldsTemp = $db->describe($tempModelOthers);
				
				foreach($modelFieldsTemp as $field)
				{	
					if($field['name'] == $inflect->singularize($modelTableName).'_id')
					{
						$hasOneClasses[] 	= $inflect->camelize($inflect->singularize($table));	
						$hasManyClasses[] 	= $inflect->camelize($inflect->singularize($table));
					}
				}
				
				$offset = strpos($table, $modelTableName . '_');
				if($offset !== false)
				{
					$offset = strlen($modelTableName . '_');
					$hasAndBelongsToManyClasses[] = $inflect->camelize($inflect->singularize(substr($table, $offset)));
				}
				
				$offset = strpos($table, '_' . $modelTableName);
				if ($offset !== false)
				{
					$hasAndBelongsToManyClasses[] = $inflect->camelize($inflect->singularize(substr($table, 0, $offset)));
				}
			}
			
			$this->stdout('Done.');
			
			$this->hr();
			
			//if none found...
			if(count($hasOneClasses) < 1 && count($hasManyClasses) < 1 && count($hasAndBelongsToManyClasses) < 1 && count($belongsToClasses))
			{
				$this->stdout('None found.');
			}
			else 
			{
				$this->stdout('Please confirm the following associations:');	
				$this->hr();

				if(count($belongsToClasses))
				{
					for($i = 0; $i < count($belongsToClasses); $i++)
					{
						$response = $this->getInput("$modelClassName belongsTo {$belongsToClasses[$i]}?", array('y','n'), 'y');
						if($response == 'y')
						{
							$modelAssociations['belongsTo'][] = $belongsToClasses[$i];
						}
					}
				}
				
				if(count($hasOneClasses))
				{
					for($i = 0; $i < count($hasOneClasses); $i++)
					{
						$response = $this->getInput("$modelClassName hasOne {$hasOneClasses[$i]}?", array('y','n'), 'y');
						if($response == 'y')
						{
							$modelAssociations['hasOne'][] = $hasOneClasses[$i];
						}
					}
				}
				
				if(count($hasManyClasses))
				{
					for($i = 0; $i < count($hasManyClasses); $i++)
					{
						$response = $this->getInput("$modelClassName hasMany {$hasManyClasses[$i]}?", array('y','n'), 'y');
						if($response == 'y')
						{
							$modelAssociations['hasMany'][] = $hasManyClasses[$i];
						}
					}
				}
				
				if(count($hasAndBelongsToManyClasses))
				{
					for($i = 0; $i < count($hasAndBelongsToManyClasses); $i++)
					{
						$response = $this->getInput("$modelClassName hasAndBelongsToMany {$hasAndBelongsToManyClasses[$i]}?", array('y','n'), 'y');
						if($response == 'y')
						{
							$modelAssociations['hasAndBelongsToMany'][] = $hasAndBelongsToManyClasses[$i];
						}
					}
				}
				
			}
			
			$wannaDoMoreAssoc = $this->getInput('Would you like to define some additional model associations?', array('y','n'), 'y');
			while((strtolower($wannaDoMoreAssoc) == 'y' || strtolower($wannaDoMoreAssoc) == 'yes'))
			{
				$assocs 		= array(1=>'belongsTo', 2=>'hasOne', 3=>'hasMany', 4=>'hasAndBelongsToMany');
				
				$bad = true;
				
				while($bad)
				{
					$this->stdout('What is the association type?');
					$prompt  =	"1- belongsTo\n";
					$prompt .=	"2- hasOne\n";
					$prompt .=	"3- hasMany\n";
					$prompt .=	"4- hasAndBelongsToMany\n";
					$assocType = intval($this->getInput($prompt, null, null));
					
					if(intval($assocType) < 1 || intval($assocType) > 4)
					{
						$this->stdout('The selection you entered was invalid. Please enter a number between 1 and 4.');
					}
					else 
					{
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
		$this->stdout("Model Name:      $modelClassName");
		$this->stdout("DB Connection:   " . ($usingDefault ? 'default' : $dbConnection));
		$this->stdout("Model Table:     " . $modelTableName);
		$this->stdout("Validation:      " . print_r($validate, true));	
		
		if(count($belongsToClasses) || count($hasOneClasses) || count($hasManyClasses) || count($hasAndBelongsToManyClasses))
		{
			$this->stdout("Associations:");	
			
			if(count($modelAssociations['belongsTo']))
			{
				for($i = 0; $i < count($modelAssociations['belongsTo']); $i++)
				{
					$this->stdout("                 $modelClassName belongsTo {$modelAssociations['belongsTo'][$i]}");
				}
			}
			
			if(count($modelAssociations['hasOne']))
			{
				for($i = 0; $i < count($modelAssociations['hasOne']); $i++)
				{
					$this->stdout("                 $modelClassName hasOne    {$modelAssociations['hasOne'][$i]}");
				}
			}
			
			if(count($modelAssociations['hasMany']))
			{
				for($i = 0; $i < count($modelAssociations['hasMany']); $i++)
				{
					$this->stdout("                 $modelClassName hasMany   {$modelAssociations['hasMany'][$i]}");
				}
			}
			
			if(count($modelAssociations['hasAndBelongsToMany']))
			{
				for($i = 0; $i < count($modelAssociations['hasAndBelongsToMany']); $i++)
				{
					$this->stdout("                 $modelClassName hasAndBelongsToMany {$modelAssociations['hasAndBelongsToMany'][$i]}");
				}
			}	
		}
		
		$this->hr();
		
		$looksGood = $this->getInput('Look okay?', array('y','n'), 'y');
		
		if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes')
		{
			if ($inflect->camelize($inflect->singularize($modelTableName)) == $modelClassName)
			{
				// set it to null... 
				// putting $useTable in the model 
				// is unnecessary.
				$modelTableName = null;
			}
			$this->bakeModel($modelClassName, $dbConnection, $modelTableName, $validate, $modelAssociations);
		}
		else
		{
			$this->stdout('Bake Aborted.');
		}	

	}
	
	function doView()
	{
		$this->hr();
		$this->stdout('View Bake:');
		$this->hr();
		
		$controllerName = '';
					
		$inflect = new Inflector();
		
		while ($controllerName == '') 
		{
			$controllerName = $this->getInput('Controller Name? (plural)');
			
			if ($controllerName == '')
			{
				$this->stdout('The controller name you supplied was empty. Please try again.');
			}
		}
		
		$wannaDoScaffold = $this->getInput("Would you like to create some scaffolded views (index, add, view, edit) for this controller?\nNOTE: Before doing so, you'll need to create your controller and model classes (including associated models).", array('y','n'), 'n');
		$uses = array();
		
		if (strtolower($wannaDoScaffold) == 'y' || strtolower($wannaDoScaffold) == 'yes')
		{
			$file = CONTROLLERS . $controllerName . '_controller.php';
			
			if(!file_exists($file))
			{
				$this->stdout('');
				$this->stdout("The file '$file' could not be found.\nIn order to scaffold, you'll need to first create the controller. ");
				$this->stdout('');
				die();
			}
			else 
			{
				require_once(CONTROLLERS . $controllerName . '_controller.php');
				$controller = $inflect->camelize($controllerName . '_controller');
				$temp = new $controller();

				if(!in_array('Form', $temp->helpers))
		        {
		            $temp->helpers[] = 'Form';
		        }
		        
		        loadModels();
				$temp->constructClasses();
				
				$fieldNames = $temp->generateFieldNames(null, false);
				
				uses('view'.DS.'helpers'.DS.'html');
				uses('view'.DS.'helpers'.DS.'form');
				$this->Html = new HtmlHelper();
				$this->Html->tags = $this->Html->loadConfig();
				
				if(!empty($temp->{$temp->modelClass}->alias))
				{
				    foreach ($temp->{$temp->modelClass}->alias as $key => $value)
				    {
				        $alias[] =  $key;
				    }
				}
				
				//-------------------------[INDEX]-------------------------//
				
				$indexView .= "<h1>List " . $inflect->pluralize($temp->modelKey) . "</h1>\n\n";
				$indexView .= "<table>\n";
				$indexView .= "<tr>\n";

				foreach ($fieldNames as $fieldName)
				{
				    $indexView .= "\t<th>".$fieldName['prompt']."</th>\n";
				}
				
				$indexView .= "\t<th>Actions</th>\n";
				$indexView .= "</tr>\n";
				
				$indexView .= "<?php foreach (\$data as \$row):?>\n";
				
				$indexView .= "<tr>\n";
				
				$count = 0;
				
				foreach($fieldNames as $field => $value)
				{
					if(isset($value['foreignKey']))
		            {      
		                $otherModelKey = Inflector::underscore($value['modelKey']);
		                $otherControllerName = $value['controller'];
		                $otherModelObject =& ClassRegistry::getObject($otherModelKey);
		                
		                if(is_object($otherModelObject))
		                {
		                    $indexView .= "\t<td><?php echo \$row['" . $alias[$count] ."']['" . $otherModelObject->getDisplayField() ."'] ?></td>\n";
		                }
		                else
		                {
		                	$indexView .= "\t<td><?php echo \$row['" . $alias[$count] ."']['" . $field ."'] ?></td>\n";
		                }
		                $count++;
		            }
		            else
		            {
		                $indexView .= "\t<td><?php echo \$row['{$temp->modelClass}']['{$field}'] ?></td>\n";
		            }
				}
				
				
				
				$id = $temp->{$temp->modelClass}->primaryKey;
				
				$indexView .= "\t<td>\n";
				$indexView .= "\t\t<?php echo \$html->link('View','/$temp->viewPath/view/' . \$row['{$temp->modelClass}']['$id'])?>\n";
                $indexView .= "\t\t<?php echo \$html->link('Edit','/$temp->viewPath/edit/' . \$row['{$temp->modelClass}']['$id'])?>\n";
                $indexView .= "\t\t<?php echo \$html->link('Delete','/$temp->viewPath/delete/' . \$row['{$temp->modelClass}']['$id'])?>\n";
       			$indexView .= "\t</td>\n";
				
				$indexView .= "</tr>\n";
				
				$indexView .= "<?php endforeach?>\n";
				$indexView .= "</table>\n\n";
				
				$indexView .= "<ul>\n";
				$indexView .= "\t<li><?php echo \$html->link('New $temp->modelClass', '/$temp->viewPath/add'); ?></li>\n";
				$indexView .= "</ul>\n";
				
				//-------------------------[VIEW]-------------------------//
				
				$modelName = $temp->modelClass;
				$modelKey = Inflector::underscore($modelName);
				$objModel =& ClassRegistry::getObject($modelKey);
				
				$viewView .= "<h1>View " . $inflect->pluralize($temp->modelKey) . "</h1>\n\n";
				
				$viewView .= "<table>\n";
				
				$count = 0;
				foreach($fieldNames as $field => $value)
				{
					$viewView .= "<tr>\n";
				    $viewView .= "\t<td><?php echo '{$value['prompt']}' ?></td>\n";
				    
				    if(isset($value['foreignKey']))
				    {
				        $otherModelObject =& ClassRegistry::getObject(Inflector::underscore($objModel->tableToModel[$value['table']]));
				        $displayField = $otherModelObject->getDisplayField();

				        $viewView .= "\t<td><?php echo \$html->link(\$data['{$alias[$count]}']['{$displayField}'], '/" . $inflect->underscore($value['controller']) . "/view/' . \$data['{$objModel->tableToModel[$objModel->table]}']['{$field}'])?></td>\n";

				        $count++;
				    }
				    else
				    {
				        $viewView .= "\t<td><?php echo \$data['{$objModel->tableToModel[$objModel->table]}']['{$field}']?></td>\n";
				    }
				    
				    $viewView .= "</tr>\n";
				}
				
				$viewView .= "</table>\n";
				
				$viewView .= "<ul>\n";
				$viewView .= "\t<li><?php echo \$html->link('Edit " . $inflect->humanize($objModel->name) . "',   '/{$temp->viewPath}/edit/' . \$data['{$objModel->tableToModel[$objModel->table]}']['$id']) ?> </li>\n";
				$viewView .= "\t<li><?php echo \$html->link('Delete " . $inflect->humanize($objModel->name) . "', '/{$temp->viewPath}/delete/' . \$data['{$objModel->tableToModel[$objModel->table]}']['$id']) ?> </li>\n";
				$viewView .= "\t<li><?php echo \$html->link('List " . $inflect->humanize($objModel->name) ."',   '/{$temp->viewPath}/index') ?> </li>\n";
				$viewView .= "\t<li><?php echo \$html->link('New " . $inflect->humanize($objModel->name) . "',    '/{$temp->viewPath}/add') ?> </li>\n";
				
				foreach( $fieldNames as $field => $value )
				{
				    if( isset( $value['foreignKey'] ) )
				    {
				        $viewView .= "\t<li> <?php echo \$html->link( 'List " . $inflect->humanize($value['controller']) . "', '/" . $inflect->underscore($value['controller']) . "/index/')?> </li>\n";
				    }
				}

				$viewView .= "</ul>\n\n";
				
				foreach ($objModel->hasOne as $association => $relation)
				{
				    $model = $relation['className'];
				    $otherModelName = $objModel->tableToModel[$objModel->{$model}->table];
				    $controller = $inflect->pluralize($model);
				    $new = true;
				    $viewView .= "<h2>Related " . $inflect->humanize($association) . "</h2>\n";
				    $viewView .= "<dl>\n";  
				    $viewView .= "<?php if(isset(\$data['{$association}']) && is_array(\$data['{$association}'])): ?>\n";   
			        $viewView .= "\t<?php foreach(\$data['{$association}'] as \$field => \$value): ?>\n";    
	                $viewView .= "\t\t<dt><?php echo \$field ?></dt>\n";
	                $viewView .= "\t\t<dd><?php echo \$value ?></dd>\n";
		            $viewView .= "\t<?php endforeach; ?>\n";	        
			        $viewView .= "\t<ul><li><?php echo \$html->link('New " . $inflect->humanize($association) . "', '/" .$inflect->underscore($controller)."/add/' . \$data['{$association}']['" . $objModel->{$model}->primaryKey . "'])?> </li></ul>\n";
			        $viewView .= "<?endif?>\n";
			        $viewView .= "</dl>\n";
				    
				}
				
				$relations = array_merge($objModel->hasMany, $objModel->hasAndBelongsToMany);
				
				foreach($relations as $association => $relation)
				{
				    $model = $relation['className'];
				    $count = 0;
				    $otherModelName = $inflect->singularize($model);
				    $controller = $inflect->pluralize($model);
				
				    $viewView .= "\n<h2>Related " . $inflect->humanize($inflect->pluralize($association)) . "</h2>\n";
				    $viewView .= "<?php if(isset(\$data['{$association}']) && is_array(\$data['{$association}'])):?>\n";
				    
				
				    $viewView .= "<table>\n";
				    $viewView .= "<tr>\n";
				
			        $viewView .= "<?php foreach(\$data['{$association}'][0] as \$column => \$value): ?>\n";
			        	$viewView .= "<th><?php echo \$column?></th>\n";
			        $viewView .= "<?endforeach;?>\n";
			
			        $viewView .= "<th>Actions</th>\n";
			        $viewView .= "</tr>\n";
			
			        $viewView .= "<?php foreach(\$data['{$association}'] as \$row):?>\n";
			        
			            $viewView .= "<tr>\n";
			            
			            $viewView .= "\t<?php foreach(\$row as \$column => \$value):?>\n";
			            	$viewView .= "\t\t<td><?php echo \$value?></td>\n";
			            $viewView .= "\t<?endforeach;?>\n";
			            
			            $viewView .= "<?if (isset(\$this->controller->{$modelName}->{$association})):?>\n";
			                $viewView .= "<td>\n";
			                	$viewView .= "\t<?php echo \$html->link('View', '/" . $inflect->underscore($controller) . "/view/' . \$row[\$this->controller->{$modelName}->{$association}->primaryKey])?>\n";
                                $viewView .= "\t<?php echo \$html->link('Edit', '/" . $inflect->underscore($controller) . "/edit/' . \$row[\$this->controller->{$modelName}->{$association}->primaryKey])?>\n";
                                $viewView .= "\t<?php echo \$html->link('Delete', '/" . $inflect->underscore($controller) . "/delete/' . \$row[\$this->controller->{$modelName}->{$association}->primaryKey])?>\n";
			                $viewView .= "</td>\n";
			            $viewView .= "<?else:?>\n";
			                $viewView .= "<td>\n";
			                	$viewView .= "\t<?php echo \$html->link('View', '/" . $inflect->underscore($controller) . "/view/' . \$row[\$this->controller->{$modelName}->primaryKey])?>\n";
                                $viewView .= "\t<?php echo \$html->link('Edit', '/" . $inflect->underscore($controller) . "/edit/' . \$row[\$this->controller->{$modelName}->primaryKey])?>\n";
                                $viewView .= "\t<?php echo \$html->link('Delete', '/" . $inflect->underscore($controller) . "/delete/' . \$row[\$this->controller->{$modelName}->primaryKey])?>\n";
			                $viewView .= "</td>\n";
			            $viewView .= "<?endif;?>\n";
			            
			            $viewView .= "</tr>\n";
			        $viewView .= "<?endforeach;?>\n";
				
				$viewView .= "</table>\n";
				$viewView .= "<?endif;?>\n\n";
				
				$viewView .= "<ul>\n";
				
				    $viewView .= "<li><?php echo \$html->link('New " . $inflect->humanize($association) . "', '/" . $inflect->underscore($controller) . "/add/')?></li>\n";
				
				$viewView .= "</ul>\n";
				
				}
				
				//-------------------------[ADD]-------------------------//
				
				$addView .= "<h1>New " . $temp->modelKey . "</h1>\n";
				$addView .= "<?php \$data = null;?>";
				
				$fields .= $this->Html->formTag('/'. $temp->viewPath . '/add') . "\n";
				$fields .= $this->generateFields($temp->generateFieldNames(null, true));
				$fields .= $this->generateSubmitDiv('Add');
				
				$addView .= $fields;
				
				$addView .= "</form>\n";
				$addView .= "<ul>\n";

				$addView .= "<li><?php echo \$html->link('List " . $temp->viewPath . "', '/{$temp->viewPath}/index')?></li>\n";

				$addView .= "</ul>\n";
				
				//-------------------------[EDIT]-------------------------//

				$editView .= "<h1>Edit " . $temp->modelKey . "</h1>\n";
				
				$editView .= "<form action=\"/{$temp->viewPath}/edit/<?php echo \$data['{$objModel->tableToModel[$objModel->table]}']['$id'] ?>\" method=\"post\">\n";
				
				$fields = $this->generateFields($temp->generateFieldNames(null, true));
				$fields .= "<?php echo \$html->hidden('{$objModel->table}/{$id}', array('value' => \$data['{$objModel->tableToModel[$objModel->table]}']['$id']))?>";
				$fields .= $this->generateSubmitDiv('Save');
				
				$editView .= $fields;
				
				$editView .= "</form>\n";
				$editView .= "<ul>\n";

				$editView .= "\t<li><?php echo \$html->link('List " . $temp->viewPath . "', '/{$temp->viewPath}/index')?></li>\n";

				$editView .= "</ul>\n";
				
				//------------------------------------------------------------------------------------//
				
				if(!file_exists(VIEWS.strtolower($controllerName)))
				{
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
				$this->stdout('Note:'."\n");
				$this->stdout("\t- If you're using a non-domain install, change URL paths \n\t  from /controller/action to /cake_install/controller/action\n");
				$this->hr();
				
				$this->stdout('');
				$this->stdout('View Scaffolding Complete.'."\n");
				
			}
		}
		else 
		{
			$actionName = '';
			
			while ($actionName == '') 
			{
				$actionName = $this->getInput('Action Name? (use camelCased function name)');
				
				if ($actionName == '')
				{
					$this->stdout('The action name you supplied was empty. Please try again.');
				}
			}
			
			$this->stdout('');
			$this->hr();
			$this->stdout('The following view will be created:');
			$this->hr();
			$this->stdout("Controller Name:    $controllerName");
			$this->stdout("Action Name:        $actionName");
			$this->stdout("Path:               app/views/" . strtolower($controllerName) . DS . $inflect->underscore($actionName) . '.thtml');
			$this->hr();
			
			$looksGood = $this->getInput('Look okay?', array('y','n'), 'y');
			
			if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes')
			{
				$this->bakeView($controllerName, $actionName);
			}
			else
			{
				$this->stdout('Bake Aborted.');
			}	
		}	
	}
	
	function doController()
	{
		$this->hr();
		$this->stdout('Controller Bake:');
		$this->hr();
		
		$controllerName = '';
		
		while ($controllerName == '') 
		{
			$controllerName = $this->getInput('Controller name? Remember that Cake controller names are plural.');
			
			if ($controllerName == '')
			{
				$this->stdout('The controller name you supplied was empty. Please try again.');
			}
		}
		
		$inflect = new Inflector();
		
		$controllerClassName = $inflect->camelize($controllerName);
		
		$wannaDoUses = $this->getInput("Would you like this controller to use other models besides '" . $inflect->singularize($controllerClassName) .  "'?", array('y','n'), 'n');
		$uses = array();
		
		if (strtolower($wannaDoUses) == 'y' || strtolower($wannaDoUses) == 'yes')
		{
			$usesList = $this->getInput("Please provide a comma separated list of the classnames of other models you'd like to use.\nExample: 'Author, Article, Book'");
			$usesListTrimmed = str_replace(' ', '', $usesList);
			$uses = explode(',', $usesListTrimmed);
		}	
		
		$wannaDoHelpers = $this->getInput("Would you like this controller to use other helpers besides HtmlHelper?", array('y','n'), 'n');
		$helpers = array();
		
		if (strtolower($wannaDoHelpers) == 'y' || strtolower($wannaDoHelpers) == 'yes')
		{
			$helpersList = $this->getInput("Please provide a comma separated list of the other helper names you'd like to use.\nExample: 'Ajax, Javascript, Time'");
			$helpersListTrimmed = str_replace(' ', '', $helpersList);
			$helpers = explode(',', $helpersListTrimmed);
		}	
		
		$wannaDoComponents = $this->getInput("Would you like this controller to use any components?", array('y','n'), 'n');
		$components = array();
		
		if (strtolower($wannaDoComponents) == 'y' || strtolower($wannaDoComponents) == 'yes')
		{
			$componentsList = $this->getInput("Please provide a comma separated list of the component names you'd like to use.\nExample: 'Acl, MyNiftyHelper'");
			$componentsListTrimmed = str_replace(' ', '', $componentsList);
			$components = explode(',', $componentsListTrimmed);
		}
		
		$wannaDoScaffolding = $this->getInput("Would to include some basic scaffolded actions (index, add, view, edit)?", array('y','n'), 'n');
		
		if (strtolower($wannaDoScaffolding) == 'y' || strtolower($wannaDoScaffolding) == 'yes')
		{
			$controllerModel = $inflect->singularize($controllerClassName);
			
			$actions .= "\n";
			$actions .= "\tfunction index()\n";	
			$actions .= "\t{\n";	
			$actions .= "\t\t\$this->set('data', \$this->{$controllerModel}->findAll());\n";	
			$actions .= "\t}\n";
			
			$actions .= "\n";
			$actions .= "\tfunction add()\n";	
			$actions .= "\t{\n";	
			$actions .= "\t\tif(empty(\$this->params['data']))\n";
			$actions .= "\t\t{\n";
			$actions .= "\t\t\t\$this->render();\n";
			$actions .= "\t\t}\n";
			$actions .= "\t\telse\n";
			$actions .= "\t\t{\n";
			$actions .= "\t\t\tif(\$this->{$controllerModel}->save(\$this->params['data']))\n";
			$actions .= "\t\t\t{\n";
			$actions .= "\t\t\t\t\$this->flash('{$controllerModel} saved.', '/{$controllerName}/index');\n";
			$actions .= "\t\t\t}\n";
			$actions .= "\t\t\telse\n";
			$actions .= "\t\t\t{\n";
			$actions .= "\t\t\t\t\$this->render();\n";
			$actions .= "\t\t\t}\n";
			$actions .= "\t\t}\n";
			$actions .= "\t}\n";
			
			$actions .= "\n";
			$actions .= "\tfunction edit(\$id)\n";	
			$actions .= "\t{\n";	
			$actions .= "\t\tif(empty(\$this->params['data']))\n";
			$actions .= "\t\t{\n";
			$actions .= "\t\t\t\$this->set('data', \$this->{$controllerModel}->find('{$controllerModel}.id = ' . \$id));\n";
			$actions .= "\t\t}\n";
			$actions .= "\t\telse\n";
			$actions .= "\t\t{\n";
			$actions .= "\t\t\tif(\$this->{$controllerModel}->save(\$this->params['data']))\n";
			$actions .= "\t\t\t{\n";
			$actions .= "\t\t\t\t\$this->flash('{$controllerModel} saved.', '/{$controllerName}/index');\n";
			$actions .= "\t\t\t}\n";
			$actions .= "\t\t\telse\n";
			$actions .= "\t\t\t{\n";
			$actions .= "\t\t\t\t\$this->render();\n";
			$actions .= "\t\t\t}\n";
			$actions .= "\t\t}\n";
			$actions .= "\t}\n";
			
			$actions .= "\n";
			$actions .= "\tfunction view(\$id)\n";	
			$actions .= "\t{\n";	
			$actions .= "\t\t\$this->set('data', \$this->{$controllerModel}->find('{$controllerModel}.id = ' . \$id));\n";
			$actions .= "\t}\n";
			
			$actions .= "\n";
			$actions .= "\tfunction delete(\$id)\n";	
			$actions .= "\t{\n";	
			$actions .= "\t\t\$this->{$controllerModel}->del(\$id);\n";
			$actions .= "\t\t\$this->redirect('/{$controllerName}/index');\n";
			$actions .= "\t}\n";
			$actions .= "\n";
			
			$lowerCaseModel = strtolower(substr($controllerModel, 0, 1)) . substr($controllerModel, 1);
			
			$actions .= "\tfunction {$lowerCaseModel}List()\n";
			$actions .= "\t{\n";
			$actions .= "\t\t\$vars = \$this->{$controllerModel}->findAll();\n";
			$actions .= "\t\tforeach(\$vars as \$var)\n";
			$actions .= "\t\t{\n";
			$actions .= "\t\t\t\$list[\$var['{$controllerModel}']['id']] = \$var['{$controllerModel}']['name'];\n";
			$actions .= "\t\t}\n";
			$actions .= "\n";
			$actions .= "\t\treturn \$list;\n";
			$actions .= "\t}\n";
		}
		
		$this->stdout('');
		$this->hr();
		$this->stdout('The following controller will be created:');
		$this->hr();
		$this->stdout("Controller Name:    $controllerName");
		if(count($uses))
		{
			$this->stdout("Uses:               ", false);
			foreach($uses as $use)
			{
				if ($use != $uses[count($uses) - 1])
				{	
					$this->stdout(ucfirst($use) . ", ", false);
				}
				else 
				{
					$this->stdout(ucfirst($use));
				}
			}
		}
		if(count($helpers))
		{
			$this->stdout("Helpers:            ", false);
			foreach($helpers as $help)
			{
				if ($help != $helpers[count($helpers) - 1])
				{	
					$this->stdout(ucfirst($help) . ", ", false);
				}
				else 
				{
					$this->stdout(ucfirst($help));
				}
			}
		}
		if(count($components))
		{
			$this->stdout("Components:         ", false);
			foreach($components as $comp)
			{
				if ($comp != $components[count($components) - 1])
				{	
					$this->stdout(ucfirst($comp) . ", ", false);
				}
				else 
				{
					$this->stdout(ucfirst($comp));
				}
			}
		}
		$this->hr();
		
		$looksGood = $this->getInput('Look okay?', array('y','n'), 'y');
		
		if (strtolower($looksGood) == 'y' || strtolower($looksGood) == 'yes')
		{
			$this->bakeController($controllerClassName, $uses, $helpers, $components, $actions);
		}
		else
		{
			$this->stdout('Bake Aborted.');
		}	
	}
	
	/*---- ----*/
	
	function bakeDbConfig($host, $login, $password, $database) 
	{
		$out =  "<?php\n";
		$out .= "class DATABASE_CONFIG\n";
		$out .= "{\n";
		$out .= "\tvar \$default = array(\n";
		$out .= "\t\t'driver'   => 'mysql',\n";
		$out .= "\t\t'connect'  => 'mysql_pconnect',\n";
		$out .= "\t\t'host'     => '$host',\n";
		$out .= "\t\t'login'    => '$login',\n";
		$out .= "\t\t'password' => '$password',\n";
		$out .= "\t\t'database' => '$database' \n";
		$out .= "\t);\n";
		$out .= "}\n";
		$out .=  "?>";
		
		$filename = CONFIGS.'database.php';
		
		$this->createFile($filename, $out);
	}
	
	function bakeModel($modelClassName, $dbConnection, $modelTableName, $validate, $modelAssociations)
	{
		$out =  "<?php\n";
		$out .= "class $modelClassName extends AppModel\n";
		$out .= "{\n";
		$out .= "\tvar \$name = '$modelClassName';\n";
		
		if ($dbConnection != 'default')
		{
			$out .= "\tvar \$useDbConfig = '$dbConnection';\n";
		}
		
		if ($modelTableName != null)
		{
			$out .= "\tvar \$useTable = '$modelTableName';\n";
		}
		
		if (count($validate)) {
			$out .= "\tvar \$validate = array(\n";
			$keys = array_keys($validate);
			for($i = 0; $i < count($validate); $i++)
			{
				$out .= "\t\t'" . $keys[$i] . "' => " . $validate[$keys[$i]] . ",\n";
			}
			$out .= "\t);\n";
		}
		
		$out .= "\n";
		
		if(count($modelAssociations['belongsTo']) || count($modelAssociations['hasOne']) || count($modelAssociations['hasMany']) || count($modelAssociations['hasAndBelongsToMany']))
		{	
			if(count($modelAssociations['belongsTo']))
			{	
				$out .= "\tvar \$belongsTo = array(\n";
				
				for($i = 0; $i < count($modelAssociations['belongsTo']); $i++)
				{
					$out .= "\t\t\t'{$modelAssociations['belongsTo'][$i]}' =>\n";
					$out .= "\t\t\t array('className'    => '{$modelAssociations['belongsTo'][$i]}',\n";
					$out .= "\t\t\t       'conditions'   => '',\n";
					$out .= "\t\t\t       'order'        => '',\n";
					$out .= "\t\t\t       'foreignKey'   => '',\n";
					$out .= "\t\t\t       'counterCache' => ''),\n\n";
				}
				
				$out .= "\t);\n\n";
			}
			
			if(count($modelAssociations['hasOne']))
			{
				$out .= "\tvar \$hasOne = array(\n";
				
				for($i = 0; $i < count($modelAssociations['hasOne']); $i++)
				{
					$out .= "\t\t\t'{$modelAssociations['hasOne'][$i]}' =>\n";
					$out .= "\t\t\t array('className'    => '{$modelAssociations['hasOne'][$i]}',\n";
					$out .= "\t\t\t       'conditions'   => '',\n";
					$out .= "\t\t\t       'order'        => '',\n";
					$out .= "\t\t\t       'foreignKey'   => '',\n";
					$out .= "\t\t\t       'dependent'    => ''),\n\n";
				}
				
				$out .= "\t);\n\n";				
			}
			
			if(count($modelAssociations['hasMany']))
			{
				$out .= "\tvar \$hasMany = array(\n";
				
				for($i = 0; $i < count($modelAssociations['hasMany']); $i++)
				{
					$out .= "\t\t\t'{$modelAssociations['hasMany'][$i]}' =>\n";
					$out .= "\t\t\t array('className'    => '{$modelAssociations['hasMany'][$i]}',\n";
					$out .= "\t\t\t       'conditions'   => '',\n";
					$out .= "\t\t\t       'order'        => '',\n";
					$out .= "\t\t\t       'foreignKey'   => '',\n";
					$out .= "\t\t\t       'dependent'    => '',\n";
					$out .= "\t\t\t       'exclusive'    => '',\n";
					$out .= "\t\t\t       'finderSql'    => '',\n";
					$out .= "\t\t\t       'counterSql'   => ''),\n\n";
				}
				
				$out .= "\t);\n\n";
			}
			
			if(count($modelAssociations['hasAndBelongsToMany']))
			{
				$out .= "\tvar \$hasAndBelongsToMany = array(\n";
				
				for($i = 0; $i < count($modelAssociations['hasAndBelongsToMany']); $i++)
				{
					$out .= "\t\t\t'{$modelAssociations['hasAndBelongsToMany'][$i]}' =>\n";
					$out .= "\t\t\t array('className'             => '{$modelAssociations['hasAndBelongsToMany'][$i]}',\n";
					$out .= "\t\t\t       'conditions'            => '',\n";
					$out .= "\t\t\t       'order'                 => '',\n";
					$out .= "\t\t\t       'foreignKey'            => '',\n";
					$out .= "\t\t\t       'joinTable'             => '',\n";
					$out .= "\t\t\t       'associationForeignKey' => '',\n";
					$out .= "\t\t\t       'uniq'                  => '',\n";
					$out .= "\t\t\t       'finderQuery'           => '',\n";
					$out .= "\t\t\t       'deleteQuery'           => '',\n";
					$out .= "\t\t\t       'insertQuery'           => ''),\n\n";
				}
				
				$out .= "\t);\n\n";
			}
		}
		
		$out .= "}\n";
		$out .=  "?>\n";
		
		$inflect = new Inflector();
		
		$filename = MODELS.$inflect->underscore($modelClassName) . '.php';
		
		$this->createFile($filename, $out);
	}
	
	function bakeView($controllerName, $actionName, $content = '')
	{
		$out = "<h1>$actionName</h1>\n";
		$out .= $content;
		
		$inflect = new Inflector();
		
		if(!file_exists(VIEWS.strtolower($controllerName)))
		{
			mkdir(VIEWS.strtolower($controllerName));
		}
		
		$filename = VIEWS . strtolower($controllerName) . DS . $inflect->underscore($actionName) . '.thtml';
		
		$this->createFile($filename, $out);
	}
	
	function bakeController($controllerName, $uses, $helpers, $components, $actions = '')
	{
		$inflect = new Inflector();
		
		$out =  "<?php\n";
		$out .= "class $controllerName" . "Controller extends AppController\n";
		$out .= "{\n";
		$out .= "\t//var \$scaffold;\n";
		$out .= "\tvar \$name       = '$controllerName';\n";
		
		if (count($uses))
		{
			$out .= "\tvar \$uses       = array('" . $inflect->singularize($controllerName) . "', ";
			
			foreach($uses as $use)
			{
				if ($use != $uses[count($uses) - 1])
				{	
					$out .= "'" . ucfirst($use) . "', ";
				}
				else 
				{
					$out .= "'" . ucfirst($use) . "'";
				}
			}
			
			$out .= ");\n";

		}
		
		if (count($helpers))
		{
			$out .= "\tvar \$helpers    = array('Html', ";
			
			foreach($helpers as $help)
			{
				if ($help != $helpers[count($helpers) - 1])
				{	
					$out .= "'" . ucfirst($help) . "', ";
				}
				else 
				{
					$out .= "'" . ucfirst($help) . "'";
				}
			}
			
			$out .= ");\n";

		}
		
		if (count($components))
		{
			$out .= "\tvar \$components = array(";
			
			foreach($components as $comp)
			{
				if ($comp != $components[count($components) - 1])
				{	
					$out .= "'" . ucfirst($comp) . "', ";
				}
				else 
				{
					$out .= "'" . ucfirst($comp) . "'";
				}
			}
			
			$out .= ");\n";

		}
		
		$out .= $actions;
		
		$out .= "}\n";
		$out .= "?>\n";
		
		$filename = CONTROLLERS . $inflect->underscore($controllerName) . '_controller.php';
		
		$this->createFile($filename, $out);
	}
	
	/*----General purpose functions----*/
	
	function getInput($prompt, $options = null, $default = null)
	{
		if (!is_array($options))
		{
			$print_options = '';
		}
		else 
		{
			$print_options = '(' . implode('/', $options) . ')';	
		}
		
		if($default == null)
		{
			$this->stdout('');
			$this->stdout($prompt . " $print_options \n" . '> ', false);
		}
		else 
		{
			$this->stdout('');
			$this->stdout($prompt . " $print_options \n" . "[$default] > ", false);
		}
		
		$result =  trim(fgets(STDIN));
		
		if($default != null && empty($result))
		{
			return $default;
		}
		else 
		{	
			return $result;	
		}
	}
	
	function stdout($string, $newline = true)
	{
		if ($newline)
		{
			fwrite($this->stdout, $string . "\n");
		}
		else 
		{
			fwrite($this->stdout, $string);
		}
	}
	
	function stderr($string)
	{
		fwrite($this->stderr, $string);
	}
	
	function hr()
	{
		$this->stdout('---------------------------------------------------------------');
	}
	
	function createFile ($path, $contents)
    {
        echo "\nCreating file $path\n";
        $shortPath = str_replace(ROOT,null,$path);

        if (is_file($path) && !$this->dontAsk)
        {
            fwrite($this->stdout, "File {$shortPath} exists, overwrite? (y/n/q):");
            $key = trim(fgets($this->stdin));

            if ($key=='q')
            {
                fwrite($this->stdout, "Quitting.\n");
                exit;
            }
            elseif ($key=='a')
            {
                $this->dont_ask = true;
            }
            elseif ($key=='y')
            {
            }
            else
            {
                fwrite($this->stdout, "Skip   {$shortPath}\n");
                return false;
            }
        }

        if ($f = fopen($path, 'w'))
        {
            fwrite($f, $contents);
            fclose($f);
            fwrite($this->stdout, "Wrote   {$shortPath}\n");
            return true;
        }
        else
        {
            fwrite($this->stderr, "Error! Couldn't open {$shortPath} for writing.\n");
            return false;
        }
    }
    
    //----------------[Modified Form Helper Methods]--------------------------------------------------------------------------------//
    
    function generateFields( $fields, $readOnly = false )
    {
        $strFormFields = '';
        foreach( $fields as $field )
        {
            if(isset( $field['type']))
            {
                if(!isset($field['required']))
                {
                    $field['required'] = false;
                }
                if(!isset( $field['errorMsg']))
                {
                    $field['errorMsg'] = null;
                }
                if(!isset( $field['htmlOptions']))
                {
                    $field['htmlOptions'] = array();
                }
                if( $readOnly )
                {
                    $field['htmlOptions']['READONLY'] = "readonly";
                }

                switch( $field['type'] )
                {
                    case "input" :
                        if( !isset( $field['size'] ) )
                        {
                            $field['size'] = 40;
                        }
                        $strFormFields = $strFormFields.$this->generateInputDiv( $field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], $field['size'], $field['htmlOptions'] );
                    break;
                    case "checkbox" :
                        $strFormFields = $strFormFields.$this->generateCheckboxDiv( $field['tagName'], $field['prompt'], $field['required'], $field['errorMsg'], $field['htmlOptions'] );
                    break;
                    case "select";
                    case "selectMultiple";
                        if( "selectMultiple" == $field['type'] )
                        {
                            $field['selectAttr']['multiple'] = 'multiple';
                            $field['selectAttr']['class'] = 'selectMultiple';
                        }
                        if(!isset( $field['selected']))
                        {
                            $field['selected'] = null;
                        }
                        if(!isset( $field['selectAttr']))
                        {
                            $field['selectAttr'] = null;
                        }
                        if(!isset( $field['optionsAttr']))
                        {
                            $field['optionsAttr'] = null;
                        }
                        if($readOnly)
                        {
                            $field['selectAttr']['DISABLED'] = true;
                        }
                        if(!isset( $field['options']))
                        {
                            $field['options'] = null;
                        }
                        
                        $strFormFields = $strFormFields.$this->generateSelectDiv( $field['tagName'], $field['prompt'], $field['options'], $field['selected'], $field['selectAttr'], $field['optionsAttr'], $field['required'], $field['errorMsg'] );
                    break;
                    case "area";
                        if(!isset( $field['rows']))
                        {
                            $field['rows'] = 10;
                        }
                        if(!isset( $field['cols']))
                        {
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
                        if( !isset( $field['selected']))
                        {
                            $field['selected'] = null;
                        }
                        $strFormFields = $strFormFields.$this->generateDate( $field['tagName'], $field['prompt'], null, null, null, null, $field['selected']);
                    break;
                    case "datetime":
                        if( !isset( $field['selected']))
                        {
                            $field['selected'] = null;
                        }
                        $strFormFields = $strFormFields.$this->generateDateTime( $field['tagName'], $field['prompt'], '','','', '', $field['selected']);
                    break;
                    default:
                    break;
                }
            }
        }
        return $strFormFields;
    }
    
    function generateAreaDiv($tagName, $prompt, $required=false, $errorMsg=null, $cols=60, $rows=10,  $htmlOptions=null )
    {
        $htmlOptions['id'] = strtolower(str_replace('/', '_',$tagName));
        $htmlAttributes = $htmlOptions;
        $htmlAttributes['cols'] = $cols;
        $htmlAttributes['rows'] = $rows;
        
        $tagNameArray = explode('/', $tagName);
        $htmlAttributes['value'] = "\$data['{$tagNameArray[0]}']['{$tagNameArray[1]}']";
        
        $str = "\t<?php echo \$html->textarea('{$tagName}', " . $this->attributesToArray($htmlAttributes) . ") ?>\n";
        $str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
        $strLabel = "\n\t" . $this->labelTag( $tagName, $prompt );

        $divClass = "optional";

        if( $required )
        $divClass = "required";

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
    
    function generateCheckboxDiv($tagName, $prompt, $required=false, $errorMsg=null, $htmlOptions=null )
    {
        $htmlOptions['class'] = "inputCheckbox";
        $htmlOptions['id'] = strtolower(str_replace('/', '_',$tagName));
        
        $tagNameArray = explode('/', $tagName);
        $htmlAttributes['checked'] = "<?php \$data['{$tagNameArray[0]}']['{$tagNameArray[1]}'] ? 'checked' : '' ?>";
        
        
        $str = "\t<?php echo \$html->checkbox('{$tagName}', null, " . $this->attributesToArray($htmlAttributes) . ")?>\n";
        $str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
        $strLabel = "\n\t" . $this->labelTag( $tagName, $prompt );

        $divClass = "optional";

        if( $required )
        $divClass = "required";

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
    
    function generateDate($tagName, $prompt, $required=false, $errorMsg=null, $size=20, $htmlOptions=null, $selected=null )
    {
        $htmlOptions['id'] = strtolower(str_replace('/', '_',$tagName));
        $tagNameArray = explode('/', $tagName);
        $str = "\t<?php echo \$html->dateTimeOptionTag('{$tagName}', 'MDY' , 'NONE', \$data['{$tagNameArray[0]}']['{$tagNameArray[1]}'], " . $this->attributesToArray($htmlOptions) . ")?>\n";
        $str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
        $strLabel = "\n\t" . $this->labelTag( $tagName, $prompt );

        $divClass = "optional";

        if( $required )
        $divClass = "required";

        $strError = "";// initialize the error to empty.

        if( $this->isFieldError( $tagName ) )
        {
// if it was an error that occured, then add the error message, and append " error" to the div tag.
            $strError = $this->pTag( 'error', $errorMsg );
            $divClass = sprintf( "%s error", $divClass );
        }
        $divTagInside = sprintf( "%s %s %s", $strError, $strLabel, $str );

        $requiredDiv = $this->divTag( $divClass, $divTagInside );

        return $this->divTag("date", $requiredDiv);
    }
    
    function generateInputDiv($tagName, $prompt, $required=false, $errorMsg=null, $size=20, $htmlOptions=null )
    {
        $htmlOptions['id'] = strtolower(str_replace('/', '_', $tagName));
        $htmlAttributes = $htmlOptions;
        $htmlAttributes['size'] = $size;
        
        $tagNameArray = explode('/', $tagName);
        $htmlAttributes['value'] = "\$data['{$tagNameArray[0]}']['{$tagNameArray[1]}']";
        
        $str = "\t<?php echo \$html->input('{$tagName}', " . $this->attributesToArray($htmlAttributes) . ") ?>\n";
        $str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
        $strLabel = "\n\t" . $this->labelTag( $tagName, $prompt );

        $divClass = "optional";

        if( $required )
        $divClass = "required";

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
    
    function generateSelectDiv($tagName, $prompt, $options, $selected=null, $selectAttr=null, $optionAttr=null, $required=false,  $errorMsg=null)
    {
        $selectAttr['id'] = strtolower(str_replace('/', '_',$tagName));
        $tagNameArray = explode('/', $tagName);
        
        $inflect = new Inflector();
        
        $model = str_replace('_id', '', $tagNameArray[1]);
        $properModel = $inflect->camelize($model);
        $controllerPath = strtolower(substr($inflect->pluralize($properModel), 0, 1)) . substr($inflect->pluralize($properModel), 1);
        $actionPath     = strtolower(substr($properModel, 0, 1)) . substr($properModel, 1) . 'List';
        $path = "/$controllerPath/$actionPath";
        
        if($selectAttr['multiple'] != 'multiple')
        {
        	$str = "\t<?php echo \$html->selectTag('{$tagName}', " . "\$this->requestAction('{$path}'), \$data['{$tagNameArray[0]}']['{$tagNameArray[1]}'], " . $this->attributesToArray($selectAttr) . ") ?>\n";
        	$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
        }
        else 
        {
        	$lowerName = strtolower($tagNameArray[0]);
        	$str = "\t<?php foreach (\$data['{$tagNameArray[0]}'] as \$var): \${$lowerName}Options[\$var['id']] = \$var['id']; endforeach; ?>\n";
        	$str .= "\t<?php echo \$html->selectTag('{$tagName}', " . "\$this->requestAction('{$path}'), \${$lowerName}Options, " . $this->attributesToArray($selectAttr) . ") ?>\n";
        	$str .= "\t<?php echo \$html->tagErrorMsg('{$tagName}', 'Error message for {$tagNameArray[1]} goes here.') ?>\n";
        }
        
        $strLabel = "\n\t" . $this->labelTag( $tagName, $prompt );

        $divClass = "optional";

        if( $required )
        $divClass = "required";

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
    
    function generateSubmitDiv($displayText, $htmlOptions = null)
    {
        return $this->divTag( 'submit', $this->Html->submitTag( $displayText, $htmlOptions) );
    }
    
    function labelTag( $tagName, $text )
    {
        return sprintf( TAG_LABEL, strtolower(str_replace('/', '_',$tagName)), $text ) . "\n";
    }
    
    function isFieldError($field )
    {
        $error = 1;
        $this->Html->setFormTag( $field );
        if( $error == $this->Html->tagIsInvalid( $this->Html->model, $this->Html->field) )
        {
            return true;
        }
		else
		{
            return false;
        }
    }
    
    function pTag( $class, $text )
    {
        return sprintf( TAG_P_CLASS, $class, $text ) . "\n";
    }
    
    function divTag( $class, $text )
    {
        return sprintf( TAG_DIV, $class, $text ) . "\n";
    }
    
    //=-=-=-=
    
    function attributesToArray($htmlAttributes)
    {
    	if (is_array($htmlAttributes))
    	{
    		$keys = array_keys($htmlAttributes);
    		$vals = array_values($htmlAttributes);
    		
    		$out = "array(";
    		
    		for($i = 0; $i < count($htmlAttributes); $i++)
    		{
    			//don't put vars in quotes
    			if(substr($vals[$i], 0, 1) != '$')
    			{
    				$out .= "'{$keys[$i]}' => '{$vals[$i]}', ";
    			}
    			else 
    			{
    				$out .= "'{$keys[$i]}' => {$vals[$i]}, ";
    			}
    		}
    		
    		//Chop off last comma
			if(substr($out, -3, 1) == ',')
			{
    			$out = substr($out, 0, strlen($out) - 2);
			}
    		$out .= ")";
    		
    		return $out;
    	}
    	else 
    	{
    		return 'array()';
    	}
    }
}

/*
@@@
Make options array in selectTag dynamic (create a listModels function in the controller and use requestAction?)

*/

?>