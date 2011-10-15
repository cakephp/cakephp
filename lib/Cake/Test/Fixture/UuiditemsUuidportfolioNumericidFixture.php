<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class UuiditemsUuidportfolioNumericidFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'UuiditemsUuidportfolioNumericid'
 */
	public $name = 'UuiditemsUuidportfolioNumericid';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'length' => 10, 'key' => 'primary'),
		'uuiditem_id' => array('type' => 'string', 'length' => 36, 'null' => false),
		'uuidportfolio_id' => array('type' => 'string', 'length' => 36, 'null' => false)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('uuiditem_id' => '481fc6d0-b920-43e0-a40d-6d1740cf8569', 'uuidportfolio_id' => '4806e091-6940-4d2b-b227-303740cf8569'),
		array('uuiditem_id' => '48298a29-81c0-4c26-a7fb-413140cf8569', 'uuidportfolio_id' => '480af662-eb8c-47d3-886b-230540cf8569'),
		array('uuiditem_id' => '482b7756-8da0-419a-b21f-27da40cf8569', 'uuidportfolio_id' => '4806e091-6940-4d2b-b227-303740cf8569'),
		array('uuiditem_id' => '482cfd4b-0e7c-4ea3-9582-4cec40cf8569', 'uuidportfolio_id' => '4806e091-6940-4d2b-b227-303740cf8569')
	);
}
