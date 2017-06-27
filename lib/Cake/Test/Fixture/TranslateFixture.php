<?php
/**
 * Short description for file.
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Fixture
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TranslateFixture
 *
 * @package       Cake.Test.Fixture
 */
class TranslateFixture extends CakeTestFixture {

/**
 * table property
 *
 * @var string
 */
	public $table = 'i18n';

/**
 * fields property
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'locale' => array('type' => 'string', 'length' => 6, 'null' => false),
		'model' => array('type' => 'string', 'null' => false),
		'foreign_key' => array('type' => 'integer', 'null' => false),
		'field' => array('type' => 'string', 'null' => false),
		'content' => array('type' => 'text')
	);

/**
 * records property
 *
 * @var array
 */
	public $records = array(
		array('locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Title #1'),
		array('locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Content #1'),
		array('locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titel #1'),
		array('locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Inhalt #1'),
		array('locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titulek #1'),
		array('locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Obsah #1'),
		array('locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Title #2'),
		array('locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'content', 'content' => 'Content #2'),
		array('locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Titel #2'),
		array('locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'content', 'content' => 'Inhalt #2'),
		array('locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Titulek #2'),
		array('locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'content', 'content' => 'Obsah #2'),
		array('locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Title #3'),
		array('locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'content', 'content' => 'Content #3'),
		array('locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Titel #3'),
		array('locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'content', 'content' => 'Inhalt #3'),
		array('locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Titulek #3'),
		array('locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'content', 'content' => 'Obsah #3')
	);
}
