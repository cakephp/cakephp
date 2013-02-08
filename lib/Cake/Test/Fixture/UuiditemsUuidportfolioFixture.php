<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class UuiditemsUuidportfolioFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'UuiditemsUuidportfolio'
 */
	public $name = 'UuiditemsUuidportfolio';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'length' => 36, 'key' => 'primary'),
		'uuiditem_id' => array('type' => 'string', 'length' => 36, 'null' => false),
		'uuidportfolio_id' => array('type' => 'string', 'length' => 36, 'null' => false)
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('id' => '4850fd8f-cc5c-449f-bf34-0c5240cf8569', 'uuiditem_id' => '481fc6d0-b920-43e0-a40d-6d1740cf8569', 'uuidportfolio_id' => '4806e091-6940-4d2b-b227-303740cf8569'),
		array('id' => '4850fee5-d24c-4ea0-9759-0c2e40cf8569', 'uuiditem_id' => '48298a29-81c0-4c26-a7fb-413140cf8569', 'uuidportfolio_id' => '480af662-eb8c-47d3-886b-230540cf8569'),
		array('id' => '4851af6e-fa18-403d-b57e-437d40cf8569', 'uuiditem_id' => '482b7756-8da0-419a-b21f-27da40cf8569', 'uuidportfolio_id' => '4806e091-6940-4d2b-b227-303740cf8569'),
		array('id' => '4851b94c-9790-42dc-b760-4f9240cf8569', 'uuiditem_id' => '482cfd4b-0e7c-4ea3-9582-4cec40cf8569', 'uuidportfolio_id' => '4806e091-6940-4d2b-b227-303740cf8569')
	);
}
