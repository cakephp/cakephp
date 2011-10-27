<?php
/**
 * InflectorTest
 *
 * InflectorTest is used to test cases on the Inflector class
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing
 * @package       Cake.Test.Case.Utility
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       Open Group Test Suite License (http://www.opensource.org/licenses/opengroup.php)
 */

/**
 * Included libraries.
 *
 */
App::uses('Inflector', 'Utility');

/**
 * Short description for class.
 *
 * @package		  cake.tests
 * @package       Cake.Test.Case.Utility
 */
class InflectorTest extends CakeTestCase {

/**
 * teardown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Inflector::reset();
	}

/**
 * testInflectingSingulars method
 *
 * @return void
 */
	public function testInflectingSingulars() {
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
		$this->assertEqual(Inflector::singularize('NodeMedia'), 'NodeMedia');
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
		$this->assertEqual(Inflector::singularize('slaves'), 'slave');
		$this->assertEqual(Inflector::singularize('shelves'), 'shelf');
		$this->assertEqual(Inflector::singularize('taxis'), 'taxi');
		$this->assertEqual(Inflector::singularize('taxes'), 'tax');
		$this->assertEqual(Inflector::singularize('Taxes'), 'Tax');
		$this->assertEqual(Inflector::singularize('AwesomeTaxes'), 'AwesomeTax');
		$this->assertEqual(Inflector::singularize('faxes'), 'fax');
		$this->assertEqual(Inflector::singularize('waxes'), 'wax');
		$this->assertEqual(Inflector::singularize('niches'), 'niche');
		$this->assertEqual(Inflector::singularize('waves'), 'wave');
		$this->assertEqual(Inflector::singularize('bureaus'), 'bureau');
		$this->assertEqual(Inflector::singularize('genetic_analyses'), 'genetic_analysis');
		$this->assertEqual(Inflector::singularize('doctor_diagnoses'), 'doctor_diagnosis');
		$this->assertEqual(Inflector::singularize('parantheses'), 'paranthesis');
		$this->assertEqual(Inflector::singularize('Causes'), 'Cause');
		$this->assertEqual(Inflector::singularize('colossuses'), 'colossus');
		$this->assertEqual(Inflector::singularize('diagnoses'), 'diagnosis');
		$this->assertEqual(Inflector::singularize('bases'), 'basis');
		$this->assertEqual(Inflector::singularize('analyses'), 'analysis');
		$this->assertEqual(Inflector::singularize('curves'), 'curve');
		$this->assertEqual(Inflector::singularize('cafes'), 'cafe');
		$this->assertEqual(Inflector::singularize('roofs'), 'roof');

		$this->assertEqual(Inflector::singularize(''), '');
	}

/**
 * testInflectingPlurals method
 *
 * @return void
 */
	public function testInflectingPlurals() {
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
		$this->assertEqual(Inflector::pluralize('NodeMedia'), 'NodeMedia');
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
		$this->assertEqual(Inflector::pluralize('tax'), 'taxes');
		$this->assertEqual(Inflector::pluralize('wave'), 'waves');
		$this->assertEqual(Inflector::pluralize('bureau'), 'bureaus');
		$this->assertEqual(Inflector::pluralize('cafe'), 'cafes');
		$this->assertEqual(Inflector::pluralize('roof'), 'roofs');
		$this->assertEqual(Inflector::pluralize(''), '');
	}

/**
 * testInflectorSlug method
 *
 * @return void
 */
	public function testInflectorSlug() {
		$result = Inflector::slug('Foo Bar: Not just for breakfast any-more');
		$expected = 'Foo_Bar_Not_just_for_breakfast_any_more';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('this/is/a/path');
		$expected = 'this_is_a_path';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('Foo Bar: Not just for breakfast any-more', "-");
		$expected = 'Foo-Bar-Not-just-for-breakfast-any-more';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('Foo Bar: Not just for breakfast any-more', "+");
		$expected = 'Foo+Bar+Not+just+for+breakfast+any+more';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('Äpfel Über Öl grün ärgert groß öko', '-');
		$expected = 'Aepfel-Ueber-Oel-gruen-aergert-gross-oeko';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('The truth - and- more- news', '-');
		$expected = 'The-truth-and-more-news';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('The truth: and more news', '-');
		$expected = 'The-truth-and-more-news';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('La langue française est un attribut de souveraineté en France', '-');
		$expected = 'La-langue-francaise-est-un-attribut-de-souverainete-en-France';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('!@$#exciting stuff! - what !@-# was that?', '-');
		$expected = 'exciting-stuff-what-was-that';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('20% of profits went to me!', '-');
		$expected = '20-of-profits-went-to-me';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('#this melts your face1#2#3', '-');
		$expected = 'this-melts-your-face1-2-3';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('controller/action/りんご/1');
		$expected = 'controller_action_りんご_1';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('の話が出たので大丈夫かなあと');
		$expected = 'の話が出たので大丈夫かなあと';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('posts/view/한국어/page:1/sort:asc');
		$expected = 'posts_view_한국어_page_1_sort_asc';
		$this->assertEqual($expected, $result);
	}

/**
 * testInflectorSlugWithMap method
 *
 * @return void
 */
	public function testInflectorSlugWithMap() {
	    Inflector::rules('transliteration', array('/r/' => '1'));
		$result = Inflector::slug('replace every r');
		$expected = '1eplace_eve1y_1';
		$this->assertEqual($expected, $result);

		$result = Inflector::slug('replace every r', '_');
		$expected = '1eplace_eve1y_1';
		$this->assertEqual($expected, $result);
	}

/**
 * testInflectorSlugWithMapOverridingDefault method
 *
 * @return void
 */
	public function testInflectorSlugWithMapOverridingDefault() {
	    Inflector::rules('transliteration', array('/å/' => 'aa', '/ø/' => 'oe'));
		$result = Inflector::slug('Testing æ ø å', '-');
		$expected = 'Testing-ae-oe-aa';
		$this->assertEqual($expected, $result);
	}

/**
 * testInflectorUnderscore method
 *
 * @return void
 */
	public function testInflectorUnderscore() {
		$this->assertIdentical(Inflector::underscore('TestThing'), 'test_thing');
		$this->assertIdentical(Inflector::underscore('testThing'), 'test_thing');
		$this->assertIdentical(Inflector::underscore('TestThingExtra'), 'test_thing_extra');
		$this->assertIdentical(Inflector::underscore('testThingExtra'), 'test_thing_extra');

		// Identical checks test the cache code path.
		$this->assertIdentical(Inflector::underscore('TestThing'), 'test_thing');
		$this->assertIdentical(Inflector::underscore('testThing'), 'test_thing');
		$this->assertIdentical(Inflector::underscore('TestThingExtra'), 'test_thing_extra');
		$this->assertIdentical(Inflector::underscore('testThingExtra'), 'test_thing_extra');

		// Test stupid values
		$this->assertIdentical(Inflector::underscore(''), '');
		$this->assertIdentical(Inflector::underscore(0), '0');
		$this->assertIdentical(Inflector::underscore(false), '');
	}

/**
 * testVariableNaming method
 *
 * @return void
 */
	public function testVariableNaming() {
		$this->assertEqual(Inflector::variable('test_field'), 'testField');
		$this->assertEqual(Inflector::variable('test_fieLd'), 'testFieLd');
		$this->assertEqual(Inflector::variable('test field'), 'testField');
		$this->assertEqual(Inflector::variable('Test_field'), 'testField');
	}

/**
 * testClassNaming method
 *
 * @return void
 */
	public function testClassNaming() {
		$this->assertEqual(Inflector::classify('artists_genres'), 'ArtistsGenre');
		$this->assertEqual(Inflector::classify('file_systems'), 'FileSystem');
		$this->assertEqual(Inflector::classify('news'), 'News');
		$this->assertEqual(Inflector::classify('bureaus'), 'Bureau');
	}

/**
 * testTableNaming method
 *
 * @return void
 */
	public function testTableNaming() {
		$this->assertEqual(Inflector::tableize('ArtistsGenre'), 'artists_genres');
		$this->assertEqual(Inflector::tableize('FileSystem'), 'file_systems');
		$this->assertEqual(Inflector::tableize('News'), 'news');
		$this->assertEqual(Inflector::tableize('Bureau'), 'bureaus');
	}

/**
 * testHumanization method
 *
 * @return void
 */
	public function testHumanization() {
		$this->assertEqual(Inflector::humanize('posts'), 'Posts');
		$this->assertEqual(Inflector::humanize('posts_tags'), 'Posts Tags');
		$this->assertEqual(Inflector::humanize('file_systems'), 'File Systems');
	}

/**
 * testCustomPluralRule method
 *
 * @return void
 */
	public function testCustomPluralRule() {
		Inflector::rules('plural', array('/^(custom)$/i' => '\1izables'));
		$this->assertEqual(Inflector::pluralize('custom'), 'customizables');

		Inflector::rules('plural', array('uninflected' => array('uninflectable')));
		$this->assertEqual(Inflector::pluralize('uninflectable'), 'uninflectable');

		Inflector::rules('plural', array(
			'rules' => array('/^(alert)$/i' => '\1ables'),
			'uninflected' => array('noflect', 'abtuse'),
			'irregular' => array('amaze' => 'amazable', 'phone' => 'phonezes')
		));
		$this->assertEqual(Inflector::pluralize('noflect'), 'noflect');
		$this->assertEqual(Inflector::pluralize('abtuse'), 'abtuse');
		$this->assertEqual(Inflector::pluralize('alert'), 'alertables');
		$this->assertEqual(Inflector::pluralize('amaze'), 'amazable');
		$this->assertEqual(Inflector::pluralize('phone'), 'phonezes');
	}

/**
 * testCustomSingularRule method
 *
 * @return void
 */
	public function testCustomSingularRule() {
		Inflector::rules('singular', array('/(eple)r$/i' => '\1', '/(jente)r$/i' => '\1'));

		$this->assertEqual(Inflector::singularize('epler'), 'eple');
		$this->assertEqual(Inflector::singularize('jenter'), 'jente');

		Inflector::rules('singular', array(
			'rules' => array('/^(bil)er$/i' => '\1', '/^(inflec|contribu)tors$/i' => '\1ta'),
			'uninflected' => array('singulars'),
			'irregular' => array('spins' => 'spinor')
		));

		$this->assertEqual(Inflector::singularize('inflectors'), 'inflecta');
		$this->assertEqual(Inflector::singularize('contributors'), 'contributa');
		$this->assertEqual(Inflector::singularize('spins'), 'spinor');
		$this->assertEqual(Inflector::singularize('singulars'), 'singulars');
	}

/**
 * testCustomTransliterationRule method
 *
 * @return void
 */
	public function testCustomTransliterationRule() {
		$this->assertEqual(Inflector::slug('Testing æ ø å'), 'Testing_ae_o_a');

		Inflector::rules('transliteration', array('/å/' => 'aa', '/ø/' => 'oe'));
		$this->assertEqual(Inflector::slug('Testing æ ø å'), 'Testing_ae_oe_aa');

		Inflector::rules('transliteration', array('/ä|æ/' => 'ae', '/å/' => 'aa'), true);
		$this->assertEqual(Inflector::slug('Testing æ ø å'), 'Testing_ae_ø_aa');
	}

/**
 * test that setting new rules clears the inflector caches.
 *
 * @return void
 */
	public function testRulesClearsCaches() {
		$this->assertEqual(Inflector::singularize('Bananas'), 'Banana');
		$this->assertEqual(Inflector::tableize('Banana'), 'bananas');
		$this->assertEqual(Inflector::pluralize('Banana'), 'Bananas');

		Inflector::rules('singular', array(
			'rules' => array('/(.*)nas$/i' => '\1zzz')
		));
		$this->assertEquals('Banazzz', Inflector::singularize('Bananas'), 'Was inflected with old rules.');

		Inflector::rules('plural', array(
			'rules' => array('/(.*)na$/i' => '\1zzz'),
			'irregular' => array('corpus' => 'corpora')
		));
		$this->assertEqual(Inflector::pluralize('Banana'), 'Banazzz', 'Was inflected with old rules.');
		$this->assertEqual(Inflector::pluralize('corpus'), 'corpora', 'Was inflected with old irregular form.');
	}

/**
 * Test resetting inflection rules.
 *
 * @return void
 */
	public function testCustomRuleWithReset() {
		$uninflected = array('atlas', 'lapis', 'onibus', 'pires', 'virus', '.*x');
		$pluralIrregular = array('as' => 'ases');

		Inflector::rules('singular', array(
			'rules' => array('/^(.*)(a|e|o|u)is$/i' => '\1\2l'),
			'uninflected' => $uninflected,
		), true);

		Inflector::rules('plural', array(
			'rules' => array(
				'/^(.*)(a|e|o|u)l$/i' => '\1\2is',
			),
			'uninflected' => $uninflected,
			'irregular' => $pluralIrregular
		), true);

		$this->assertEqual(Inflector::pluralize('Alcool'), 'Alcoois');
		$this->assertEqual(Inflector::pluralize('Atlas'), 'Atlas');
		$this->assertEqual(Inflector::singularize('Alcoois'), 'Alcool');
		$this->assertEqual(Inflector::singularize('Atlas'), 'Atlas');
	}

}
