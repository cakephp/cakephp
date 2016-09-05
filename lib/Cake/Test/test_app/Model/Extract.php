<?php
/**
 * Test App Extract Model
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.TestApp.Model
 * @since         CakePHP v 2.4
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Extract
 *
 * For testing Console i18n validation message extraction with quotes
 *
 * @package       Cake.Test.TestApp.Model
 */
class Extract extends AppModel {

	public $useTable = false;

	public $validate = array(
		'title' => array(
			'custom' => array(
				'rule' => array('custom', '.*'),
				'allowEmpty' => true,
				'required' => false,
				'message' => 'double "quoted" validation'
			),
			'between' => array(
				'rule' => array('lengthBetween', 5, 15),
				'message' => "single 'quoted' validation"
			)
		),
	);

}
