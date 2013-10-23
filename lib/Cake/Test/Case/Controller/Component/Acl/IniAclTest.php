<?php
/**
 * IniAclTest file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Controller.Component.Acl
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('IniAcl', 'Controller/Component/Acl');

/**
 * Test case for the IniAcl implementation
 *
 * @package       Cake.Test.Case.Controller.Component.Acl
 */
class IniAclTest extends CakeTestCase {

/**
 * testIniCheck method
 *
 * @return void
 */
	public function testCheck() {
		$iniFile = CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'acl.ini.php';

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
		$iniFile = CAKE . 'Test' . DS . 'test_app' . DS . 'Config' . DS . 'acl.ini.php';

		$Ini = new IniAcl();
		$Ini->config = $Ini->readConfigFile($iniFile);
		$Ini->userPath = 'User.username';

		$user = array(
			'User' => array('username' => 'admin')
		);
		$this->assertTrue($Ini->check($user, 'posts'));
	}
}
