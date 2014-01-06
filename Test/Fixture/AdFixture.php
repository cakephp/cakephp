<?php
/**
 * Short description for ad_fixture.php
 *
 * Long description for ad_fixture.php
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://www.cakephp.org
 * @since         1.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AdFixture class
 *
 */
class AdFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'campaign_id' => ['type' => 'integer'],
		'parent_id' => ['type' => 'integer'],
		'lft' => ['type' => 'integer'],
		'rght' => ['type' => 'integer'],
		'name' => ['type' => 'string', 'length' => 255, 'null' => false],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('parent_id' => null, 'lft' => 1, 'rght' => 2, 'campaign_id' => 1, 'name' => 'Nordover'),
		array('parent_id' => null, 'lft' => 3, 'rght' => 4, 'campaign_id' => 1, 'name' => 'Statbergen'),
		array('parent_id' => null, 'lft' => 5, 'rght' => 6, 'campaign_id' => 1, 'name' => 'Feroy'),
		array('parent_id' => null, 'lft' => 7, 'rght' => 12, 'campaign_id' => 2, 'name' => 'Newcastle'),
		array('parent_id' => null, 'lft' => 8, 'rght' => 9, 'campaign_id' => 2, 'name' => 'Dublin'),
		array('parent_id' => null, 'lft' => 10, 'rght' => 11, 'campaign_id' => 2, 'name' => 'Alborg'),
		array('parent_id' => null, 'lft' => 13, 'rght' => 14, 'campaign_id' => 3, 'name' => 'New York')
	);
}
