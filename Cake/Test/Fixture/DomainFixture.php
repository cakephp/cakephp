<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 * @package       Cake.Test.Fixture
 */
class DomainFixture extends TestFixture {

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'domain' => ['type' => 'string', 'null' => false],
		'created' => 'datetime',
		'updated' => 'datetime',
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
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
