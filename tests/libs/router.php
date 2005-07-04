<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
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
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

/**
 * 
 */
uses ('router');
/**
 * Enter description here...
 *
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v .9
 *
 */
class RouterTest extends UnitTestCase
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $router;

/**
 * Enter description here...
 *
 * @return RouterTest
 */
	function RouterTest()
	{
		$this->UnitTestCase('Router test');
	}

/**
 * Enter description here...
 *
 */
	function setUp()
	{
		$this->router = new Router();
	}

/**
 * Enter description here...
 *
 */
	function tearDown()
	{
		unset($this->router);
	}


/**
 * Enter description here...
 *
 */
	function _testConnect()
	{
		$tests = array(
		'/' => array('controller'=>'Foo', 'action'=>'bar'),
		'/foo/baz' => array('controller'=>'Foo', 'action'=>'baz'),
		'/foo/*' => array('controller'=>'Foo', 'action'=>'dodo'),
		'/foobar' => array('controller'=>'Foobar', 'action'=>'bar'),
		);

		foreach ($tests as $route=>$data)
		$this->router->connect ($route, $data);
	}


/**
 * Enter description here...
 *
 */
	function testParse ()
	{

		$this->_testConnect();

		$tests = array(
		'' => array('controller'=>'Foo', 'action'=>'bar'),
		'/' => array('controller'=>'Foo', 'action'=>'bar'),
		'/foo/baz/' => array('controller'=>'Foo', 'action'=>'baz'),
		'/foo/foo+bar' => array('pass'=>array('foo+bar'), 'controller'=>'Foo', 'action'=>'dodo'),
		'/foobar/' => array('controller'=>'Foobar', 'action'=>'bar'),
		'/foo/bar/baz' => array('controller'=>'Foo', 'action'=>'dodo', 'pass'=>array('bar', 'baz')),
		'/one/two/three/' => array('controller'=>'one', 'action'=>'two', 'pass'=>array('three')),
		'/ruburb' => array('controller'=>'ruburb','action'=>null),
		'???' => array()
		);

		foreach ($tests as $test=>$expected)
		{
			$tested = $this->router->parse($test);
			$this->assertEqual($tested, $expected);
		}
	}
}

?>