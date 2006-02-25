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
        $this->__renderScaffold($params);
    }


/**
 * Private method to render Scaffold.
 *
 * @param array $params
 * @access private
 */
    function __renderScaffold($params)
    {
        $this->__scaffoldView($params);
    }

/**
 * Renders the List view as the default action (index).
 *
 * @param array $params
 * @return Scaffold::__scaffoldList();
 * @access private
 */
    function __scaffoldIndex($params)
    {
        $this->controllerClass->pageTitle = Inflector::humanize(Inflector::pluralize($this->modelKey));
        return $this->__scaffoldList($params);
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
    function __scaffoldList($params)
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
 * Creates a new scaffold.
 *
 * @param array $params
 * @return A rendered view with a form to create a new row in the Models database table
 * @access private
 */
    function __scaffoldNew($params)
    {
        if($this->controllerClass->_beforeScaffold('add'))
        {
            $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames() );
            $this->controllerClass->set('type', 'New');
            if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffold.new.thtml'))
            {
                return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffold.new.thtml');
            }
            elseif(file_exists(APP.'views'.DS.'scaffold'.DS.'scaffold.new.thtml'))
            {
                return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.'scaffold'.DS.'scaffold.new.thtml');
            }
            else
            {
                return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
            }
        }
        else if($this->controllerClass->_scaffoldError('add') === false)
        {
            return $this->__scaffoldError();
        }
    }

/**
 * Renders an Edit view for scaffolded Model.
 *
 * @param array $params
 * @return A rendered view with a form to edit a record in the Models database table
 * @access private
 */
    function __scaffoldEdit($params=array())
    {
        if($this->controllerClass->_beforeScaffold('edit'))
        {
            $this->controllerClass->params['data'] = $this->controllerClass->{$this->modelKey}->read();
            $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames($this->controllerClass->params['data']) );
            $this->controllerClass->set('type', 'Edit');
            $this->controllerClass->set('data', $this->controllerClass->params['data']);
            if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffold.edit.thtml'))
            {
                return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffold.edit.thtml');
            }
            elseif(file_exists(APP.'views'.DS.'scaffold'.DS.'scaffold.new.thtml'))
            {
                return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.'scaffold'.DS.'scaffold.edit.thtml');
            }
            else
            {
                return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
            }
        }
        else if($this->controllerClass->_scaffoldError('edit') === false)
        {
            return $this->__scaffoldError();
        }
    }


/**
 * Renders a "create new" view for scaffolded Model.
 *
 * @param array $params
 * @return success on save  new form if data is empty or if data does not validate
 * @access private
 */
    function __scaffoldCreate($params)
    {
        if($this->controllerClass->_beforeScaffold('create'))
        {
            if(empty($this->controllerClass->params['data']))
            {
                return $this->__scaffoldNew($params);
            }

            $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames() );
            $this->controllerClass->cleanUpFields();

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
                elseif(file_exists(APP.'views'.DS.'scaffold'.DS.'scaffold.new.thtml'))
                {
                    return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.'scaffold'.DS.'scaffold.new.thtml');
                }
                else
                {
                    return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
                }
            }
        }
        else if($this->controllerClass->_scaffoldError('create') === false)
        {
            return $this->__scaffoldError();
        }
    }

/**
 * Renders an update view for scaffolded Model.
 *
 * @param array $params
 * @return success on save  new form if data is empty or error if update fails
 * @access private
 */
    function __scaffoldUpdate($params=array())
    {
        if($this->controllerClass->_beforeScaffold('update'))
        {
            if(empty($this->controllerClass->params['data']))
            {
                return $this->__scaffoldNew($params);
            }

            $this->controllerClass->set('fieldNames', $this->controllerClass->generateFieldNames() );
            $this->controllerClass->cleanUpFields();
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
                if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffolds'.DS.'new.thtml'))
                {
                    return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffolds'.DS.'edit.thtml');
                }
                elseif(file_exists(APP.'views'.DS.'scaffold'.DS.'scaffold.new.thtml'))
                {
                    return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.'scaffold'.DS.'scaffold.edit.thtml');
                }
                else
                {
                    return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
                }
            }
        }
        else if($this->controllerClass->_scaffoldError('update') === false)
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
        if(file_exists(APP.'views'.DS.$this->viewPath.DS.'scaffolds'.DS.'new.thtml'))
        {
            return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.$this->viewPath.DS.'scaffolds'.DS.'scaffold_error.thtml');
        }
        elseif(file_exists(APP.'views'.DS.'scaffold'.DS.'scaffold.new.thtml'))
        {
            return $this->controllerClass->render($this->actionView, '', APP.'views'.DS.'scaffold'.DS.'scaffold_error.thtml');
        }
        else
        {
             return $this->controllerClass->render($this->actionView, '', LIBS.'view'.DS.'templates'.DS.'errors'.DS.'scaffold_error.thtml');
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
                            $this->__scaffoldList($params);
                        break;

                        case 'add':
                            $this->__scaffoldNew($params);
                        break;

                        case 'edit':
                            $this->__scaffoldEdit($params);
                        break;

                        case 'create':
                            $this->__scaffoldCreate($params);
                        break;

                        case 'update':
                            $this->__scaffoldUpdate($params);
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