<?php
/* SVN FILE: $Id$ */

/**
 * Base controller class.
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
 * @subpackage   cake.cake.libs.controller
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Include files
 */
uses(DS.'controller'.DS.'component',DS.'model'.DS.'model', 'inflector', 'folder', DS.'view'.DS.'view');

/**
 * Controller
 *
 * Application controller (controllers are where you put all the actual code)
 * Provides basic functionality, such as rendering views (aka displaying templates).
 * Automatically selects model name from on singularized object class name
 * and creates the model object if proper class exists.
 *
 * @package    cake
 * @subpackage cake.cake.libs.controller
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
     * Web page title
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
    var $modelNames = array();


    /**
     * Enter description here...
     *
     * @var unknown_type
     * @access public
     */
    var $base = null;

    /**
     * Layout file to use (see /app/views/layouts/default.thtml)
     *
     * @var string
     * @access public
     */
    var $layout = 'default';

    /**
     * Automatically render the view (the dispatcher checks for this variable before running render())
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
     * Database configuration to use (see /config/database.php)
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
     * Enter description here...
     *
     * @var unknown_type
     */
    var $components = array();

    /**
     * Constructor.
     *
     */
    function __construct ()
    {
        if($this->name === null)
        {
            $r = null;
            if (!preg_match('/(.*)Controller/i', get_class($this), $r))
            {
                die("Controller::__construct() : Can't get or parse my own class name, exiting.");
            }
            $this->name = $r[1];
        }
        
        $this->viewPath = Inflector::underscore($this->name);
        $this->modelClass = Inflector::singularize($this->name);
        $this->modelKey  = Inflector::underscore($this->modelClass);
        parent::__construct();
    }

    /**
     * Enter description here...
     *
     */
    function constructClasses(){
        
        if (!empty($this->components))
        {
            $component =& new Component($this);
        }
        
        if (!empty($this->beforeFilter))
        {
            if(is_array($this->beforeFilter))
            {
                foreach($this->beforeFilter as $filter)
                {
                    if(is_callable(array($this,$filter)))
                    {
                        $this->$filter();
                    }
                }
            }
            else
            {
                if(is_callable(array($this,$this->beforeFilter)))
                {
                    $this->{$this->beforeFilter}();
                }
            }
        }
        
        if(empty($this->params['pass']))
        {
            $id = false;
        }
        else
        {
            $id = $this->params['pass'];
        }

        $dboFactory = DboFactory::getInstance($this->useDbConfig);
        $this->db =& $dboFactory;

        if (class_exists($this->modelClass) && ($this->uses === false))
        {
            $this->{$this->modelClass} =& new $this->modelClass($id);
            $this->modelNames[] = $this->modelClass;
        }
        elseif ($this->uses)
        {
            if (!$this->db)
            {
                die("Controller::__construct() : ".$this->name." controller needs database access, exiting.");
            }

            $uses = is_array($this->uses)? $this->uses: array($this->uses);

            foreach ($uses as $modelClass)
            {
                $modelKey  = Inflector::underscore($modelClass);

                if (class_exists($modelClass))
                {
                    $this->{$modelClass} =& new $modelClass($id);
                    $this->modelNames[] = $modelClass;
                }
                else
                {
                    die("Controller::__construct() : ".ucfirst($this->name)." requires missing model {$modelClass}, exiting.");
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
     * Gets an instance of the view object & prepares it for rendering the output, then
     * asks the view to actualy do the job.
     *
     * @param unknown_type $action
     * @param unknown_type $layout
     * @param unknown_type $file
     * @return unknown
     */
    function render($action=null, $layout=null, $file=null)
    {
        $view =& new View($this);

        if(!empty($this->modelNames))
        {
            foreach ($this->modelNames as $model)
            {
                if(!empty($this->{$model}->validationErrors))
                {
                    $view->validationErrors[$this->modelKey] =& $this->{$model}->validationErrors;
                }
            }
        }

        return  $view->render($action, $layout, $file);
    }

/**
 * Renders the Missing Controller web page.
 *
 */
   function missingController()
   {
       $this->autoLayout = true;
       $this->pageTitle = 'Missing Controller';
       $this->render('../errors/missingController');
       exit();
   }

/**
 * Renders the Missing Action web page.
 *
 */
   function missingAction()
   {
       $this->autoLayout = true;
       $this->pageTitle = 'Missing Method in Controller';
       $this->render('../errors/missingAction');
       exit();
   }
   
/**
 * Renders the Private Action web page.
 *
 */
   function privateAction()
   {
       $this->autoLayout = true;
       $this->pageTitle = 'Trying to access private method in class';
       $this->render('../errors/privateAction');
       exit();
   }
   
/**
 * Renders the Missing Database web page.
 *
 */
    function missingDatabase()
    {
        $this->autoLayout = true;
        $this->pageTitle = 'Scaffold Missing Database Connection';
        $this->render('../errors/missingScaffolddb');
        exit();
    }

/**
 * Renders the Missing Table web page.
 *
 */
    function missingTable($tableName)
    {
        $this->autoLayout = true;
        $this->missingTableName = $tableName;
        $this->pageTitle = 'Missing Database Table';
        $this->render('../errors/missingTable');
        exit();
    }
    
/**
 * Renders the Missing Helper file web page.
 *
 */
    function missingHelperFile($file)
    {
        $this->missingHelperFile = $file;
        $this->missingHelperClass = Inflector::camelize($file) . "Helper";
        $this->pageTitle = 'Missing Helper File';
        $this->render('../errors/missingHelperFile');
        exit();
    }
    
    
/**
 * Renders the Missing Helper class web page.
 *
 */
    function missingHelperClass($class)
    {
        $this->missingHelperClass = Inflector::camelize($class) . "Helper";
        $this->missingHelperFile = Inflector::underscore($class);
        $this->pageTitle = 'Missing Helper Class';
        $this->render('../errors/missingHelperClass');
        exit();
    }
    
/**
 * Renders the Missing Table web page.
 *
 */
    function missingConnection()
    {
        $this->autoLayout = true;
        $this->pageTitle = 'Missing Database Connection';
        $this->render('../errors/missingDatabase');
        exit();
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
     * Shows a message to the user $time seconds, then redirects to $url
     * Uses flash.thtml as a layout for the messages
     *
     * @param string $message Message to display to the user
     * @param string $url Relative URL to redirect to after the time expires
     * @param int $time Time to show the message
     */
    function flash($message, $url, $pause=1)
    {
        $this->autoRender = false;
        $this->autoLayout = false;

        $this->set('url', $this->base.$url);
        $this->set('message', $message);
        $this->set('pause', $pause);
        $this->set('page_title', $message);

		if(file_exists(VIEWS.'layouts'.DS.'flash.thtml'))
		{
		    $flash = VIEWS.'layouts'.DS.'flash.thtml';
		}
		else if(file_exists(LIBS.'view'.DS.'templates'.DS."layouts".DS.'flash.thtml'))
		{
		    $flash = LIBS.'view'.DS.'templates'.DS."layouts".DS.'flash.thtml';
		}
        
        
        
        $this->render(null,false,$flash);
    }

    /**
     * Shows a message to the user $time seconds, then redirects to $url
     * Uses flash.thtml as a layout for the messages
     *
     * @param string $message Message to display to the user
     * @param string $url URL to redirect to after the time expires
     * @param int $time Time to show the message
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
	    $fieldNames = array();
	    
	    $model = $this->modelClass;
	    $modelKey = $this->modelKey;
	    $table = $this->{$model}->table;
	    
	    $classRegistry =& ClassRegistry::getInstance();
	    $objRegistryModel = $classRegistry->getObject($modelKey);
	    
	    foreach ($objRegistryModel->_tableInfo as $tables)
	    {
	        foreach ($tables as $tabl)
	        {
	            //  set up the prompt
	            if( $objRegistryModel->isForeignKey($tabl['name']) )
	            {
	                $niceName = substr( $tabl['name'], 0, strpos( $tabl['name'], "_id" ) );
	                $fieldNames[ $tabl['name'] ]['prompt'] = Inflector::humanize($niceName);
	                //  this is a foreign key, also set up the other controller
	                $fieldNames[ $tabl['name'] ]['table'] = Inflector::pluralize($niceName);
	                $fieldNames[ $tabl['name'] ]['model'] = $this->{$model}->tableToModel[$fieldNames[ $tabl['name'] ]['table']];
	                $fieldNames[ $tabl['name'] ]['controller'] = Inflector::pluralize($this->{$model}->tableToModel[Inflector::pluralize($niceName)]);
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
	          $fieldNames[ $tabl['name']]['tagName'] = $modelKey.'/'.$tabl['name'];

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
                         $registry =& ClassRegistry::getInstance();
                         $otherModel =& $registry->getObject($fieldNames[ $tabl['name']]['model']);

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
                 case "tinyint":
                 {
                    if( $fieldLength > 1 )
                    {
                       $fieldNames[ $tabl['name']]['type'] = 'input';
                    }
                    else 
                    {
                       $fieldNames[ $tabl['name']]['type'] = 'checkbox';
                    }
                 }
                 break;
                 case "int":
                 case "smallint":
                 case "mediumint":
                 case "bigint":
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
                         $registry =& ClassRegistry::getInstance();
                         $otherModel =& $registry->getObject($fieldNames[ $tabl['name']]['model']);

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
                             $fieldNames[ $tabl['name']]['selected'] = $data[$modelKey][$tabl['name']];
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
            list($tableName) = $relation;

            $otherModelName = Inflector::underscore($tableName);
            $otherModel = new $otherModelName();

            if( $doCreateOptions )
              {
                  $otherDisplayField = $otherModel->getDisplayField();
                  $fieldNames[$tableName]['model'] = $tableName;
                  $fieldNames[$tableName]['prompt'] = "Related ".Inflector::humanize($tableName);
                  $fieldNames[$tableName]['type'] = "selectMultiple";
                  $fieldNames[$tableName]['tagName'] = $otherModelName.'/'.Inflector::underscore($tableName);

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