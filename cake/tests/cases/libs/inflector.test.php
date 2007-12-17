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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('inflector');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class InflectorTest extends UnitTestCase {

	var $Inflector = null;

	function setUp() {
		$this->Inflector = new Inflector();
	}

	function testInflectingSingulars() {
		$result = $this->Inflector->singularize('categorias');
		$expected = 'categoria';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('menus');
		$expected = 'menu';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('food_menus');
		$expected = 'food_menu';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('Menus');
		$expected = 'Menu';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('FoodMenus');
		$expected = 'FoodMenu';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('houses');
		$expected = 'house';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('powerhouses');
		$expected = 'powerhouse';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('quizzes');
		$expected = 'quiz';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('Buses');
		$expected = 'Bus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('buses');
		$expected = 'bus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('matrix_rows');
		$expected = 'matrix_row';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('matrices');
		$expected = 'matrix';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('vertices');
		$expected = 'vertex';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('indices');
		$expected = 'index';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('Aliases');
		$expected = 'Alias';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('Alias');
		$expected = 'Alias';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('Media');
		$expected = 'Media';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('alumni');
		$expected = 'alumnus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('bacilli');
		$expected = 'bacillus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('cacti');
		$expected = 'cactus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('foci');
		$expected = 'focus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('fungi');
		$expected = 'fungus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('nuclei');
		$expected = 'nucleus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('octopuses');
		$expected = 'octopus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('radii');
		$expected = 'radius';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('stimuli');
		$expected = 'stimulus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('syllabi');
		$expected = 'syllabus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('termini');
		$expected = 'terminus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->singularize('viri');
		$expected = 'virus';
		$this->assertEqual($result, $expected);
	}

	function testInflectingPlurals() {
		$result = $this->Inflector->pluralize('categoria');
		$expected = 'categorias';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('house');
		$expected = 'houses';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('powerhouse');
		$expected = 'powerhouses';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('Bus');
		$expected = 'Buses';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('bus');
		$expected = 'buses';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('menu');
		$expected = 'menus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('food_menu');
		$expected = 'food_menus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('Menu');
		$expected = 'Menus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('FoodMenu');
		$expected = 'FoodMenus';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('quiz');
		$expected = 'quizzes';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('matrix_row');
		$expected = 'matrix_rows';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('matrix');
		$expected = 'matrices';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('vertex');
		$expected = 'vertices';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('index');
		$expected = 'indices';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('Alias');
		$expected = 'Aliases';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('Aliases');
		$expected = 'Aliases';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('Media');
		$expected = 'Media';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('alumnus');
		$expected = 'alumni';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('bacillus');
		$expected = 'bacilli';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('cactus');
		$expected = 'cacti';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('focus');
		$expected = 'foci';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('fungus');
		$expected = 'fungi';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('nucleus');
		$expected = 'nuclei';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('octopus');
		$expected = 'octopuses';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('radius');
		$expected = 'radii';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('stimulus');
		$expected = 'stimuli';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('syllabus');
		$expected = 'syllabi';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('terminus');
		$expected = 'termini';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->pluralize('virus');
		$expected = 'viri';
		$this->assertEqual($result, $expected);
	}

	function testInflectorSlug() {
		$result = $this->Inflector->slug('Foo Bar: Not just for breakfast any-more');
		$expected = 'Foo_Bar_Not_just_for_breakfast_any_more';
		$this->assertEqual($result, $expected);
		
		$result = $this->Inflector->slug('this/is/a/path');
		$expected = 'this_is_a_path';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->slug('Foo Bar: Not just for breakfast any-more', "-");
		$expected = 'Foo-Bar-Not-just-for-breakfast-any-more';
		$this->assertEqual($result, $expected);

		$result = $this->Inflector->slug('Foo Bar: Not just for breakfast any-more', "+");
		$expected = 'Foo+Bar+Not+just+for+breakfast+any+more';
		$this->assertEqual($result, $expected);
	}

	function tearDown() {
		unset($this->Inflector);
	}
}
?>