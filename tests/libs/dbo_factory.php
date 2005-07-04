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
uses('dbo_factory');
/**
 * Enter description here...
 *
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v .9
 *
 */
class DboFactoryTest extends UnitTestCase
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $dboFactory;


/**
 * Enter description here...
 *
 * @return DboFactoryTest
 */
	function DboFactoryTest()
	{
		$this->UnitTestCase('DBO Factory test');
	}

	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here

/**
 * Enter description here...
 *
 */
	//function setUp()
	//{
	//	$this->dboFactory = new DboFactory();
	//}


/**
 * Enter description here...
 *
 */
	//function tearDown()
	//{
	//	unset($this->dboFactory);
	//}

/**
 * Enter description here...
 *
 */
	//function testMake()
	//{
	//	if(class_exists('DATABASE_CONFIG'))
	//	{

	//		$output = $this->dboFactory->make('test');
	//		$this->assertTrue(is_object($output), 'We create dbo factory object');
   //
	//		$config = DATABASE_CONFIG::test();
	//		if(preg_match('#^(adodb)_.*$#i', $config['driver'], $res))
	//		{
	//			$desiredDriverName = $res[1];
	//		}
	//		else
	//		{
	//			$desiredDriverName = $config['driver'];
	//		}

	//		$desiredClassName = 'dbo_'.strtolower($desiredDriverName);
	//		$outputClassName  = is_object($output)?  strtolower(get_class($output)): false;

	//		$this->assertEqual($outputClassName, $desiredClassName, "Class name should be $desiredClassName - is $outputClassName");

	//		$this->assertTrue($output->connected, 'We are connected');
	//	}
	//}

	// this test expect an E_USER_ERROR to occur during it's run
	// I've disabled it until I find a way to assert it happen
	//
	
/**
 * Enter description here...
 *
 */
	//	function testBadConfig() {
	//		$output = $this->dboFactory->make(null);
	//		$this->assertTrue($output === false);
	//}
}

?>