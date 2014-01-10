<?php
/**
 * Short description for file.
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
 * @since         CakePHP(tm) v 1.2.0.6879//Correct version number as needed**
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for file.
 *
 * @since         CakePHP(tm) v 1.2.0.6879//Correct version number as needed**
 */
class DependencyFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'child_id' => 'integer',
		'parent_id' => 'integer'
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('child_id' => 1, 'parent_id' => 2),
	);
}
