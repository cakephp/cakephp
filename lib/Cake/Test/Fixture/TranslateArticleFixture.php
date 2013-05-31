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
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class TranslateArticleFixture
 *
 * @package       Cake.Test.Fixture
 */
class TranslateArticleFixture extends CakeTestFixture {

/**
 * name property
 *
 * @var string 'Translate'
 */
	public $name = 'TranslateArticle';

/**
 * table property
 *
 * @var string 'i18n'
 */
	public $table = 'article_i18n';

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
		array('locale' => 'eng', 'model' => 'TranslatedArticle', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Title (eng) #1'),
		array('locale' => 'eng', 'model' => 'TranslatedArticle', 'foreign_key' => 1, 'field' => 'body', 'content' => 'Body (eng) #1'),
		array('locale' => 'deu', 'model' => 'TranslatedArticle', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Title (deu) #1'),
		array('locale' => 'deu', 'model' => 'TranslatedArticle', 'foreign_key' => 1, 'field' => 'body', 'content' => 'Body (deu) #1'),
		array('locale' => 'cze', 'model' => 'TranslatedArticle', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Title (cze) #1'),
		array('locale' => 'cze', 'model' => 'TranslatedArticle', 'foreign_key' => 1, 'field' => 'body', 'content' => 'Body (cze) #1'),
		array('locale' => 'eng', 'model' => 'TranslatedArticle', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Title (eng) #2'),
		array('locale' => 'eng', 'model' => 'TranslatedArticle', 'foreign_key' => 2, 'field' => 'body', 'content' => 'Body (eng) #2'),
		array('locale' => 'deu', 'model' => 'TranslatedArticle', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Title (deu) #2'),
		array('locale' => 'deu', 'model' => 'TranslatedArticle', 'foreign_key' => 2, 'field' => 'body', 'content' => 'Body (deu) #2'),
		array('locale' => 'cze', 'model' => 'TranslatedArticle', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Title (cze) #2'),
		array('locale' => 'cze', 'model' => 'TranslatedArticle', 'foreign_key' => 2, 'field' => 'body', 'content' => 'Body (cze) #2'),
		array('locale' => 'eng', 'model' => 'TranslatedArticle', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Title (eng) #3'),
		array('locale' => 'eng', 'model' => 'TranslatedArticle', 'foreign_key' => 3, 'field' => 'body', 'content' => 'Body (eng) #3'),
		array('locale' => 'deu', 'model' => 'TranslatedArticle', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Title (deu) #3'),
		array('locale' => 'deu', 'model' => 'TranslatedArticle', 'foreign_key' => 3, 'field' => 'body', 'content' => 'Body (deu) #3'),
		array('locale' => 'cze', 'model' => 'TranslatedArticle', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Title (cze) #3'),
		array('locale' => 'cze', 'model' => 'TranslatedArticle', 'foreign_key' => 3, 'field' => 'body', 'content' => 'Body (cze) #3')
	);
}
