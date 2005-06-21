<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * Purpose: Controller
 * Application controller (controllers are where you put all the actual code) 
 * Provides basic functionality, such as rendering views (aka displaying templates).
 * Automatically selects model name from on singularized object class name 
 * and creates the model object if proper class exists.
 * 
 * @filesource 
 * @author Cake Authors/Developers
 * @copyright Copyright (c) 2005, Cake Authors/Developers
 * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs
 * @since Cake v 0.2.9
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

/**
 * Enter description here...
 */
uses('model', 'template', 'inflector', 'folder');

/**
 * Enter description here...
 *
 * @package cake
 * @subpackage cake.libs
 * @since Cake v 0.2.9
 *
 */
class Controller extends Template
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
	 * Constructor. 
	 *
	 */
	function __construct ($params=null)
	{
		$this->params = $params;

		$r = null;
		if (!preg_match('/(.*)Controller/i', get_class($this), $r))
		{
			die("Controller::__construct() : Can't get or parse my own class name, exiting.");
		}

		$this->name = strtolower($r[1]);
		$this->viewpath = Inflector::underscore($r[1]);

		$model_class = Inflector::singularize($this->name);

		if (class_exists($model_class) && $this->db && ($this->uses === false))
		{
			$this->$model_class = new $model_class ();
		}
		elseif ($this->uses)
		{
			if (!$this->db)
			die("Controller::__construct() : ".$this->name." controller needs database access, exiting.");

			$uses = is_array($this->uses)? $this->uses: array($this->uses);

			foreach ($uses as $model_name)
			{
				$model_class = ucfirst(strtolower($model_name));

				if (class_exists($model_class))
				{
					$this->$model_name = new $model_class (false);
				}
				else
				{
					die("Controller::__construct() : ".ucfirst($this->name)." requires missing model {$model_class}, exiting.");
				}
			}
		}

		parent::__construct();
	}

	function missingController()
	{
		$this->autoRender = false;
		$this->render('../errors/missingController');
	}

	function missingAction()
	{
		$this->autoRender = false;
		$this->render('../errors/missingAction');
	}

	function missingView()
	{
		$this->autoRender = false;
		$this->render('../errors/missingView');
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
	 * Returns value of $tag_name. False is the tag does not exist.
	 *
	 * @param string $tag_name
	 * @return unknown Value of the named tag.
	 */
	function tagValue ($tag_name)
	{
		return isset($this->params['data'][$tag_name])? $this->params['data'][$tag_name]: false;
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
	 * Returns a formatted error message for given FORM field, NULL if no errors.
	 *
	 * @param string $field
	 * @param string $text
	 * @return string If there are errors this method returns an error message, else NULL. 
	 */
	function tagErrorMsg ($field, $text)
	{
		$error = $this->tagIsInvalid($field);

		if (0 == $error)
		{
			return sprintf(SHORT_ERROR_MESSAGE, is_array($text)? (empty($text[$error-1])? 'Error in field': $text[$error-1]): $text);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Returns false if given FORM field has no errors. Otherwise it returns the constant set in the array Model->validationErrors.
	 *
	 * @param unknown_type $field
	 * @return unknown
	 */
	function tagIsInvalid ($field)
	{
		return empty($this->validationErrors[$field])? 0: $this->validationErrors[$field];
	}

	/**
	 * Displays an error page to the user. Uses layouts/error.html to render the page.
	 *
	 * @param int $code Error code (for instance: 404)
	 * @param string $name Name of the error (for instance: Not Found)
	 * @param string $message Error message
	 */
	function error ($code, $name, $message)
	{
		header ("HTTP/1.0 {$code} {$name}");
		print ($this->_render(VIEWS.'layouts/error.thtml', array('code'=>$code,'name'=>$name,'message'=>$message)));
	}

}

?>