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
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.view.helpers
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('view'.DS.'helpers'.DS.'app_helper', 'view'.DS.'helper', 'view'.DS.'helpers'.DS.'text');
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.view.helpers
 */
class TextTest extends UnitTestCase {
	var $helper = null;

	function setUp() {
		$this->Text = new TextHelper();
	}

	function testTruncate() {
		$text1 = 'The quick brown fox jumps over the lazy dog';
		$text2 = 'Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung';
		$text3 = '<b>&copy; 2005-2007, Cake Software Foundation, Inc.</b><br />written by Alexander Wegener';
		$text4 = '<img src="mypic.jpg"> This image tag is not XHTML conform!<br><hr/><b>But the following image tag should be conform <img src="mypic.jpg" alt="Me, myself and I" /></b><br />Great, or?';
		$text5 = '0<b>1<i>2<span class="myclass">3</span>4<u>5</u>6</i>7</b>8<b>9</b>0';

		$this->assertIdentical($this->Text->truncate($text1, 15), 'The quick br...');
		$this->assertIdentical($this->Text->truncate($text1, 15, '...', false), 'The quick...');
		$this->assertIdentical($this->Text->truncate($text1, 100), 'The quick brown fox jumps over the lazy dog');
		$this->assertIdentical($this->Text->truncate($text2, 10, '...'), 'Heiz&ou...');
		$this->assertIdentical($this->Text->truncate($text2, 10, '...', false), '...');
		$this->assertIdentical($this->Text->truncate($text3, 20), '<b>&copy; 2005-20...');
		$this->assertIdentical($this->Text->truncate($text4, 15), '<img src="my...');
		$this->assertIdentical($this->Text->truncate($text5, 6, ''), '0<b>1<');

		$this->assertIdentical($this->Text->truncate($text1, 15, array('ending' => '...', 'exact' => true, 'considerHtml' => true)), 'The quick br...');
		$this->assertIdentical($this->Text->truncate($text1, 15, '...', true, true), 'The quick br...');
		$this->assertIdentical($this->Text->truncate($text1, 15, '...', false, true), 'The quick...');
		$this->assertIdentical($this->Text->truncate($text2, 10, '...', true, true), 'Heiz&ouml;lr...');
		$this->assertIdentical($this->Text->truncate($text2, 10, '...', false, true), '...');
		$this->assertIdentical($this->Text->truncate($text3, 20, '...', true, true), '<b>&copy; 2005-2007, Cake...</b>');
		$this->assertIdentical($this->Text->truncate($text4, 15, '...', true, true), '<img src="mypic.jpg"> This image ...');
		$this->assertIdentical($this->Text->truncate($text4, 45, '...', true, true), '<img src="mypic.jpg"> This image tag is not XHTML conform!<br><hr/><b>But t...</b>');
		$this->assertIdentical($this->Text->truncate($text4, 90, '...', true, true), '<img src="mypic.jpg"> This image tag is not XHTML conform!<br><hr/><b>But the following image tag should be conform <img src="mypic.jpg" alt="Me, myself and I" /></b><br />Grea...');
		$this->assertIdentical($this->Text->truncate($text5, 6, '', true, true), '0<b>1<i>2<span class="myclass">3</span>4<u>5</u></i></b>');
	}

	function testHighlight() {
		$text = 'This is a test text';
		$phrases = array('This', 'text');
		$result = $this->Text->highlight($text, $phrases, '<b>\1</b>');
		$expected = '<b>This</b> is a test <b>text</b>';
		$this->assertEqual($expected, $result);
	}

	function testStripLinks() {
		$text = 'This is a test text';
		$expected = 'This is a test text';
		$result = $this->Text->stripLinks($text);
		$this->assertEqual($expected, $result);

		$text = 'This is a <a href="#">test</a> text';
		$expected = 'This is a test text';
		$result = $this->Text->stripLinks($text);
		$this->assertEqual($expected, $result);

		$text = 'This <strong>is</strong> a <a href="#">test</a> <a href="#">text</a>';
		$expected = 'This <strong>is</strong> a test text';
		$result = $this->Text->stripLinks($text);
		$this->assertEqual($expected, $result);

		$text = 'This <strong>is</strong> a <a href="#">test</a> and <abbr>some</abbr> other <a href="#">text</a>';
		$expected = 'This <strong>is</strong> a test and <abbr>some</abbr> other text';
		$result = $this->Text->stripLinks($text);
		$this->assertEqual($expected, $result);
	}

	function testAutoLinkUrls() {
		$text = 'This is a test text';
		$expected = 'This is a test text';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEqual($expected, $result);

		$text = 'Text with a partial www.cakephp.org URL';
		$expected = 'Text with a partial <a href="http://www.cakephp.org"\s*>www.cakephp.org</a> URL';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertPattern('#^' . $expected . '$#', $result);

		$text = 'Text with a partial www.cakephp.org URL';
		$expected = 'Text with a partial <a href="http://www.cakephp.org" \s*class="link">www.cakephp.org</a> URL';
		$result = $this->Text->autoLinkUrls($text, array('class' => 'link'));
		$this->assertPattern('#^' . $expected . '$#', $result);

		$text = 'Text with a partial WWW.cakephp.org URL';
		$expected = 'Text with a partial <a href="http://www.cakephp.org"\s*>WWW.cakephp.org</a> URL';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertPattern('#^' . $expected . '$#', $result);

		$text = 'Text with a partial WWW.cakephp.org &copy; URL';
		$expected = 'Text with a partial <a href="http://www.cakephp.org"\s*>WWW.cakephp.org</a> &copy; URL';
		$result = $this->Text->autoLinkUrls($text, array('escape' => false));
		$this->assertPattern('#^' . $expected . '$#', $result);

	}

	function testAutoLinkEmails() {
		$text = 'This is a test text';
		$expected = 'This is a test text';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEqual($expected, $result);

		$text = 'Text with email@example.com address';
		$expected = 'Text with <a href="mailto:email@example.com"\s*>email@example.com</a> address';
		$result = $this->Text->autoLinkEmails($text);
		$this->assertPattern('#^' . $expected . '$#', $result);

		$text = 'Text with email@example.com address';
		$expected = 'Text with <a href="mailto:email@example.com" \s*class="link">email@example.com</a> address';
		$result = $this->Text->autoLinkEmails($text, array('class' => 'link'));
		$this->assertPattern('#^' . $expected . '$#', $result);
	}

	function testHighlightCaseInsensitivity() {
		$text = 'This is a Test text';
		$expected = 'This is a <b>Test</b> text';

		$result = $this->Text->highlight($text, 'test', '<b>\1</b>');
		$this->assertEqual($expected, $result);

		$result = $this->Text->highlight($text, array('test'), '<b>\1</b>');
		$this->assertEqual($expected, $result);
	}

	function testExcerpt() {
		$text = 'This is a phrase with test text to play with';

		$expected = '...with test text...';
		$result = $this->Text->excerpt($text, 'test', 9, '...');
		$this->assertEqual($expected, $result);

		$expected = 'This is a...';
		$result = $this->Text->excerpt($text, 'not_found', 9, '...');
		$this->assertEqual($expected, $result);
	}

	function testExcerptCaseInsensitivity() {
		$text = 'This is a phrase with test text to play with';

		$expected = '...with test text...';
		$result = $this->Text->excerpt($text, 'TEST', 9, '...');
		$this->assertEqual($expected, $result);

		$expected = 'This is a...';
		$result = $this->Text->excerpt($text, 'NOT_FOUND', 9, '...');
		$this->assertEqual($expected, $result);
	}

	function tearDown() {
		unset($this->Text);
	}
}

?>