<?php
/**
 * Short description for campaign_fixture.php
 *
 * Long description for campaign_fixture.php
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://www.cakephp.org
 * @package       Cake.Test.Fixture
 * @since         1.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * CampaignFixture class
 *
 * @package       Cake.Test.Fixture
 */
class CampaignFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => 255, 'null' => false),
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('name' => 'Hurtigruten'),
		array('name' => 'Colorline'),
		array('name' => 'Queen of Scandinavia')
	);
}
