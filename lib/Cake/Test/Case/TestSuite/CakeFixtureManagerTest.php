<?php
/**
 * CakeFixtureManagerTest file
 *
 * Test Case for CakeFixtureManager class
 *
 * PHP version 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP v 1.2.0.4487
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * CakeFixtureManagerTest class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeFixtureManagerTest extends CakeTestCase {

	var $fixtures = array(
		'core.article*',
		'core.apple',
		'core.aro*'
	);
	var $autoFixtures = false;

/**
 * test that fixture manager loads all article fixtures using * wildcard
 *
 * @return void
 */
	function testFixtureExpand(){
		$excepted = array(
			'core.article_featured',
			'core.article_featureds_tags',
			'core.article',
			'core.articles_tag',
	 		'core.apple',
			'core.aro',
			'core.aro_two',
			'core.aros_aco',
			'core.aros_aco_two'
		);
		$this->assertEquals($excepted, $this->fixtureManager->getLoadedFixtures());
	}

}
