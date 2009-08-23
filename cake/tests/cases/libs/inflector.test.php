<?php
/* SVN FILE: $Id$ */
/**
 * InflectorTest file
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Inflector');
/**
 * InflectorTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class InflectorTest extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
	}
/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
	}
/**
 * testInstantiation method
 *
 * @access public
 * @return void
 */
	function testInstantiation() {
		$this->skipUnless(strpos(Debugger::trace(), 'GroupTest') === false, '%s Cannot be run from within a group test');

		$Instance = Inflector::getInstance();
		$this->assertEqual(new Inflector(), $Instance);
	}
/**
 * testInflectingSingulars method
 *
 * @access public
 * @return void
 */
	function testInflectingSingulars() {
		$this->assertEqual(Inflector::singularize('categorias'), 'categoria');
		$this->assertEqual(Inflector::singularize('menus'), 'menu');
		$this->assertEqual(Inflector::singularize('news'), 'news');
		$this->assertEqual(Inflector::singularize('food_menus'), 'food_menu');
		$this->assertEqual(Inflector::singularize('Menus'), 'Menu');
		$this->assertEqual(Inflector::singularize('FoodMenus'), 'FoodMenu');
		$this->assertEqual(Inflector::singularize('houses'), 'house');
		$this->assertEqual(Inflector::singularize('powerhouses'), 'powerhouse');
		$this->assertEqual(Inflector::singularize('quizzes'), 'quiz');
		$this->assertEqual(Inflector::singularize('Buses'), 'Bus');
		$this->assertEqual(Inflector::singularize('buses'), 'bus');
		$this->assertEqual(Inflector::singularize('matrix_rows'), 'matrix_row');
		$this->assertEqual(Inflector::singularize('matrices'), 'matrix');
		$this->assertEqual(Inflector::singularize('vertices'), 'vertex');
		$this->assertEqual(Inflector::singularize('indices'), 'index');
		$this->assertEqual(Inflector::singularize('Aliases'), 'Alias');
		$this->assertEqual(Inflector::singularize('Alias'), 'Alias');
		$this->assertEqual(Inflector::singularize('Media'), 'Media');
		$this->assertEqual(Inflector::singularize('alumni'), 'alumnus');
		$this->assertEqual(Inflector::singularize('bacilli'), 'bacillus');
		$this->assertEqual(Inflector::singularize('cacti'), 'cactus');
		$this->assertEqual(Inflector::singularize('foci'), 'focus');
		$this->assertEqual(Inflector::singularize('fungi'), 'fungus');
		$this->assertEqual(Inflector::singularize('nuclei'), 'nucleus');
		$this->assertEqual(Inflector::singularize('octopuses'), 'octopus');
		$this->assertEqual(Inflector::singularize('radii'), 'radius');
		$this->assertEqual(Inflector::singularize('stimuli'), 'stimulus');
		$this->assertEqual(Inflector::singularize('syllabi'), 'syllabus');
		$this->assertEqual(Inflector::singularize('termini'), 'terminus');
		$this->assertEqual(Inflector::singularize('viri'), 'virus');
		$this->assertEqual(Inflector::singularize('people'), 'person');
		$this->assertEqual(Inflector::singularize('gloves'), 'glove');
		$this->assertEqual(Inflector::singularize('doves'), 'dove');
		$this->assertEqual(Inflector::singularize('lives'), 'life');
		$this->assertEqual(Inflector::singularize('knives'), 'knife');
		$this->assertEqual(Inflector::singularize('wolves'), 'wolf');
		$this->assertEqual(Inflector::singularize('shelves'), 'shelf');
		$this->assertEqual(Inflector::singularize('taxis'), 'taxi');
		$this->assertEqual(Inflector::singularize('taxes'), 'tax');
		$this->assertEqual(Inflector::singularize('faxes'), 'fax');
		$this->assertEqual(Inflector::singularize('waxes'), 'wax');
		$this->assertEqual(Inflector::singularize('waves'), 'wave');
		$this->assertEqual(Inflector::singularize(''), '');
	}
/**
 * testInflectingPlurals method
 *
 * @access public
 * @return void
 */
	function testInflectingPlurals() {
		$this->assertEqual(Inflector::pluralize('categoria'), 'categorias');
		$this->assertEqual(Inflector::pluralize('house'), 'houses');
		$this->assertEqual(Inflector::pluralize('powerhouse'), 'powerhouses');
		$this->assertEqual(Inflector::pluralize('Bus'), 'Buses');
		$this->assertEqual(Inflector::pluralize('bus'), 'buses');
		$this->assertEqual(Inflector::pluralize('menu'), 'menus');
		$this->assertEqual(Inflector::pluralize('news'), 'news');
		$this->assertEqual(Inflector::pluralize('food_menu'), 'food_menus');
		$this->assertEqual(Inflector::pluralize('Menu'), 'Menus');
		$this->assertEqual(Inflector::pluralize('FoodMenu'), 'FoodMenus');
		$this->assertEqual(Inflector::pluralize('quiz'), 'quizzes');
		$this->assertEqual(Inflector::pluralize('matrix_row'), 'matrix_rows');
		$this->assertEqual(Inflector::pluralize('matrix'), 'matrices');
		$this->assertEqual(Inflector::pluralize('vertex'), 'vertices');
		$this->assertEqual(Inflector::pluralize('index'), 'indices');
		$this->assertEqual(Inflector::pluralize('Alias'), 'Aliases');
		$this->assertEqual(Inflector::pluralize('Aliases'), 'Aliases');
		$this->assertEqual(Inflector::pluralize('Media'), 'Media');
		$this->assertEqual(Inflector::pluralize('alumnus'), 'alumni');
		$this->assertEqual(Inflector::pluralize('bacillus'), 'bacilli');
		$this->assertEqual(Inflector::pluralize('cactus'), 'cacti');
		$this->assertEqual(Inflector::pluralize('focus'), 'foci');
		$this->assertEqual(Inflector::pluralize('fungus'), 'fungi');
		$this->assertEqual(Inflector::pluralize('nucleus'), 'nuclei');
		$this->assertEqual(Inflector::pluralize('octopus'), 'octopuses');
		$this->assertEqual(Inflector::pluralize('radius'), 'radii');
		$this->assertEqual(Inflector::pluralize('stimulus'), 'stimuli');
		$this->assertEqual(Inflector::pluralize('syllabus'), 'syllabi');
		$this->assertEqual(Inflector::pluralize('terminus'), 'termini');
		$this->assertEqual(Inflector::pluralize('virus'), 'viri');
		$this->assertEqual(Inflector::pluralize('person'), 'people');
		$this->assertEqual(Inflector::pluralize('people'), 'people');
		$this->assertEqual(Inflector::pluralize('glove'), 'gloves');
		$this->assertEqual(Inflector::pluralize('crisis'), 'crises');
		$this->assertEqual(Inflector::pluralize('wave'), 'waves');
		$this->assertEqual(Inflector::pluralize(''), '');
	}
/**
 * testInflectorSlug method
 *
 * @access public
 * @return void
 */
	function testInflectorSlug() {
		$result = Inflector::slug('Foo Bar: Not just for breakfast any-more');
		$expected = 'Foo_Bar_Not_just_for_breakfast_any_more';
		$this->assertEqual($result, $expected);

		$result = Inflector::slug('this/is/a/path');
		$expected = 'this_is_a_path';
		$this->assertEqual($result, $expected);

		$result = Inflector::slug('Foo Bar: Not just for breakfast any-more', "-");
		$expected = 'Foo-Bar-Not-just-for-breakfast-any-more';
		$this->assertEqual($result, $expected);

		$result = Inflector::slug('Foo Bar: Not just for breakfast any-more', "+");
		$expected = 'Foo+Bar+Not+just+for+breakfast+any+more';
		$this->assertEqual($result, $expected);

		$result = Inflector::slug('Äpfel Über Öl grün ärgert groß öko', '-');
		$expected = 'Aepfel-Ueber-Oel-gruen-aergert-gross-oeko';
		$this->assertEqual($result, $expected);

		$result = Inflector::slug('The truth - and- more- news', '-');
		$expected = 'The-truth-and-more-news';
		$this->assertEqual($result, $expected);

		$result = Inflector::slug('The truth: and more news', '-');
		$expected = 'The-truth-and-more-news';
		$this->assertEqual($result, $expected);

		$result = Inflector::slug('La langue française est un attribut de souveraineté en France', '-');
		$expected = 'La-langue-francaise-est-un-attribut-de-souverainete-en-France';
		$this->assertEqual($result, $expected);

		$result = Inflector::slug('!@$#exciting stuff! - what !@-# was that?', '-');
		$expected = 'exciting-stuff-what-was-that';
		$this->assertEqual($result, $expected);

		$result = Inflector::slug('20% of profits went to me!', '-');
		$expected = '20-of-profits-went-to-me';
		$this->assertEqual($result, $expected);

		$result = Inflector::slug('#this melts your face1#2#3', '-');
		$expected = 'this-melts-your-face1-2-3';
		$this->assertEqual($result, $expected);
	}
/**
 * testVariableNaming method
 *
 * @access public
 * @return void
 */
	function testVariableNaming() {
		$this->assertEqual(Inflector::variable('test_field'), 'testField');
		$this->assertEqual(Inflector::variable('test_fieLd'), 'testFieLd');
		$this->assertEqual(Inflector::variable('test field'), 'testField');
		$this->assertEqual(Inflector::variable('Test_field'), 'testField');
	}
/**
 * testClassNaming method
 *
 * @access public
 * @return void
 */
	function testClassNaming() {
		$this->assertEqual(Inflector::classify('artists_genres'), 'ArtistsGenre');
		$this->assertEqual(Inflector::classify('file_systems'), 'FileSystem');
		$this->assertEqual(Inflector::classify('news'), 'News');
	}
/**
 * testTableNaming method
 *
 * @access public
 * @return void
 */
	function testTableNaming() {
		$this->assertEqual(Inflector::tableize('ArtistsGenre'), 'artists_genres');
		$this->assertEqual(Inflector::tableize('FileSystem'), 'file_systems');
		$this->assertEqual(Inflector::tableize('News'), 'news');
	}
/**
 * testHumanization method
 *
 * @access public
 * @return void
 */
	function testHumanization() {
		$this->assertEqual(Inflector::humanize('posts'), 'Posts');
		$this->assertEqual(Inflector::humanize('posts_tags'), 'Posts Tags');
		$this->assertEqual(Inflector::humanize('file_systems'), 'File Systems');
	}
}
?>