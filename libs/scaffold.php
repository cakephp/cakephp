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
 * Copyright (c) 2005, CakePHP Authors/Developers
 *
 * Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com>
 *            Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The MIT License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.libs
 * @since        Cake v 1.0.0.172
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * Enter description here...
  */
uses('model', 'template', 'inflector', 'object');

/**
 * Scaffolding is a set of automatic views, forms and controllers for starting web development work faster.
 *
 * Scaffold inspects your database tables, and making educated guesses, sets up a 
 * number of pages for each of your Models. These pages have data forms that work,
 * and afford the web developer an early look at the data, and the possibility to over-ride
 * scaffolded actions with custom-made ones.
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      Cake v 1.0.0.172
 */
class Scaffold extends Object {

	/**
	 * Name of controller class
	 *
	 * @var string
	 */
	var $clazz = null;

	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	var $actionView = null;

	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 */
	var $modelKey = null;

	/**
	 * Enter description here...
	 *
	 * @var Object
	 */
	var $controllerClass = null;

	/**
	 * Name of scaffolded Model
	 *
	 * @var string
	 */
	var $modelName = null;

	/**
	 * Title HTML element for current scaffolded view
	 *
	 * @var string
	 */
	var $scaffoldTitle = null;

	/**
  * Base URL
  *
  * @var string
  */
	var $base = false;

	/**
	 * Construct and set up given controller with given parameters.
	 *
	 * @param string $controller_class Name of controller
	 * @param array $params
	 */
	function __construct(&$controller_class)
	{
		$this->controllerClass =& $controller_class;
		$this->clazz =& $controller_class->name;
		$this->actionView =& $controller_class->action;
		$this->modelKey = Inflector::underscore(Inflector::singularize($this->clazz));
		$this->scaffoldTitle = Inflector::humanize($this->modelKey);
		$this->controllerClass->layout = 'scaffold';
		$this->controllerClass->pageTitle = $this->scaffoldTitle;
		$this->controllerClass->contructClasses();
	}

	/**
	 * Renders the List view as the default action (index).
	 *
	 * @param array $params
	 * @return boolean Success
	 */
	function scaffoldIndex($params)
	{
	    $this->controllerClass->pageTitle = Inflector::humanize(Inflector::pluralize($this->modelKey));
		return $this->scaffoldList($params);
	}

	/**
	 * Renders a Show view of scaffolded Model.
	 *
	 * @param array $params
	 */
	function scaffoldShow($params)
	{
		$this->controllerClass->params['data'] = $this->controllerClass->models[$this->modelKey]->read();
		$this->controllerClass->set('data', $this->controllerClass->params['data'] );
		$this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames( $this->controllerClass->params['data'], false ) );
		$this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'show.thtml');
	}

	/**
	 * Renders List view of scaffolded Model.
	 *
	 * @param array $params
	 */
	function scaffoldList($params)
	{
		$this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames(null,false) );
		$this->controllerClass->set('data', $this->controllerClass->models[$this->modelKey]->findAll());
		$this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'list.thtml');
	}

	/**
	 * Creates a new scaffold.
	 *
	 * @param array $params
	 */
	function scaffoldNew($params)
	{
		$this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames() );
		$this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'new.thtml');
	}

	/**
	 * Renders an Edit view for scaffolded Model.
	 *
	 * @param array $params
	 */
	function scaffoldEdit($params)
	{
		$this->controllerClass->params['data'] = $this->controllerClass->models[$this->modelKey]->read();
		//  generate the field names.
		$this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames($this->controllerClass->params['data']) );
		$this->controllerClass->set('data', $this->controllerClass->params['data']);
		$this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
	}


	/**
	 * Renders a "create new" view for scaffolded Model.
	 *
	 * @param array $params
	 */
	function scaffoldCreate($params)
	{
		$this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames() );
		$this->cleanUpFields();

		if ($this->controllerClass->models[$this->modelKey]->save($this->controllerClass->params['data']))
		{
			$this->controllerClass->flash('Your '.$this->modelKey.' has been saved.', '/'.Inflector::underscore($this->controllerClass->viewPath) );
		}
		else
		{
			$this->controllerClass->set('data', $this->controllerClass->params['data']);
			$this->controllerClass->validateErrors($this->controllerClass->models[$this->modelKey]);
			$this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'new.thtml');
		}
	}

	/**
	 * Renders an update view for scaffolded Model.
	 *
	 * @param array $params
	 */
	function scaffoldUpdate($params=array())
	{
		//  clean up the date fields
		$this->cleanUpFields();

		$this->controllerClass->models[$this->modelKey]->set($this->controllerClass->params['data']);
		if ( $this->controllerClass->models[$this->modelKey]->save())
		{
			$this->controllerClass->flash('The '.Inflector::humanize($this->modelKey).' has been updated.','/'.Inflector::underscore($this->controllerClass->viewPath));
		}
		else
		{
			$this->controllerClass->flash('There was an error updating the '.Inflector::humanize($this->modelKey),'/'.Inflector::underscore($this->controllerClass->viewPath));
		}
	}

	/**
	 * Performs a delete on given scaffolded Model.
	 *
	 * @param array $params
	 */
	function scaffoldDestroy($params=array())
	{
		$id = $params['pass'][0];
		//  figure out what model and table we are working with
		if ($this->controllerClass->models[$this->modelKey]->del($id))
		{
			$this->controllerClass->flash('The '.Inflector::humanize($this->modelKey).' with id: '.$id.' has been deleted.', '/'.Inflector::underscore($this->controllerClass->viewPath));
		}
		else
		{
			$this->controllerClass->flash('There was an error deleting the '.Inflector::humanize($this->modelKey).' with the id '.$id, '/'.Inflector::underscore($this->controllerClass->viewPath));
		}
	}
	/**
	 * Cleans up the date fields of current Model.
	 * 
	 *
	 */
	function cleanUpFields()
	{
		//  clean up the date fields
		$objModel = $this->controllerClass->models[$this->modelKey];
		foreach( $objModel->_table_info as $table )
		{
			foreach ($table as $field)
			{
				if( 'date' == $field['type'] && isset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_year'] ) )
				{
					$newDate = mktime( 0,0,0,
					$this->controllerClass->params['data'][$this->modelKey][$field['name'].'_month'],
					$this->controllerClass->params['data'][$this->modelKey][$field['name'].'_day'],
					$this->controllerClass->params['data'][$this->modelKey][$field['name'].'_year'] );
					$newDate = date( 'Y-m-d', $newDate );
					$this->controllerClass->params['data'][$this->modelKey][$field['name']] = $newDate;
				}
				else if( 'datetime' == $field['type'] && isset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_year'] ) )
				{
					$hour = $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_hour'];
					if( $hour != 12 && 'pm' == $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_meridian'] )
					{
						$hour = $hour + 12;
					}
					$newDate = mktime( $hour,
					$this->controllerClass->params['data'][$this->modelKey][$field['name'].'_min'],
					0,
					$this->controllerClass->params['data'][$this->modelKey][$field['name'].'_month'],
					$this->controllerClass->params['data'][$this->modelKey][$field['name'].'_day'],
					$this->controllerClass->params['data'][$this->modelKey][$field['name'].'_year'] );
					$newDate = date( 'Y-m-d', $newDate );
					$this->controllerClass->params['data'][$this->modelKey][$field['name']] = $newDate;
				}
				else if( 'tinyint(1)' == $field['type'] )
				{
					if( isset( $this->controllerClass->params['data'][$this->modelKey][$field['name']]) &&
					"on" == $this->controllerClass->params['data'][$this->modelKey][$field['name']] )
					{
						$this->controllerClass->params['data'][$this->modelKey][$field['name']] = true;
					}
					else
					{
						$this->controllerClass->params['data'][$this->modelKey][$field['name']] = false;
					}
				}
			}
		}
	}
}

?>