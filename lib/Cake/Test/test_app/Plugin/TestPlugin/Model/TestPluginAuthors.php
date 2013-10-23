<?php
/**
 * Test App Comment Model
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Model
 * @since         CakePHP v 1.2.0.7726
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class TestPluginAuthors
 *
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Model
 */
class TestPluginAuthors extends TestPluginAppModel {

	public $useTable = 'authors';

	public $name = 'TestPluginAuthors';

	public $validate = array(
		'field' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'message' => 'I can haz plugin model validation message',
			),
		),
	);

}
