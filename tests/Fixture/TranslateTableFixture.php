<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class TranslateTableFixture
 *
 */
class TranslateTableFixture extends TestFixture {

/**
 * table property
 *
 * @var string
 */
	public $table = 'another_i18n';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => ['type' => 'integer'],
		'locale' => ['type' => 'string', 'length' => 6, 'null' => false],
		'model' => ['type' => 'string', 'null' => false],
		'foreign_key' => ['type' => 'integer', 'null' => false],
		'field' => ['type' => 'string', 'null' => false],
		'content' => ['type' => 'text'],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('locale' => 'eng', 'model' => 'TranslatedItemWithTable', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Another Title #1'),
		array('locale' => 'eng', 'model' => 'TranslatedItemWithTable', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Another Content #1')
	);
}
