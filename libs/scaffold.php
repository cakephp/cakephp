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
 * Short description for class
 *
 * Long description for class
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      Cake v 1.0.0.172
 */
class Scaffold extends Object {
	
	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 */
	var $clazz = null;
	
	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 */
	var $actionView = null;
	
	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 */
	var $model = null;
	
	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 */
	var $controllerClass = null;
	
	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 */
	var $modelName = null;
	
	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 */
	var $scaffoldTitle = null;
	
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $base = false;
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $controller_class
	 * @param unknown_type $params
	 */
	function __construct($controller_class, $params)
	{
	    $this->clazz = $controller_class;
	    $this->actionView = $params['action'];
	    
	    $r = null;
	    if (!preg_match('/(.*)Controller/i', $this->clazz, $r))
	    {
	        die("Scaffold::__construct() : Can't get or parse class name.");
	    }
	    $this->model = strtolower(Inflector::singularize($r[1]));
	    $this->scaffoldTitle = $r[1];
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $params
	 */
	function constructClasses($params)
	{
	    $this->controllerClass = new $this->clazz();
	    $this->controllerClass->base = $this->base;
	    $this->controllerClass->params = $params;
	    $this->controllerClass->contructClasses();
	    $this->controllerClass->layout = 'scaffold';
	    $this->controllerClass->pageTitle = $this->scaffoldTitle;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $params
	 * @return unknown
	 */
	function scaffoldIndex($params)
	{
	    return $this->scaffoldList($params);		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $params
	 */
	function scaffoldShow($params)
	{
	    $this->controllerClass->params['data'] = $this->controllerClass->models[$this->model]->read();
	    $this->controllerClass->set('data', $this->controllerClass->params['data'] );
	    $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames( $this->controllerClass->params['data'], false ) );
	    $this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'show.thtml');
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $params
	 */
	function scaffoldList($params)
	{
	    $model = $this->model;
	    $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames(null,false) );
		$this->controllerClass->set('data', $this->controllerClass->models[$this->model]->findAll());
		$this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'list.thtml');		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $params
	 */
	function scaffoldNew($params)
	{
	    $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames() );
	    $this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'new.thtml');
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $params
	 */
	function scaffoldEdit($params)
	{
	    $this->controllerClass->params['data'] = $this->controllerClass->models[$this->model]->read();
	    //  generate the field names.
	    $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames($this->controllerClass->params['data']) );
	    $this->controllerClass->set('data', $this->controllerClass->params['data']);
	    $this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
	}

	
/**
 * Enter description here...
 *
 * @param unknown_type $params
 */
	function scaffoldCreate($params)
	{
	    $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames() );
	    $this->cleanUpDateFields();
	    
	    if ($this->controllerClass->models[$this->model]->save($this->controllerClass->params['data']))
	    {
	        $this->controllerClass->flash('Your '.$this->model.' has been saved.', '/'.$this->controllerClass->viewPath );
	    }
	    else
	    {
	         $this->controllerClass->set('data', $this->controllerClass->params['data']);
	         $this->controllerClass->validateErrors($this->controllerClass->models[$this->model]);
	         $this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'new.thtml');
	    }
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $params
	 */
	function scaffoldUpdate($params=array())
	{
	   //  clean up the date fields
      $this->cleanUpDateFields();
	   
	    $this->controllerClass->models[$this->model]->set($this->controllerClass->params['data']);
	    if ( $this->controllerClass->models[$this->model]->save())
	    {
	        $this->controllerClass->flash('The '.$this->model.' has been updated.','/'.$this->controllerClass->name);
	    }
	    else
	    {
	        $this->controllerClass->flash('There was an error updating the '.$this->model,'/'.$this->controllerClass->name);
	    }
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $params
	 */
	function scaffoldDestroy($params=array())
	{
	    $id = $params['pass'][0];
	    //  figure out what model and table we are working with
	    $controllerName = $this->controllerClass->name;
	    $table = Inflector::singularize($controllerName);
	    if ($this->controllerClass->models[$table]->del($id))
	    {
	        $this->controllerClass->flash('The '.$table.' with id: '.$id.' has been deleted.', '/'.$controllerName);
	    }
	    else
	    {
	        $this->controllerClass->flash('There was an error deleting the '.$table.' with the id '.$id, '/'.$controllerName);
	    }
	}
	
	function cleanUpDateFields() 
	{
	   //  clean up the date fields
	   $objModel = $this->controllerClass->models[$this->model];
	   foreach( $objModel->_table_info as $table )
	   {
	      foreach ($table as $field) 
	      {
	         if( 'date' == $field['type'] && isset($this->controllerClass->params['data'][$this->model][$field['name'].'_year'] ) )
	         {
	            $newDate = mktime( 0,0,0,
	                    $this->controllerClass->params['data'][$this->model][$field['name'].'_month'],
	                    $this->controllerClass->params['data'][$this->model][$field['name'].'_day'],
	                    $this->controllerClass->params['data'][$this->model][$field['name'].'_year'] );
	            $newDate = date( 'Y-m-d', $newDate );
	            $this->controllerClass->params['data'][$this->model][$field['name']] = $newDate;
	         }
	         else if( 'datetime' == $field['type'] && isset($this->controllerClass->params['data'][$this->model][$field['name'].'_year'] ) )
	         {
	            $hour = $this->controllerClass->params['data'][$this->model][$field['name'].'_hour'];
	            if( $hour != 12 && 'pm' == $this->controllerClass->params['data'][$this->model][$field['name'].'_meridian'] )
	            {
	               $hour = $hour + 12;
	            }
	            $newDate = mktime( $hour,
	                    $this->controllerClass->params['data'][$this->model][$field['name'].'_min'],
	                    0,
	                    $this->controllerClass->params['data'][$this->model][$field['name'].'_month'],
	                    $this->controllerClass->params['data'][$this->model][$field['name'].'_day'],
	                    $this->controllerClass->params['data'][$this->model][$field['name'].'_year'] );
	            $newDate = date( 'Y-m-d', $newDate );
	            $this->controllerClass->params['data'][$this->model][$field['name']] = $newDate;
	         }
	      }
	   }
	}
}

?>