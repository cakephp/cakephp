<?php
/**
 * Short description for campaign_fixture.php
 *
 * Long description for campaign_fixture.php
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * @link          http://www.cakephp.org
 * @package       cake.tests.fixtures
 * @since         1.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * CampaignFixture class
 *
 * @package       cake.tests.fixtures
 */
class CampaignFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Campaign'
 * @access public
 */
	public $name = 'Campaign';

/**
 * fields property
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => 255, 'null' => false),
	);

/**
 * records property
 *
 * @var array
 * @access public
 */
	public $records = array(
		array('name' => 'Hurtigruten'),
		array('name' => 'Colorline'),
		array('name' => 'Queen of Scandinavia')
	);
}
