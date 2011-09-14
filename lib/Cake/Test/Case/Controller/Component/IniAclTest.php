<?php
App::uses('AclComponent', 'Controller/Component');
class_exists('AclComponent');

/**
 * Test case for the IniAcl implementation
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class IniAclTest extends CakeTestCase {

/**
 * testIniCheck method
 *
 * @return void
 */
	public function testCheck() {
		$iniFile = CAKE . 'Test' . DS . 'test_app' . DS . 'Config'. DS . 'acl.ini.php';

		$Ini = new IniAcl();
		$Ini->config = $Ini->readConfigFile($iniFile);

		$this->assertFalse($Ini->check('admin', 'ads'));
		$this->assertTrue($Ini->check('admin', 'posts'));

		$this->assertTrue($Ini->check('jenny', 'posts'));
		$this->assertTrue($Ini->check('jenny', 'ads'));

		$this->assertTrue($Ini->check('paul', 'posts'));
		$this->assertFalse($Ini->check('paul', 'ads'));

		$this->assertFalse($Ini->check('nobody', 'comments'));
	}

/**
 * check should accept a user array.
 *
 * @return void
 */
	public function testCheckArray() {
		$iniFile = CAKE . 'Test' . DS . 'test_app' . DS . 'Config'. DS . 'acl.ini.php';

		$Ini = new IniAcl();
		$Ini->config = $Ini->readConfigFile($iniFile);
		$Ini->userPath = 'User.username';

		$user = array(
			'User' => array('username' => 'admin')
		);
		$this->assertTrue($Ini->check($user, 'posts'));
	}
}

