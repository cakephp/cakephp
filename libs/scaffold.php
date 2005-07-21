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
  * Purpose: Scaffold
  * 
  * 
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.172
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
  */

/**
  * Enter description here...
  */
uses('model', 'template', 'inflector', 'object');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.172
  *
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
	 * @param unknown_type $action
	 */
	function __construct($controller_class, $params){
		$this->clazz = $controller_class;
		$this->actionView = $params['action'];
		
			$r = null;
			if (!preg_match('/(.*)Controller/i', $this->clazz, $r))
			die("Scaffold::__construct() : Can't get or parse class name.");
			$this->model = strtolower(Inflector::singularize($r[1]));
			$this->scaffoldTitle = Inflector::toString($this) . ' ' . $r[1];
	}
	
	function constructClasses($params){

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
	 * @return unknown
	 */
	function showScaffoldIndex($params){
		return $this->showScaffoldList($params);		
	}
	
	/**
	 * Enter description here...
	 *
	 */
	function showScaffoldShow($params){
			$model = $this->model;
			$this->controllerClass->set('data', $this->controllerClass->models[$model]->read());
			$this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'show.thtml');
	}
	
	/**
	 * Enter description here...
	 *
	 */
	function showScaffoldList($params){
		$this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'list.thtml');
				
	}
	
	/**
	 * Enter description here...
	 *
	 */
	function showScaffoldNew($params){
		
				$this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'new.thtml');
	}
	
	/**
	 * Enter description here...
	 *
	 */
	function showScaffoldEdit($params){
			$model = $this->model;
			$this->controllerClass->set('data', $this->controllerClass->models[$model]->read());
			$this->controllerClass->render($this->actionView, '', LIBS.'controllers'.DS.'templates'.DS.'scaffolds'.DS.'edit.thtml');
	}
	
	/**
	 * Enter description here...
	 *
	 */
	function scaffoldCreate($params){
		
		$this->controllerClass->flash('Scaffold::scaffoldCreate not implemented yet', '/'.$this->controllerClass->viewPath, 1);
	}
	
	/**
	 * Enter description here...
	 *
	 */
	function scaffoldUpdate($params=array()){
		
		$this->controllerClass->flash('Scaffold::scaffoldUpdate not implemented yet', '/'.$this->controllerClass->viewPath, 1);
	}
	
	/**
	 * Enter description here...
	 *
	 */
	function scaffoldDestroy($params=array()){
		
		$this->controllerClass->flash('Scaffold::scaffoldDestroy not implemented yet', '/'.$this->controllerClass->viewPath, 1);
	}
	
}

?>