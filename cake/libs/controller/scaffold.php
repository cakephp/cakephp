<?php
/* SVN FILE: $Id$ */

/**
 * Scaffold.
 *
 * Automatic forms and actions generation for rapid web application development.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2006, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2006, Cake Software Foundation, Inc.
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
 *  Name of view to render
 *
 * @var string
 */
    var $actionView = null;

/**
 * Class name of model
 *
 * @var unknown_type
 */
    var $modelKey = null;

/**
 * Controller object
 *
 * @var Controller
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
        $this->viewPath = Inflector::underscore($controller->name);
        $this->controllerClass->pageTitle = $this->scaffoldTitle;
        $this->_renderScaffold($params);
    }


/**
 * Private method to render Scaffold.
 *
 * @param array $params
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
        if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffold.show.thtml'))
        {
            return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffold.show.thtml');
        }
        return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'show.thtml');
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
        if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffold.list.thtml'))
        {
            return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffold.list.thtml');
        }
        return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'list.thtml');
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
        $this->controllerClass->set('type', 'New');
        if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffold.new.thtml'))
        {
            return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffold.new.thtml');
        }
        return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
    }

/**
 * Renders an Edit view for scaffolded Model.
 *
 * @param array $params
 * @return A rendered view with a form to edit a record in the Models database table
 * @access private
 */
    function _scaffoldEdit($params=array())
    {
        $this->controllerClass->params['data'] = $this->controllerClass->{$this->modelKey}->read();
        $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames($this->controllerClass->params['data']) );
        $this->controllerClass->set('type', 'Edit');
        $this->controllerClass->set('data', $this->controllerClass->params['data']);
        if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffold.edit.thtml'))
        {
            return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffold.edit.thtml');
        }
        return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
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
                $this->controllerClass->redirect(Inflector::underscore($this->controllerClass->viewPath));

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
            $this->controllerClass->set('type', 'New');
            if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffolds'.DS.'new.thtml'))
            {
                return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffolds'.DS.'new.thtml');
            }
            return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
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
        $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames() );
        $this->_cleanUpFields();

        $this->controllerClass->{$this->modelKey}->set($this->controllerClass->params['data']);

        if ( $this->controllerClass->{$this->modelKey}->save())
        {
            if(is_object($this->controllerClass->Session))
            {
                $this->controllerClass->Session->setFlash('Your '.Inflector::humanize($this->modelKey).' has been saved.', '/');
                $this->controllerClass->redirect(Inflector::underscore($this->controllerClass->viewPath));

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
                $this->controllerClass->Session->setFlash('Please correct errors below');
            }
            $this->controllerClass->validateErrors($this->controllerClass->{$this->modelKey});
            $this->controllerClass->set('data', $this->controllerClass->params['data']);
            $this->controllerClass->set('type', 'Edit');
            return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
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
                $this->controllerClass->redirect(Inflector::underscore($this->controllerClass->viewPath));

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
                $this->controllerClass->redirect(Inflector::underscore($this->controllerClass->viewPath));

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

        $this->controllerClass->constructClasses();
        if(isset($this->controllerClass->{$this->modelKey}->db))
        {
            if($params['action'] === 'index'  || $params['action'] === 'list' ||
                $params['action'] === 'show'    || $params['action'] === 'add' ||
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
                    return $this->cakeError('missingAction',
                                array(array('className' => Inflector::camelize($params['controller']."Controller"),
                                            'action' => $params['action'],
                                            'webroot' => $this->controllerClass->webroot)));
                }
        }
        else
        {
            return $this->cakeError('missingDatabase',
                        array(array('webroot' => $this->controllerClass->webroot)));
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
                    $newDate  = $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_year'].'-';
                    $newDate .= $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_month'].'-';
                    $newDate .= $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_day'].' ';
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_year']);
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_month']);
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_day']);
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_hour']);
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_min']);
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_meridian']);
                    $this->controllerClass->params['data'][$this->modelKey][$field['name']] = $newDate;
                }
                else if( 'datetime' == $field['type'] && isset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_year'] ) )
                {
                    $hour = $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_hour'];
                    if( $hour != 12 && 'pm' == $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_meridian'] )
                    {
                        $hour = $hour + 12;
                    }
                    $newDate  = $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_year'].'-';
                    $newDate .= $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_month'].'-';
                    $newDate .= $this->controllerClass->params['data'][$this->modelKey][$field['name'].'_day'].' ';
                    $newDate .= $hour.':'.$this->controllerClass->params['data'][$this->modelKey][$field['name'].'_min'].':00';
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_year']);
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_month']);
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_day']);
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_hour']);
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_min']);
                        unset($this->controllerClass->params['data'][$this->modelKey][$field['name'].'_meridian']);
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