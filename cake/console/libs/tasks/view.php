<?php
/* SVN FILE: $Id$ */
/**
 * The View Tasks handles creating and updating view files.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
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
 * @subpackage		cake.cake.console.libs.tasks
 * @since			CakePHP(tm) v 1.2
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Task class for creating and updating view files.
 *
 * @package		cake
 * @subpackage	cake.cake.console.libs.tasks
 */
class ViewTask extends BakeShell {


	function execute() {
		if(empty($this->args)) {
			$this->__interactive();
		}
	}

	function __interactive() {
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

			if ($enteredController == '' || intval($enteredController) > count($this->_controllerNames)) {
				$this->out('Error:');
				$this->out("The Controller name you supplied was empty, or the number \nyou selected was not an option. Please try again.");
				$enteredController = '';
			}
		}

		if (intval($enteredController) > 0 && intval($enteredController) <= count($this->_controllerNames) ) {
			$controllerName = $this->_controllerNames[intval($enteredController) - 1];
		} else {
			$controllerName = Inflector::camelize($enteredController);
		}

		$controllerPath = low(Inflector::underscore($controllerName));

		$doItInteractive = $this->in("Would you like bake to build your views interactively?\nWarning: Choosing no will overwrite {$controllerName} views if it exist.", array('y','n'), 'y');

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
					$this->__bake($controllerName, $controllerPath, $admin, $admin_url);
				}
				$this->__bake($controllerName, $controllerPath, null, null);

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
			$this->out("Action Name:	 $actionName");
			$this->out("Path:			 app/views/" . $controllerPath . DS . Inflector::underscore($actionName) . '.ctp');
			$this->hr();
			$looksGood = $this->in('Look okay?', array('y','n'), 'y');

			if (low($looksGood) == 'y' || low($looksGood) == 'yes') {
				$this->__bakeView($controllerName, $actionName);
			} else {
				$this->out('Bake Aborted.');
			}
		}
	}

	function __bake($controllerName, $controllerPath, $admin= null, $admin_url = null) {
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
		$singularName = $this->_singularName($currentModelName);
		$pluralName = $this->_pluralName($currentModelName);
		$singularHumanName = $this->_singularHumanName($currentModelName);
		$pluralHumanName = $this->_pluralHumanName($controllerName);

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
				$otherModelName = $this->_modelName($value['model']);
				$otherModelKey = Inflector::underscore($value['modelKey']);
				$otherModelObj =& ClassRegistry::getObject($otherModelKey);
				$otherControllerName = $this->_controllerName($value['modelKey']);
				$otherControllerPath = $this->_controllerPath($otherControllerName);
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
				$otherModelName = $this->_modelName($value['model']);
				$otherModelKey = Inflector::underscore($value['modelKey']);
				$otherModelObj =& ClassRegistry::getObject($value['modelKey']);
				$otherControllerName = $this->_controllerName($value['modelKey']);
				$otherControllerPath = $this->_controllerPath($otherControllerName);
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
		$viewView .= "\t\t<li><?php echo \$html->link('New " . $singularHumanName . "', array('action'=>'add')); ?> </li>\n";
		foreach( $fieldNames as $field => $value ) {
			if( isset( $value['foreignKey'] ) ) {
				$otherModelName = $this->_modelName($value['modelKey']);
				if($otherModelName != $currentModelName) {
					$otherControllerName = $this->_controllerName($otherModelName);
					$otherControllerPath = $this->_controllerPath($otherControllerName);
					$otherSingularHumanName = $this->_singularHumanName($value['controller']);
					$otherPluralHumanName = $this->_pluralHumanName($value['controller']);
					$viewView .= "\t\t<li><?php echo \$html->link('List " . $otherSingularHumanName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'index')); ?> </li>\n";
					$viewView .= "\t\t<li><?php echo \$html->link('New " . $otherPluralHumanName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'add')); ?> </li>\n";
				}
			}
		}
		$viewView .= "\t</ul>\n\n";
		$viewView .= "</div>\n";

		foreach ($modelObj->hasOne as $associationName => $relation) {
			$new = true;
			$otherModelName = $this->_modelName($relation['className']);
			$otherControllerName = $this->_controllerName($otherModelName);
			$otherControllerPath = $this->_controllerPath($otherControllerName);
			$otherSingularName = $this->_singularName($associationName);
			$otherPluralHumanName = $this->_pluralHumanName($associationName);
			$otherSingularHumanName = $this->_singularHumanName($associationName);
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
			$otherControllerName = $this->_controllerName($relation['className']);
			$otherControllerPath = $this->_controllerPath($otherControllerName);
			$otherSingularName = $this->_singularName($associationName);
			$otherPluralHumanName = $this->_pluralHumanName($associationName);
			$otherSingularHumanName = $this->_singularHumanName($associationName);
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
			$otherModelName = $this->_modelName($relation['className']);
			if($otherModelName != $currentModelName) {
				$otherControllerName = $this->_controllerName($otherModelName);
				$otherControllerPath = $this->_controllerPath($otherControllerName);
				$otherSingularName = $this->_singularName($associationName);
				$otherPluralName = $this->_pluralHumanName($associationName);
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
			$otherModelName = $this->_modelName($relation['className']);
			if($otherModelName != $currentModelName) {
				$otherControllerName = $this->_controllerName($otherModelName);
				$otherControllerPath = $this->_controllerPath($otherControllerName);
				$otherSingularName = $this->_singularName($associationName);
				$otherPluralName = $this->_pluralHumanName($associationName);
				$addView .= "\t\t<li><?php echo \$html->link('View " . $otherPluralName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'view'));?></li>\n";
				$addView .= "\t\t<li><?php echo \$html->link('Add " . $otherPluralName . "', array('controller'=> '{$otherControllerPath}', 'action'=>'add')); ?></li>\n";
			}
		}
		$addView .= "\t</ul>\n";
		$addView .= "</div>\n";

		//------------------------------------------------------------------------------------//

		$Folder =& new Folder(VIEWS . $controllerPath, true);
		if($path = $Folder->cd(VIEWS . $controllerPath)) {
			$path = $Folder->slashTerm(VIEWS . $controllerPath);
			$filename = $path . $admin . 'index.ctp';
			$this->createFile($filename, $indexView);
			$filename = $path . $admin . 'view.ctp';
			$this->createFile($filename, $viewView);
			$filename = $path . $admin . 'add.ctp';
			$this->createFile($filename, $addView);
			$filename = $path . $admin . 'edit.ctp';
			$this->createFile($filename, $editView);
		} else {
			return false;
		}
	}

/**
 * Assembles and writes a View file.
 *
 * @param string $controllerName
 * @param string $actionName
 * @param string $content
 */
	function __bakeView($controllerName, $actionName, $content = '') {
		$out = "<h2>{$actionName}</h2>\n";
		$out .= $content;
		if(!file_exists(VIEWS.$this->_controllerPath($controllerName))) {
			mkdir(VIEWS.$this->_controllerPath($controllerName));
		}
		$filename = VIEWS . $this->_controllerPath($controllerName) . DS . Inflector::underscore($actionName) . '.ctp';
		$Folder =& new Folder(VIEWS . $controllerPath, true);
		if($path = $Folder->cd(VIEWS . $controllerPath)) {
			$path = $Folder->slashTerm(VIEWS . $controllerPath);
			return $this->createFile($filename, $out);
		} else {
			return false;
		}
	}

/**
 * returns the fields to be display in the baked forms.
 *
 * @access private
 * @param array $fields
 */
	function inputs($fields = array()) {
		$displayFields = null;

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
				$fieldOptions = $this->_pluralName($options['model']);
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
 * outputs the a list of possible models or controllers from database
 *
 * @param string $useDbConfig
 * @param string $type = Models or Controllers
 * @return output
 */
	function __doList($useDbConfig = 'default') {
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
		$this->out('Possible Models based on your current database:');
		$this->_controllerNames = array();
		$count = count($tables);
		for ($i = 0; $i < $count; $i++) {
			$this->_controllerNames[] = $this->_controllerName($this->_modelName($tables[$i]));
			$this->out($i + 1 . ". " . $this->_controllerNames[$i]);
		}
	}
}