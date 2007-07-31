<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP Test Suite <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * Author(s): Larry E. Masters aka PhpNut <phpnut@gmail.com>
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @author       Larry E. Masters aka PhpNut <phpnut@gmail.com>
 * @copyright    Copyright (c) 2006, Larry E. Masters Shorewood, IL. 60431
 * @link         http://www.phpnut.com/projects/
 * @package      test_suite
 * @subpackage   test_suite.cases.app
 * @since        CakePHP Test Suite v 1.0.0.0
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once CAKE.'app_helper.php';
uses('class_registry', 'controller'.DS.'controller', 'model'.DS.'model', 'view'.DS.'helper',
	'view'.DS.'helpers'.DS.'html', 'view'.DS.'helpers'.DS.'form');

class TheHtmlTestController extends Controller {
	var $name = 'TheTest';
	var $uses = null;
}

class HtmlHelperTest extends UnitTestCase {
	var $html = null;

	function setUp() {
		$this->Html =& new HtmlHelper();
		$view =& new View(new TheHtmlTestController());
		ClassRegistry::addObject('view', $view);
	}

	function testLinkEscape() {
		$result = $this->Html->link('Next >', '#');
		$expected = '/^<a href="#"\s+>Next &gt;<\/a>$/';

		$this->assertPattern($expected, $result);

		$result = $this->Html->link('Next >', '#', array('escape' => false));
		$expected = '/^<a href="#"\s+>Next ><\/a>$/';

		$this->assertPattern($expected, $result);
	}

	function testImageLink() {
		$result = $this->Html->link($this->Html->image('test.gif'), '#', array(), false, false, false);
		$expected = '/^<a href="#"\s+><img\s+src="img\/test.gif"\s+alt=""\s+\/><\/a>$/';

		$this->assertPattern($expected, $result);
	}

	function testStyle() {
		$result = $this->Html->style(array('display'=> 'none', 'margin'=>'10px'));
		$expected = 'display:none; margin:10px;';
		$this->assertEqual($expected, $result);

		$result = $this->Html->style(array('display'=> 'none', 'margin'=>'10px'), false);
		$expected = "display:none;\nmargin:10px;";
		$this->assertEqual($expected, $result);

	}

	function tearDown() {
		unset($this->Html);
	}
}
?>