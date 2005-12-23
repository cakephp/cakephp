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
 * @subpackage   cake.cake.libs.controller
 * @since        Cake v 0.10.0.1076
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * Enter description here...
  */
uses(DS.'model'.DS.'model', 'inflector', 'object');

/**
 * Scaffolding is a set of automatic views, forms and controllers for starting web development work faster.
 *
 * Scaffold inspects your database tables, and making educated guesses, sets up a
 * number of pages for each of your Models. These pages have data forms that work,
 * and afford the web developer an early look at the data, and the possibility to over-ride
 * scaffolded actions with custom-made ones.
 *
 * @package    cake
 * @subpackage cake.cake.libs.controller
 * @since      Cake v 0.10.0.1076
 */
class Scaffold extends Object {

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
    function __construct(&$controller, $params)
    {
        $this->controllerClass =& $controller;
		$this->actionView = $controller->action;
		$this->modelKey = Inflector::singularize($controller->name);
		$this->scaffoldTitle = Inflector::humanize($this->modelKey);
		$this->controllerClass->pageTitle = $this->scaffoldTitle;
		$this->_renderScaffold($params);
	}


/**
 * Enter description here...
 *
 * @param unknown_type $params
 * @access private
 */
    function _renderScaffold($params)
    {
        $this->_scaffoldView($params);
    }

/**
 * Renders the List view as the default action (index).
 *
 * @param array $params
 * @return Scaffold::_scaffoldList();
 * @access private
 */
    function _scaffoldIndex($params)
    {
        $this->controllerClass->pageTitle = Inflector::humanize(Inflector::pluralize($this->modelKey));
        return $this->_scaffoldList($params);
    }

/**
 * Renders a Show view of scaffolded Model.
 *
 * @param array $params
 * @return A rendered view of a row from Models database table
 * @access private
 */
    function _scaffoldShow($params)
    {
        $this->controllerClass->params['data'] = $this->controllerClass->{$this->modelKey}->read();
        $this->controllerClass->set('data', $this->controllerClass->params['data'] );
        $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames( $this->controllerClass->params['data'], false ) );
        return $this->controllerClass->render($this->actionView, '', LIBS.'controller'.DS.'templates'.DS.'scaffolds'.DS.'show.thtml');
    }

/**
 * Renders List view of scaffolded Model.
 *
 * @param array $params
 * @return A rendered view listing rows from Models database table
 * @access private
 */
    function _scaffoldList($params)
    {
        $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames(null,false) );
        $this->controllerClass->set('data', $this->controllerClass->{$this->modelKey}->findAll());
        return $this->controllerClass->render($this->actionView, '', LIBS.'controller'.DS.'templates'.DS.'scaffolds'.DS.'list.thtml');
    }

/**
 * Creates a new scaffold.
 *
 * @param array $params
 * @return A rendered view with a form to create a new row in the Models database table
 * @access private
 */
    function _scaffoldNew($params)
    {
        $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames() );
        return $this->controllerClass->render($this->actionView, '', LIBS.'controller'.DS.'templates'.DS.'scaffolds'.DS.'new.thtml');
    }

/**
 * Renders an Edit view for scaffolded Model.
 *
 * @param array $params
 * @return A rendered view with a form to edit a record in the Models database table
 * @access private
 */
    function _scaffoldEdit($params)
    {
        $this->controllerClass->params['data'] = $this->controllerClass->{$this->modelKey}->read();
        $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames($this->controllerClass->params['data']) );
        $this->controllerClass->set('data', $this->controllerClass->params['data']);
        return $this->controllerClass->render($this->actionView, '', LIBS.'controller'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
    }


/**
 * Renders a "create new" view for scaffolded Model.
 *
 * @param array $params
 * @return success on save  new form if data is empty or if data does not validate
 * @access private
 */
    function _scaffoldCreate($params)
    {
        if(empty($this->controllerClass->params['data']))
        {
            return $this->_scaffoldNew($params);
        }

        $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames() );
        $this->_cleanUpFields();

        if ($this->controllerClass->{$this->modelKey}->save($this->controllerClass->params['data']))
        {
            if(is_object($this->controllerClass->Session))
            {
                $this->controllerClass->Session->setFlash('Your '.Inflector::humanize($this->modelKey).' has been saved.');
                $this->controllerClass->redirect('/'.Inflector::underscore($this->controllerClass->viewPath));

            }
            else
            {
            return $this->controllerClass->flash('Your '.Inflector::humanize($this->modelKey).' has been saved.', '/'.
                                                Inflector::underscore($this->controllerClass->viewPath) );
            }
        }
        else
        {
            if(is_object($this->controllerClass->Session))
            {
                $this->controllerClass->Session->setFlash('Please correct errors below');
            }
            $this->controllerClass->set('data', $this->controllerClass->params['data']);
            $this->controllerClass->validateErrors($this->controllerClass->{$this->modelKey});
            return $this->controllerClass->render($this->actionView, '', LIBS.'controller'.DS.'templates'.DS.'scaffolds'.DS.'new.thtml');
        }
    }

/**
 * Renders an update view for scaffolded Model.
 *
 * @param array $params
 * @return success on save  new form if data is empty or error if update fails
 * @access private
 */
    function _scaffoldUpdate($params=array())
    {
        if(empty($this->controllerClass->params['data']))
        {
            return $this->_scaffoldNew($params);
        }

        $this->_cleanUpFields();
        $this->controllerClass->{$this->modelKey}->set($this->controllerClass->params['data']);

        if ( $this->controllerClass->{$this->modelKey}->save())
        {
            if(is_object($this->controllerClass->Session))
            {
                $this->controllerClass->Session->setFlash('Your '.Inflector::humanize($this->modelKey).' has been saved.', '/');
                $this->controllerClass->redirect('/'.Inflector::underscore($this->controllerClass->viewPath));

            }
            else
            {
            return $this->controllerClass->flash('The '.Inflector::humanize($this->modelKey).' has been updated.','/'.
                                                 Inflector::underscore($this->controllerClass->viewPath));
            }
        }
        else
		{
            if(is_object($this->controllerClass->Session))
            {
                $this->controllerClass->Session->setFlash('The '.Inflector::humanize($this->modelKey).' has been updated.','/');
                $this->controllerClass->redirect('/'.Inflector::underscore($this->controllerClass->viewPath));

            }
            else
            {
		    return $this->controllerClass->flash('There was an error updating the '.Inflector::humanize($this->modelKey),'/'.
			                                     Inflector::underscore($this->controllerClass->viewPath));
            }
		}
    }

/**
 * Performs a delete on given scaffolded Model.
 *
 * @param array $params
 * @return success on delete error if delete fails
 * @access private
 */
    function _scaffoldDestroy($params=array())
    {
        $id = $params['pass'][0];
        if ($this->controllerClass->{$this->modelKey}->del($id))
        {
            if(is_object($this->controllerClass->Session))
            {
                $this->controllerClass->Session->setFlash('The '.Inflector::humanize($this->modelKey).' with id: '.$id.' has been deleted.', '/');
                $this->controllerClass->redirect('/'.Inflector::underscore($this->controllerClass->viewPath));

            }
            else
            {
            return $this->controllerClass->flash('The '.Inflector::humanize($this->modelKey).' with id: '.
                                                $id.' has been deleted.', '/'.Inflector::underscore($this->controllerClass->viewPath));
            }
        }
        else
        {
            if(is_object($this->controllerClass->Session))
            {
                $this->controllerClass->Session->setFlash('There was an error deleting the '.Inflector::humanize($this->modelKey).' with the id '.$id, '/');
                $this->controllerClass->redirect('/'.Inflector::underscore($this->controllerClass->viewPath));

            }
            else
            {
           return $this->controllerClass->flash('There was an error deleting the '.Inflector::humanize($this->modelKey).' with the id '.
                                                $id, '/'.Inflector::underscore($this->controllerClass->viewPath));
            }
        }
    }

/**
 * When methods are now present in a controller
 * scaffoldView is used to call default Scaffold methods if:
 * <code>
 * var $scaffold;
 * </code>
 * is placed in the controller's class definition.
 *
 * @param string $url
 * @param string $controller_class
 * @param array $params
 * @since Cake v 0.10.0.172
 * @access private
 */
    function _scaffoldView ($params)
    {
        if(!in_array('Form', $this->controllerClass->helpers))
        {
            $this->controllerClass->helpers[] = 'Form';
        }

        $isDataBaseSet = DboFactory::getInstance($this->controllerClass->useDbConfig);
        if(!empty($isDataBaseSet))
        {
            $this->controllerClass->constructClasses();

            if($params['action'] === 'index'  || $params['action'] === 'list' ||
               $params['action'] === 'show'   || $params['action'] === 'add' ||
               $params['action'] === 'create' || $params['action'] === 'edit' ||
               $params['action'] === 'update' || $params['action'] === 'destroy')
            {
                switch ($params['action'])
                {
                    case 'index':
                        $this->_scaffoldIndex($params);
                    break;

                    case 'show':
                        $this->_scaffoldShow($params);
                    break;

                    case 'list':
                        $this->_scaffoldList($params);
                    break;

                    case 'add':
                        $this->_scaffoldNew($params);
                    break;

                    case 'edit':
                        $this->_scaffoldEdit($params);
                    break;

                    case 'create':
                        $this->_scaffoldCreate($params);
                    break;

                    case 'update':
                        $this->_scaffoldUpdate($params);
                    break;

                    case 'destroy':
                        $this->_scaffoldDestroy($params);
                    break;
                }
            }
            else
            {
                $this->controllerClass->layout = 'default';
                $this->controllerClass->missingAction = $params['action'];
                call_user_func_array(array($this->controllerClass, 'missingAction'), null);
                exit;
            }
        }
        else
        {
            $this->controllerClass->constructClasses();
            $this->controllerClass->layout = 'default';
            call_user_func_array(array($this->controllerClass, 'missingDatabase'), null);
            exit;
        }
    }

/**
 * Cleans up the date fields of current Model.
 *
 * @access private
 */
    function _cleanUpFields()
    {

        foreach( $this->controllerClass->{$this->modelKey}->_tableInfo as $table )
        {
            foreach ($table as $field)
            {
                if('date' == $field['type'] && isset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_year']))
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
                    $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_min'],0,
                    $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_month'],
                    $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_day'],
                    $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_year'] );
                    $newDate = date( 'Y-m-d H:i:s', $newDate );
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