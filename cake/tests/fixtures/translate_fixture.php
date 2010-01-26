<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.fixtures
 * @since         CakePHP(tm) v 1.2.0.5669
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.fixtures
 */
class TranslateFixture extends CakeTestFixture {
/**
 * name property
 *
 * @var string 'Translate'
 * @access public
 */
	var $name = 'Translate';
/**
 * table property
 *
 * @var string 'i18n'
 * @access public
 */
	var $table = 'i18n';
/**
 * fields property
 *
 * @var array
 * @access public
 */
	var $fields = array(
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
 * @access public
 */
	var $records = array(
		array('id' => 1, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Title #1'),
		array('id' => 2, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Content #1'),
		array('id' => 3, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titel #1'),
		array('id' => 4, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Inhalt #1'),
		array('id' => 5, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'title', 'content' => 'Titulek #1'),
		array('id' => 6, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 1, 'field' => 'content', 'content' => 'Obsah #1'),
		array('id' => 7, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Title #2'),
		array('id' => 8, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'content', 'content' => 'Content #2'),
		array('id' => 9, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Titel #2'),
		array('id' => 10, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'content', 'content' => 'Inhalt #2'),
		array('id' => 11, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'title', 'content' => 'Titulek #2'),
		array('id' => 12, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 2, 'field' => 'content', 'content' => 'Obsah #2'),
		array('id' => 13, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Title #3'),
		array('id' => 14, 'locale' => 'eng', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'content', 'content' => 'Content #3'),
		array('id' => 15, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Titel #3'),
		array('id' => 16, 'locale' => 'deu', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'content', 'content' => 'Inhalt #3'),
		array('id' => 17, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'title', 'content' => 'Titulek #3'),
		array('id' => 18, 'locale' => 'cze', 'model' => 'TranslatedItem', 'foreign_key' => 3, 'field' => 'content', 'content' => 'Obsah #3')
	);
}
?>