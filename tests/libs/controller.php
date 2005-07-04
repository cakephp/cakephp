<?php
//////////////////////////////////////////////////////////////////////////
// + $Id:$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * 
 * 
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v 0.2.9
 * @version $Revision:$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date:$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

/**
 * Basic defines
 */
uses('controller');

/**
 * Enter description here...
 *
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v .9
 *
 */
class ControllerTest extends UnitTestCase
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $controller;


/**
 * constructor of the test suite.
 *
 * @return ControllerTest
 */
	function ControllerTest()
	{
		$this->UnitTestCase('Controller test');
	}


/**
 * called before the test functions will be executed
 * this function is defined in PHPUnit_TestCase and overwritten
 * here
 *
 */
	function setUp()
	{
		$this->controller = new Controller();
		$this->controller->base = '/ease';

		$data = array('foo'=>'foo_value', 'foobar'=>'foobar_value', 'tofu'=>'1');
		$params = array('controller'=>'Test', 'action'=>'test_action', 'data'=>$data);
		$here = '/cake/test';
		$this->controller->params = $params;
		$this->controller->data = $data;
		$this->controller->here = $here;

		$this->controller->action = $this->controller->params['action'];
		$this->controller->passed_args = null;
	}

/**
 * called after the test functions are executed
 * this function is defined in PHPUnit_TestCase and overwritten
 * here
 */
	function tearDown()
	{
		unset($this->controller);
	}
}

?>