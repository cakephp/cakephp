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
        $this->controllerClass->pageTitle = 'Scaffold :: ' . Inflector::humanize($controller->action) . ' :: ' .
                                            Inflector::humanize(Inflector::pluralize($this->modelKey));
        $this->__scaffoldView($params);
    }

/**
 * Renders a Show view of scaffolded Model.
 *
 * @param array $params
 * @return A rendered view of a row from Models database table
 * @access private
 */
    function __scaffoldShow($params)
    {
        if($this->controllerClass->_beforeScaffold('show'))
        {
            $this->controllerClass->params['data'] = $this->controllerClass->{$this->modelKey}->read();
            $this->controllerClass->set('data', $this->controllerClass->params['data'] );
            $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames( $this->controllerClass->params['data'], false ) );
            if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffold.show.thtml'))
            {
                return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffold.show.thtml');
            }
            elseif(file_exists(APP.'views'.DS.'scaffold'.DS.'scaffold.show.thtml'))
            {
                return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.'scaffold'.DS.'scaffold.show.thtml');
            }
            else
            {
                return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'show.thtml');
            }
        }
        else if($this->controllerClass->_scaffoldError('show') === false)
        {
            return $this->__scaffoldError();
        }
    }

/**
 * Renders List view of scaffolded Model.
 *
 * @param array $params
 * @return A rendered view listing rows from Models database table
 * @access private
 */
    function __scaffoldIndex($params)
    {
        if($this->controllerClass->_beforeScaffold('index'))
        {
            $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames(null,false) );
            $this->controllerClass->set('data', $this->controllerClass->{$this->modelKey}->findAll());
            if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffold.list.thtml'))
            {
                return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffold.list.thtml');
            }
            elseif(file_exists(APP.'views'.DS.'scaffold'.DS.'scaffold.list.thtml'))
            {
                return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.'scaffold'.DS.'scaffold.list.thtml');
            }
            else
            {
                return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'list.thtml');
            }
        }
        else if($this->controllerClass->_scaffoldError('index') === false)
        {
            return $this->__scaffoldError();
        }
    }

/**
 * Renders an Add or Edit view for scaffolded Model.
 *
 * @param array $params
 * @param string $params add or new
 * @return A rendered view with a form to edit or add a record in the Models database table
 * @access private
 */
    function __scaffoldForm($params=array(), $type)
    {
        $thtml = 'edit';
        $form = 'Edit';

        if($type === 'add')
        {
            $thtml = 'new';
            $form = 'New';
        }

        if($this->controllerClass->_beforeScaffold($type))
        {
            if($type == 'edit')
            {
                $this->controllerClass->params['data'] = $this->controllerClass->{$this->modelKey}->read();
                $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames($this->controllerClass->params['data']) );
                $this->controllerClass->set('data', $this->controllerClass->params['data']);
            }
            else
            {
                $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames());
            }

            $this->controllerClass->set('type', $form);

            if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffold.'.$thtml.'.thtml'))
            {
                return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffold.'.$thtml.'.thtml');
            }
            elseif(file_exists(APP.'views'.DS.'scaffold'.DS.'scaffold.'.$thtml.'.thtml'))
            {
                return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.'scaffold'.DS.'scaffold.'.$thtml.'.thtml');
            }
            else
            {
                return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
            }
        }
        else if($this->controllerClass->_scaffoldError($type) === false)
        {
            return $this->__scaffoldError();
        }
    }


/**
 *
 * Saves or updates a model.
 *
 * @param array $params
 * @param string $type create or update
 * @return success on save/update, new/edit form if data is empty or error if save or update fails
 * @access private
 */
    function __scaffoldSave($params = array(), $type)
    {
        $thtml = 'edit';
        $form = 'Edit';
        $success = 'updated';
        $formError = 'edit';

        if($this->controllerClass->_beforeScaffold($type))
        {
            if(empty($this->controllerClass->params['data']))
            {
                if($type === 'create')
                {
                    $formError = 'add';
                }
                return $this->__scaffoldForm($params, $formError);
            }

            $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames() );
            $this->controllerClass->cleanUpFields();


            if($type == 'create')
            {
                $this->controllerClass->{$this->modelKey}->create();
                $thtml = 'new';
                $form = 'New';
                $success = 'saved';
            }

            if ($this->controllerClass->{$this->modelKey}->save($this->controllerClass->params['data']))
            {
                if(is_object($this->controllerClass->Session))
                {
                    $this->controllerClass->Session->setFlash('The '.Inflector::humanize($this->modelKey).' has been '.$success.'.', '/');
                    $this->controllerClass->redirect(Inflector::underscore($this->controllerClass->viewPath));
                }
                else
                {
                    return $this->controllerClass->flash('The '.Inflector::humanize($this->modelKey).' has been '.$success.'.','/'.
                                                                Inflector::underscore($this->controllerClass->viewPath));
                }
            }
            else
            {
                if(is_object($this->controllerClass->Session))
                {
                    $this->controllerClass->Session->setFlash('Please correct errors below');
                }
                $this->controllerClass->set('data', $this->controllerClass->params['data']);
                $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames($this->__rebuild($this->controllerClass->params['data'])));
                $this->controllerClass->validateErrors($this->controllerClass->{$this->modelKey});
                $this->controllerClass->set('type', $form);
                if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffolds'.DS.'scaffold.'.$thtml.'.thtml'))
                {
                    return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffolds'.DS.'scaffold.'.$thtml.'.thtml');
                }
                elseif(file_exists(APP.'views'.DS.'scaffold'.DS.'scaffold.'.$thtml.'.thtml'))
                {
                    return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.'scaffold'.DS.'scaffold.'.$thtml.'.thtml');
                }
                else
                {
                    return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
                }
            }
        }
        else if($this->controllerClass->_scaffoldError($type) === false)
        {
            return $this->__scaffoldError();
        }
    }

/**
 * Performs a delete on given scaffolded Model.
 *
 * @param array $params
 * @return success on delete error if delete fails
 * @access private
 */
    function __scaffoldDelete($params=array())
    {
        if($this->controllerClass->_beforeScaffold('delete'))
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
        else if($this->controllerClass->_scaffoldError('delete') === false)
        {
            return $this->__scaffoldError();
        }
    }

/**
 * Enter description here...
 *
 * @return unknown
 */
    function __scaffoldError()
    {
        if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffolds'.DS.'scaffold.error.thtml'))
        {
            return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffolds'.DS.'scaffold.error.thtml');
        }
        elseif(file_exists(APP.'views'.DS.'scaffold'.DS.'scaffold.error.thtml'))
        {
            return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.'scaffold'.DS.'scaffold.error.thtml');
        }
        else
        {
             return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'errors'.DS.'scaffold_error.thtml');
        }
    }

/**
 * When forms are submited the arrays need to be rebuilt if
 * an error occured, here the arrays are rebuilt to structure needed
 *
 * @param array $params data passed to forms
 * @return array rebuilds the association arrays to pass back to Controller::generateFieldNames()
 */
    function __rebuild($params)
    {
        foreach ($params as $model => $field)
        {
            if(!empty($field) && is_array($field))
            {
                $match = array_keys($field);
                if($model == $match[0])
                {
                    $count = 0;
                    foreach ($field[$model] as $value)
                    {
                        $params[$model][$count][$this->controllerClass->{$this->modelKey}->{$model}->primaryKey] = $value;
                        $count++;
                    }
                    unset($params[$model][$model]);
                }
            }
        }
        return $params;
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
    function __scaffoldView ($params)
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
                $params['action'] === 'update' || $params['action'] === 'delete')
                {
                    switch ($params['action'])
                    {
                        case 'index':
                            $this->__scaffoldIndex($params);
                        break;

                        case 'show':
                            $this->__scaffoldShow($params);
                        break;

                        case 'list':
                            $this->__scaffoldIndex($params);
                        break;

                        case 'add':
                            $this->__scaffoldForm($params, 'add');
                        break;

                        case 'edit':
                            $this->__scaffoldForm($params, 'edit');
                        break;

                        case 'create':
                            $this->__scaffoldSave($params, 'create');
                        break;

                        case 'update':
                            $this->__scaffoldSave($params, 'update');
                        break;

                        case 'delete':
                            $this->__scaffoldDelete($params);
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
}

?>