<?php
/**
 * Short description for file.
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class DomainFixture extends CakeTestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'domain' => array('type' => 'string', 'null' => false),
		'created' => 'datetime',
		'updated' => 'datetime'
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('domain' => 'cakephp.org', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
		array('domain' => 'book.cakephp.org', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
		array('domain' => 'api.cakephp.org', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'),
		array('domain' => 'mark-story.com', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
		array('domain' => 'tinadurocher.com', 'created' => '2007-03-17 01:18:23', 'updated' => '2007-03-17 01:20:31'),
		array('domain' => 'chavik.com', 'created' => '2001-02-03 00:01:02', 'updated' => '2007-03-17 01:22:31'),
		array('domain' => 'xintesa.com', 'created' => '2001-02-03 00:01:02', 'updated' => '2007-03-17 01:22:31'),
	);
}
