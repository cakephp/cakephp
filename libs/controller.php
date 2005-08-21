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
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Enter description here...
 */
uses('model', 'inflector', 'folder', 'view', 'helper');


/**
 * Controller
 *
 * Application controller (controllers are where you put all the actual code) 
 * Provides basic functionality, such as rendering views (aka displaying templates).
 * Automatically selects model name from on singularized object class name 
 * and creates the model object if proper class exists.
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      CakePHP v 0.2.9
 *
 */
class Controller extends Object
{
    /**
     * Name of the controller.
     *
     * @var unknown_type
     * @access public
     */
    var $name = null;

    /**
     * Stores the current URL (for links etc.)
     *
     * @var string Current URL
     */
    var $here = null;

    /**
     * Enter description here...
     *
     * @var unknown_type
     * @access public
     */
    var $parent = null;

    /**
     * Action to be performed.
     *
     * @var string
     * @access public
     */
    var $action = null;

    /**
     * An array of names of models the particular controller wants to use.
     *
     * @var mixed A single name as a string or a list of names as an array.
     * @access protected
     */
    var $uses = false;

    /**
     * An array of names of built-in helpers to include.
     *
     * @var mixed A single name as a string or a list of names as an array.
     * @access protected
     */
    var $helpers = array('html');

    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    var $viewPath;

    /**
     * Variables for the view
     *
     * @var array
     * @access private
     */
    var $_viewVars = array();

    /**
     * Enter description here...
     *
     * @var boolean
     * @access private
     */
    var $pageTitle = false;

    /**
     * An array of model objects.
     *
     * @var array Array of model objects.
     * @access public
     */
    var $models = array();


    /**
     * Enter description here...
     *
     * @var unknown_type
     * @access public
     */
    var $base = null;

    /**
     * Enter description here...
     *
     * @var string
     * @access public
     */
    var $layout = 'default';

    /**
     * Enter description here...
     *
     * @var boolean
     * @access public
     */
    var $autoRender = true;

    /**
     * Enter description here...
     *
     * @var boolean
     * @access public
     */
    var $autoLayout = true;

    /**
     * Enter description here...
     *
     * @var string
     * @access public
     */
    var $useDbConfig = 'default';
    
    /**
     * Enter description here...
     *
     * @var string
     * @access public
     */
    var $beforeFilter = null;

    /**
     * Constructor. 
     *
     */
    function __construct ($params=null)
    {
        // parent::__construct();
        $r = null;
        if (!preg_match('/(.*)Controller/i', get_class($this), $r))
        {
            die("Controller::__construct() : Can't get or parse my own class name, exiting.");
        }

        $this->name = strtolower($r[1]);
        $this->viewPath = Inflector::underscore($r[1]);

        //Adding Before Filter check
	   if (!empty($this->beforeFilter))
	   {
	       if(is_array($this->beforeFilter))
	       {
	           // If is an array we will use a foreach and run these methods
	       }
	       else
	       {
	           // Run a single before filter
	       }    
	   }
        
        parent::__construct();
    }

    /**
     * Enter description here...
     *
     */
    function contructClasses(){

        if(empty($this->params['pass']))
        {
            $id = false;
        }
        else
        {
            $id = $this->params['pass'];
        }


        $model_class = Inflector::singularize($this->name);

        $this->db = DboFactory::getInstance($this->useDbConfig);

        if (class_exists($model_class) && ($this->uses === false))
        {
            $this->models[$model_class] = new $model_class($id);
        }
        elseif ($this->uses)
        {
            if (!$this->db)
            {
                die("Controller::__construct() : ".$this->name." controller needs database access, exiting.");
            }

            $uses = is_array($this->uses)? $this->uses: array($this->uses);

            foreach ($uses as $model_name)
            {
                $model_class = ucfirst(strtolower($model_name));

                if (class_exists($model_class))
                {
                    $this->models[$model_name] = new $model_class($id);
                }
                else
                {
                    die("Controller::__construct() : ".ucfirst($this->name)." requires missing model {$model_class}, exiting.");
                }
            }
        }
    }

    /**
     * Redirects to given $url, after turning off $this->autoRender.
     *
     * @param unknown_type $url
     */
    function redirect ($url)
    {
        $this->autoRender = false;
        header ('Location: '.$this->base.$url);
    }

    /**
     * Saves a variable to use inside a template.
     *
     * @param mixed $one A string or an array of data.
     * @param string $two Value in case $one is a string (which then works as the key), otherwise unused.
     * @return unknown
     */
    function set($one, $two=null)
    {
        return $this->_setArray(is_array($one)? $one: array($one=>$two));
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $action
     */
    function setAction ($action)
    {
        $this->action = $action;

        $args = func_get_args();
        call_user_func_array(array(&$this, $action), $args);
    }

    /**
     * Returns number of errors in a submitted FORM.
     *
     * @return int Number of errors
     */
    function validate ()
    {
        $args = func_get_args();
        $errors = call_user_func_array(array(&$this, 'validateErrors'), $args);

        return count($errors);
    }

    /**
     * Validates a FORM according to the rules set up in the Model.
     *
     * @return int Number of errors
     */
    function validateErrors ()
    {
        $objects = func_get_args();
        if (!count($objects)) return false;

        $errors = array();
        foreach ($objects as $object)
        {
            $errors = array_merge($errors, $object->invalidFields($object->data));
        }

        return $this->validationErrors = (count($errors)? $errors: false);
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $action
     * @param unknown_type $layout
     * @param unknown_type $file
     * @return unknown
     */
    function render($action=null, $layout=null, $file=null)
    {
        $view                =  View::getInstance();
        $view->_viewVars     =& $this->_viewVars;
        $view->action        =& $this->action;
        $view->autoLayout    =& $this->autoLayout;
        $view->autoRender    =& $this->autoRender;
        $view->base          =& $this->base;
        $view->helpers       =& $this->helpers;
        $view->here          =& $this->here;
        $view->layout        =& $this->layout;
        $view->models        =& $this->models;
        $view->name          =& $this->name;
        $view->pageTitle     =& $this->pageTitle;
        $view->parent        =& $this->parent;
        $view->viewPath      =& $this->viewPath;
        $view->params        =& $this->params;
        $view->data          =& $this->data;
        $view->displayFields =& $this->displayFields;

        if(!empty($this->models))
        {
            foreach ($this->models as $key => $value)
            {
                if(!empty($this->models[$key]->validationErrors))
                {
                    $view->validationErrors[$key] =& $this->models[$key]->validationErrors;
                }
            }
        }

        return  $view->render($action, $layout, $file);
    }

    /**
     * Enter description here...
     *
     */
    function missingController()
    {
        //We are simulating action call below, this is not a filename!
        $this->render('../errors/missingController');
    }

    /**
     * Enter description here...
     *
     */
    function missingAction()
    {
        //We are simulating action call below, this is not a filename!
        $this->render('../errors/missingAction');
    }

    /**
     * Enter description here...
     *
     */
    function missingView()
    {
        //We are simulating action call below, this is not a filename!
        $this->render('../errors/missingView');
    }

    //   /**
    //    * Displays an error page to the user. Uses layouts/error.html to render the page.
    //    *
    //    * @param int $code Error code (for instance: 404)
    //    * @param string $name Name of the error (for instance: Not Found)
    //    * @param string $message Error message
    //    */
    //      function error ($code, $name, $message)
    //      {
    //         header ("HTTP/1.0 {$code} {$name}");
    //         print ($this->_render(VIEWS.'layouts/error.thtml', array('code'=>$code,'name'=>$name,'message'=>$message)));
    //      }

    /**
     * Sets data for this view. Will set title if the key "title" is in given $data array.
     *
     * @param array $data Array of 
     * @access private
     */
    function _setArray($data)
    {
        foreach ($data as $name => $value)
        {
            if ($name == 'title')
            $this->_setTitle($value);
            else
            $this->_viewVars[$name] = $value;
        }
    }

    /**
     * Set the title element of the page.
     *
     * @param string $pageTitle Text for the title
     * @access private
     */
    function _setTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $message
     * @param unknown_type $url
     * @param unknown_type $time
     */
    function flash($message, $url, $time=1)
    {
        $this->autoRender = false;
        $this->autoLayout = false;

        $this->set('url', $this->base.$url);
        $this->set('message', $message);
        $this->set('time', $time);

        $this->render(null,false,VIEWS.'layouts'.DS.'flash.thtml');
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $message
     * @param unknown_type $url
     * @param unknown_type $time
     */
    function flashOut($message, $url, $time=1)
    {
        $this->autoRender = false;
        $this->autoLayout = false;

        $this->set('url', $url);
        $this->set('message', $message);
        $this->set('time', $time);

        $this->render(null,false,VIEWS.'layouts'.DS.'flash.thtml');
    }
    
    /**
	 * This function creates a $fieldNames array for the view to use.
	 * @todo Map more database field types to html form fields.
	 * @todo View the database field types from all the supported databases.
	 *
	 */
	function generateFieldNames( $data = null, $doCreateOptions = true  ) 
	{
	    //  Initialize the list array
	    $fieldNames = array();
	    
	    //  figure out what model and table we are working with
	    $model = Inflector::pluralize($this->name);
	    $table = Inflector::singularize($this->name);

	    //  get all of the column names.
	    $classRegistry = ClassRegistry::getInstance();
	    $objRegistryModel = $classRegistry->getObject($table);
	    
	    foreach ($objRegistryModel->_table_info as $tables)
	    {
	        foreach ($tables as $tabl)
	        {
	            //  set up the prompt
	            if( Model::isForeignKey($tabl['name']) )
	            {
	                $niceName = substr( $tabl['name'], 0, strpos( $tabl['name'], "_id" ) );
	                $fieldNames[ $tabl['name'] ]['prompt'] = Inflector::humanize($niceName);
	                //  this is a foreign key, also set up the other controller
	                $fieldNames[ $tabl['name'] ]['model'] = Inflector::singularize($niceName);
	                $fieldNames[ $tabl['name'] ]['controller'] = Inflector::pluralize($niceName);
	                $fieldNames[ $tabl['name'] ]['foreignKey'] = true;
	         }
	         else if( 'created' != $tabl['name'] && 'updated' != $tabl['name'] )
	         {
	             $fieldNames[$tabl['name']]['prompt'] = Inflector::humanize($tabl['name']);
	         }
	         else if( 'created' == $tabl['name'] )
	         {
	             $fieldNames[$tabl['name']]['prompt'] = 'Created';
	         }
	         else if( 'updated' == $tabl['name'] )
	         {
	             $fieldNames[$tabl['name']]['prompt'] = 'Modified';
	         }
	         
	         // Now, set up some other attributes that will be useful for auto generating a form.
	         //tagName is in the format table/field "post/title"
	          $fieldNames[ $tabl['name']]['tagName'] = $table.'/'.$tabl['name'];

	         //  Now, find out if this is a required field.
	         //$validationFields = $classRegistry->getObject($table)->validate;
	         $validationFields = $objRegistryModel->validate;
	         if( isset( $validationFields[ $tabl['name'] ] ) )
	         {
	            //  Now, we know that this field has some validation set.
	            //  find out if it is a required field.
	            if( VALID_NOT_EMPTY == $validationFields[ $tabl['name'] ] ) 
	            {
	                //  this is a required field.
	                $fieldNames[$tabl['name']]['required'] = true;
	                $fieldNames[$tabl['name']]['errorMsg'] = "Required Field";
	            }
	         }
	         
	         //  now, determine what the input type should be for this database field.
	         $lParenPos = strpos( $tabl['type'], '(');
	         $rParenPos = strpos( $tabl['type'], ')');
	         if( false != $lParenPos )
	         {
	             $type = substr($tabl['type'], 0, $lParenPos );
	             $fieldLength = substr( $tabl['type'], $lParenPos+1, $rParenPos - $lParenPos -1 );
	         }
	         else
	         {
	             $type = $tabl['type'];
	         }
	         switch( $type )
	         {
	             case "text":
	             {	                                 
	                 $fieldNames[ $tabl['name']]['type'] = 'area';
	                 //$fieldNames[ $tabl['name']]['size'] = $fieldLength;
                 }
                 break;
                 case "varchar":
                 {
                     if( isset( $fieldNames[ $tabl['name']]['foreignKey'] ) )
                     {
                         $fieldNames[ $tabl['name']]['type'] = 'select';
                         //  This is a foreign key select dropdown box.  now, we have to add the options.
                         $fieldNames[ $tabl['name']]['options'] = array();
                         
                         //  get the list of options from the other model.
                         $registry = ClassRegistry::getInstance();
                         $otherModel = $registry->getObject($fieldNames[ $tabl['name']]['model']);
                         
                         if( is_object($otherModel) ) 
                         {
                             if( $doCreateOptions )
                             {
                                 $otherDisplayField = $otherModel->getDisplayField();
                                 foreach( $otherModel->findAll() as $pass )
                                 {
                                     foreach( $pass as $key=>$value ) 
                                     {
                                         if( $key == $fieldNames[ $tabl['name']]['model'] && isset( $value['id'] ) && isset( $value[$otherDisplayField] ) ) 
                                         {
                                             $fieldNames[ $tabl['name']]['options'][$value['id']] = $value[$otherDisplayField];
                                         }
                                     }
                                 }
                             }
                             $fieldNames[ $tabl['name']]['selected'] = $data[$table][$tabl['name']];
                         }
                     }
                     else 
                     {
                         $fieldNames[ $tabl['name']]['type'] = 'input';
                     }
                 }
                 break;
                 case "int":
                 case "decimal":
                 case "float":
                 case "double":
                 {
                     //BUGBUG:  Need a better way to determine if this field is an auto increment foreign key.
                     //  If it is a number, and it is a foreign key, we'll make a HUGE assumption that it is an auto increment field.
                     //  for foreign key autonumber fields, we'll set the type to 'key' so that it does not display in the input form.
                     if( 0 == strncmp($tabl['name'], 'id', 2) )
                     {
                         $fieldNames[ $tabl['name']]['type'] = 'hidden';
                     }
                     else if( isset( $fieldNames[ $tabl['name']]['foreignKey'] ) )
                     {
                         $fieldNames[ $tabl['name']]['type'] = 'select';
                         //  This is a foreign key select dropdown box.  now, we have to add the options.
                         $fieldNames[ $tabl['name']]['options'] = array();
                         
                         //  get the list of options from the other model.
                         $registry = ClassRegistry::getInstance();
                         $otherModel = $registry->getObject($fieldNames[ $tabl['name']]['model']);
                         
                         if( is_object($otherModel) ) 
                         {
                             if( $doCreateOptions ) 
                             {
                                 $otherDisplayField = $otherModel->getDisplayField();
                                 foreach( $otherModel->findAll() as $pass )
                                 {
                                     foreach( $pass as $key=>$value ) 
                                     {
                                         if( $key == $fieldNames[ $tabl['name']]['model'] && isset( $value['id'] ) && isset( $value[$otherDisplayField] ) ) 
                                         {
                                             $fieldNames[ $tabl['name']]['options'][$value['id']] = $value[$otherDisplayField];
                                         }
                                     }
                                 }
                             }
                             $fieldNames[ $tabl['name']]['selected'] = $data[$table][$tabl['name']];
                         }
                     }
                     else 
                     {
                         $fieldNames[ $tabl['name']]['type'] = 'input';
                     }
                 }
                 break;
                 case "enum":
                 {
                     //  for enums, the $fieldLength variable is actually the list of enums.
                     $fieldNames[ $tabl['name']]['type'] = 'select';
                     //  This is a foreign key select dropdown box.  now, we have to add the options.
                     $fieldNames[ $tabl['name']]['options'] = array();
                     
                     $enumValues = split(',', $fieldLength );
                     $iCount = 1;
                     foreach ($enumValues as $enum ) 
                     {
                         $enum = trim( $enum, "'" );
                         $fieldNames[$tabl['name']]['options'][$enum] = $enum;
                     }
                     $fieldNames[ $tabl['name']]['selected'] = $data[$table][$tabl['name']];

               }
               break;
               case "date":
               case "datetime":
               {
                  if( 0 != strncmp( "created", $tabl['name'], 6 ) && 0 != strncmp("modified",$tabl['name'], 8) )
                  $fieldNames[ $tabl['name']]['type'] = $type;
               } 
               break;
               default:
               //sorry, this database field type is not yet set up.
                  break;	            


	         } // end switch
         }
   	    // now, add any necessary hasAndBelongsToMany list boxes
   	    //  loop through the many to many relations to make a list box.
      	foreach( $objRegistryModel->_manyToMany as $relation ) 
         {
            list($tableName, $field, $value, $joinTable, $key1, $key2) = $relation;

            $otherModelName = Inflector::singularize($tableName);
            $otherModel = new $otherModelName();
            
            if( $doCreateOptions ) 
              {
                  $otherDisplayField = $otherModel->getDisplayField();
                  $fieldNames[$tableName]['model'] = $tableName;
                  $fieldNames[$tableName]['prompt'] = "Related ".Inflector::humanize($tableName);
                  $fieldNames[$tableName]['type'] = "selectMultiple";
                  $fieldNames[$tableName]['tagName'] = $tableName;
                  
                  foreach( $otherModel->findAll() as $pass )
                  {
                      foreach( $pass as $key=>$value ) 
                      {
                          if( $key == $otherModelName && isset( $value['id'] ) && isset( $value[$otherDisplayField] ) ) 
                          {
                              $fieldNames[$tableName]['options'][$value['id']] = $value[$otherDisplayField];
                          }
                      }
                  }
                  if( isset( $data[$tableName] ) )
                  {
                    foreach( $data[$tableName] as $row )
                    {
                       $fieldNames[$tableName]['selected'][$row['id']] = $row['id'];
                    }
                  }
              }
         } // end loop through manytomany relations.
	    }
	    

	    
      return $fieldNames;
	}    
}

?>