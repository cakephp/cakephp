<?php
/**
 * TextHelperTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('View', 'View');
App::uses('TextHelper', 'View/Helper');

/**
 * TextHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class TextHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		$controller = null;
		$this->View = new View($controller);
		$this->Text = new TextHelper($this->View);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->View, $this->Text);
	}

/**
 * testTruncate method
 *
 * @return void
 */
	public function testTruncate() {
		$text1 = 'The quick brown fox jumps over the lazy dog';
		$text2 = 'Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung';
		$text3 = '<b>&copy; 2005-2007, Cake Software Foundation, Inc.</b><br />written by Alexander Wegener';
		$text4 = '<img src="mypic.jpg"> This image tag is not XHTML conform!<br><hr/><b>But the following image tag should be conform <img src="mypic.jpg" alt="Me, myself and I" /></b><br />Great, or?';
		$text5 = '0<b>1<i>2<span class="myclass">3</span>4<u>5</u>6</i>7</b>8<b>9</b>0';
        $text6 = '<p><strong>Extra dates have been announced for this year\'s tour.</strong></p><p>Tickets for the new shows in</p>';
        $text7 = 'El moño está en el lugar correcto. Eso fue lo que dijo la niña, ¿habrá dicho la verdad?';
        $text8 = 'Vive la R'.chr(195).chr(169).'publique de France';
		$text9 = 'НОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыь';

		$this->assertSame($this->Text->truncate($text1, 15), 'The quick br...');
		$this->assertSame($this->Text->truncate($text1, 15, array('exact' => false)), 'The quick...');
		$this->assertSame($this->Text->truncate($text1, 100), 'The quick brown fox jumps over the lazy dog');
		$this->assertSame($this->Text->truncate($text2, 10), 'Heiz&ou...');
		$this->assertSame($this->Text->truncate($text2, 10, array('exact' => false)), '...');
		$this->assertSame($this->Text->truncate($text3, 20), '<b>&copy; 2005-20...');
		$this->assertSame($this->Text->truncate($text4, 15), '<img src="my...');
		$this->assertSame($this->Text->truncate($text5, 6, array('ending' => '')), '0<b>1<');
		$this->assertSame($this->Text->truncate($text1, 15, array('html' => true)), 'The quick br...');
		$this->assertSame($this->Text->truncate($text1, 15, array('exact' => false, 'html' => true)), 'The quick...');
		$this->assertSame($this->Text->truncate($text2, 10, array('html' => true)), 'Heiz&ouml;lr...');
		$this->assertSame($this->Text->truncate($text2, 10, array('exact' => false, 'html' => true)), '...');
		$this->assertSame($this->Text->truncate($text3, 20, array('html' => true)), '<b>&copy; 2005-2007, Cake...</b>');
		$this->assertSame($this->Text->truncate($text4, 15, array('html' => true)), '<img src="mypic.jpg"> This image ...');
		$this->assertSame($this->Text->truncate($text4, 45, array('html' => true)), '<img src="mypic.jpg"> This image tag is not XHTML conform!<br><hr/><b>But t...</b>');
		$this->assertSame($this->Text->truncate($text4, 90, array('html' => true)), '<img src="mypic.jpg"> This image tag is not XHTML conform!<br><hr/><b>But the following image tag should be conform <img src="mypic.jpg" alt="Me, myself and I" /></b><br />Grea...');
		$this->assertSame($this->Text->truncate($text5, 6, array('ending' => '', 'html' => true)), '0<b>1<i>2<span class="myclass">3</span>4<u>5</u></i></b>');
		$this->assertSame($this->Text->truncate($text5, 20, array('ending' => '', 'html' => true)), $text5);
		$this->assertSame($this->Text->truncate($text6, 57, array('exact' => false, 'html' => true)), "<p><strong>Extra dates have been announced for this year's...</strong></p>");
		$this->assertSame($this->Text->truncate($text7, 255), $text7);
		$this->assertSame($this->Text->truncate($text7, 15), 'El moño está...');
		$this->assertSame($this->Text->truncate($text8, 15), 'Vive la R'.chr(195).chr(169).'pu...');
		$this->assertSame($this->Text->truncate($text9, 10), 'НОПРСТУ...');
	}

/**
 * testHighlight method
 *
 * @return void
 */
	public function testHighlight() {
		$text = 'This is a test text';
		$phrases = array('This', 'text');
		$result = $this->Text->highlight($text, $phrases, array('format' => '<b>\1</b>'));
		$expected = '<b>This</b> is a test <b>text</b>';
		$this->assertEquals($expected, $result);

		$text = 'This is a test text';
		$phrases = null;
		$result = $this->Text->highlight($text, $phrases, array('format' => '<b>\1</b>'));
		$this->assertEquals($result, $text);

		$text = 'This is a (test) text';
		$phrases = '(test';
		$result = $this->Text->highlight($text, $phrases, array('format' => '<b>\1</b>'));
		$this->assertEquals('This is a <b>(test</b>) text', $result);

		$text = 'Ich saß in einem Café am Übergang';
		$expected = 'Ich <b>saß</b> in einem <b>Café</b> am <b>Übergang</b>';
		$phrases = array('saß', 'café', 'übergang');
		$result = $this->Text->highlight($text, $phrases, array('format' => '<b>\1</b>'));
		$this->assertEquals($expected, $result);
	}

/**
 * testHighlightHtml method
 *
 * @return void
 */
	public function testHighlightHtml() {
		$text1 = '<p>strongbow isn&rsquo;t real cider</p>';
		$text2 = '<p>strongbow <strong>isn&rsquo;t</strong> real cider</p>';
		$text3 = '<img src="what-a-strong-mouse.png" alt="What a strong mouse!" />';
		$text4 = 'What a strong mouse: <img src="what-a-strong-mouse.png" alt="What a strong mouse!" />';
		$options = array('format' => '<b>\1</b>', 'html' => true);

		$expected = '<p><b>strong</b>bow isn&rsquo;t real cider</p>';
		$this->assertEquals($this->Text->highlight($text1, 'strong', $options), $expected);

		$expected = '<p><b>strong</b>bow <strong>isn&rsquo;t</strong> real cider</p>';
		$this->assertEquals($this->Text->highlight($text2, 'strong', $options), $expected);

		$this->assertEquals($this->Text->highlight($text3, 'strong', $options), $text3);

		$this->assertEquals($this->Text->highlight($text3, array('strong', 'what'), $options), $text3);

		$expected = '<b>What</b> a <b>strong</b> mouse: <img src="what-a-strong-mouse.png" alt="What a strong mouse!" />';
		$this->assertEquals($this->Text->highlight($text4, array('strong', 'what'), $options), $expected);
	}

/**
 * testHighlightMulti method
 *
 * @return void
 */
	public function testHighlightMulti() {
		$text = 'This is a test text';
		$phrases = array('This', 'text');
		$result = $this->Text->highlight($text, $phrases, array('format' => array('<b>\1</b>', '<em>\1</em>')));
		$expected = '<b>This</b> is a test <em>text</em>';
		$this->assertEquals($expected, $result);

	}

/**
 * testStripLinks method
 *
 * @return void
 */
	public function testStripLinks() {
		$text = 'This is a test text';
		$expected = 'This is a test text';
		$result = $this->Text->stripLinks($text);
		$this->assertEquals($expected, $result);

		$text = 'This is a <a href="#">test</a> text';
		$expected = 'This is a test text';
		$result = $this->Text->stripLinks($text);
		$this->assertEquals($expected, $result);

		$text = 'This <strong>is</strong> a <a href="#">test</a> <a href="#">text</a>';
		$expected = 'This <strong>is</strong> a test text';
		$result = $this->Text->stripLinks($text);
		$this->assertEquals($expected, $result);

		$text = 'This <strong>is</strong> a <a href="#">test</a> and <abbr>some</abbr> other <a href="#">text</a>';
		$expected = 'This <strong>is</strong> a test and <abbr>some</abbr> other text';
		$result = $this->Text->stripLinks($text);
		$this->assertEquals($expected, $result);
	}

/**
 * testAutoLink method
 *
 * @return void
 */
	public function testAutoLink() {
		$text = 'This is a test text';
		$expected = 'This is a test text';
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with a partial www.cakephp.org URL and test@cakephp.org email address';
		$result = $this->Text->autoLink($text);
		$expected = 'Text with a partial <a href="http://www.cakephp.org">www.cakephp.org</a> URL and <a href="mailto:test@cakephp\.org">test@cakephp\.org</a> email address';
		$this->assertRegExp('#^' . $expected . '$#', $result);

		$text = 'This is a test text with URL http://www.cakephp.org';
		$expected = 'This is a test text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>';
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = 'This is a test text with URL http://www.cakephp.org and some more text';
		$expected = 'This is a test text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a> and some more text';
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = "This is a test text with URL http://www.cakephp.org\tand some more text";
		$expected = "This is a test text with URL <a href=\"http://www.cakephp.org\">http://www.cakephp.org</a>\tand some more text";
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = 'This is a test text with URL http://www.cakephp.org(and some more text)';
		$expected = 'This is a test text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>(and some more text)';
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = 'This is a test text with URL http://www.cakephp.org';
		$expected = 'This is a test text with URL <a href="http://www.cakephp.org" class="link">http://www.cakephp.org</a>';
		$result = $this->Text->autoLink($text, array('class' => 'link'));
		$this->assertEquals($expected, $result);

		$text = 'This is a test text with URL http://www.cakephp.org';
		$expected = 'This is a test text with URL <a href="http://www.cakephp.org" class="link" id="MyLink">http://www.cakephp.org</a>';
		$result = $this->Text->autoLink($text, array('class' => 'link', 'id' => 'MyLink'));
		$this->assertEquals($expected, $result);
	}

/**
 * Test escaping for autoLink
 *
 * @return void
 */
	public function testAutoLinkEscape() {
		$text = 'This is a <b>test</b> text with URL http://www.cakephp.org';
		$expected = 'This is a &lt;b&gt;test&lt;/b&gt; text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>';
		$result = $this->Text->autoLink($text);
		$this->assertEquals($expected, $result);

		$text = 'This is a <b>test</b> text with URL http://www.cakephp.org';
		$expected = 'This is a <b>test</b> text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>';
		$result = $this->Text->autoLink($text, array('escape' => false));
		$this->assertEquals($expected, $result);
	}

/**
 * testAutoLinkUrls method
 *
 * @return void
 */
	public function testAutoLinkUrls() {
		$text = 'This is a test text';
		$expected = 'This is a test text';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);

		$text = 'This is a test that includes (www.cakephp.org)';
		$expected = 'This is a test that includes (<a href="http://www.cakephp.org">www.cakephp.org</a>)';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with a partial www.cakephp.org URL';
		$expected = 'Text with a partial <a href="http://www.cakephp.org"\s*>www.cakephp.org</a> URL';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertRegExp('#^' . $expected . '$#', $result);

		$text = 'Text with a partial www.cakephp.org URL';
		$expected = 'Text with a partial <a href="http://www.cakephp.org" \s*class="link">www.cakephp.org</a> URL';
		$result = $this->Text->autoLinkUrls($text, array('class' => 'link'));
		$this->assertRegExp('#^' . $expected . '$#', $result);

		$text = 'Text with a partial WWW.cakephp.org URL';
		$expected = 'Text with a partial <a href="http://WWW.cakephp.org"\s*>WWW.cakephp.org</a> URL';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertRegExp('#^' . $expected . '$#', $result);

		$text = 'Text with a partial WWW.cakephp.org &copy; URL';
		$expected = 'Text with a partial <a href="http://WWW.cakephp.org"\s*>WWW.cakephp.org</a> &copy; URL';
		$result = $this->Text->autoLinkUrls($text, array('escape' => false));
		$this->assertRegExp('#^' . $expected . '$#', $result);

		$text = 'Text with a url www.cot.ag/cuIb2Q and more';
		$expected = 'Text with a url <a href="http://www.cot.ag/cuIb2Q">www.cot.ag/cuIb2Q</a> and more';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with a url http://www.does--not--work.com and more';
		$expected = 'Text with a url <a href="http://www.does--not--work.com">http://www.does--not--work.com</a> and more';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with a url http://www.not--work.com and more';
		$expected = 'Text with a url <a href="http://www.not--work.com">http://www.not--work.com</a> and more';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);
	}

/**
 * Test autoLinkUrls with the escape option.
 *
 * @return void
 */
	public function testAutoLinkUrlsEscape() {
		$text = 'Text with a partial <a href="http://www.cakephp.org">link</a> link';
		$expected = 'Text with a partial <a href="http://www.cakephp.org">link</a> link';
		$result = $this->Text->autoLinkUrls($text, array('escape' => false));
		$this->assertEquals($expected, $result);

		$text = 'Text with a partial <iframe src="http://www.cakephp.org" /> link';
		$expected = 'Text with a partial <iframe src="http://www.cakephp.org" /> link';
		$result = $this->Text->autoLinkUrls($text, array('escape' => false));
		$this->assertEquals($expected, $result);

		$text = 'Text with a partial <iframe src="http://www.cakephp.org" /> link';
		$expected = 'Text with a partial &lt;iframe src=&quot;http://www.cakephp.org&quot; /&gt; link';
		$result = $this->Text->autoLinkUrls($text, array('escape' => true));
		$this->assertEquals($expected, $result);
	}

/**
 * testAutoLinkEmails method
 *
 * @return void
 */
	public function testAutoLinkEmails() {
		$text = 'This is a test text';
		$expected = 'This is a test text';
		$result = $this->Text->autoLinkUrls($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with email@example.com address';
		$expected = 'Text with <a href="mailto:email@example.com"\s*>email@example.com</a> address';
		$result = $this->Text->autoLinkEmails($text);
		$this->assertRegExp('#^' . $expected . '$#', $result);

		$text = "Text with o'hare._-bob@example.com address";
		$expected = 'Text with <a href="mailto:o&#039;hare._-bob@example.com">o&#039;hare._-bob@example.com</a> address';
		$result = $this->Text->autoLinkEmails($text);
		$this->assertEquals($expected, $result);

		$text = 'Text with email@example.com address';
		$expected = 'Text with <a href="mailto:email@example.com" \s*class="link">email@example.com</a> address';
		$result = $this->Text->autoLinkEmails($text, array('class' => 'link'));
		$this->assertRegExp('#^' . $expected . '$#', $result);
	}

/**
 * test invalid email addresses.
 *
 * @return void
 */
	public function testAutoLinkEmailInvalid() {
		$result = $this->Text->autoLinkEmails('this is a myaddress@gmx-de test');
		$expected = 'this is a myaddress@gmx-de test';
		$this->assertEquals($expected, $result);
	}

/**
 * testHighlightCaseInsensitivity method
 *
 * @return void
 */
	public function testHighlightCaseInsensitivity() {
		$text = 'This is a Test text';
		$expected = 'This is a <b>Test</b> text';

		$result = $this->Text->highlight($text, 'test', array('format' => '<b>\1</b>'));
		$this->assertEquals($expected, $result);

		$result = $this->Text->highlight($text, array('test'), array('format' => '<b>\1</b>'));
		$this->assertEquals($expected, $result);
	}

/**
 * testExcerpt method
 *
 * @return void
 */
	public function testExcerpt() {
		$text = 'This is a phrase with test text to play with';

		$expected = '...ase with test text to ...';
		$result = $this->Text->excerpt($text, 'test', 9, '...');
		$this->assertEquals($expected, $result);

		$expected = 'This is a...';
		$result = $this->Text->excerpt($text, 'not_found', 9, '...');
		$this->assertEquals($expected, $result);

		$expected = 'This is a phras...';
		$result = $this->Text->excerpt($text, null, 9, '...');
		$this->assertEquals($expected, $result);

		$expected = $text;
		$result = $this->Text->excerpt($text, null, 200, '...');
		$this->assertEquals($expected, $result);

		$expected = '...a phrase w...';
		$result = $this->Text->excerpt($text, 'phrase', 2, '...');
		$this->assertEquals($expected, $result);

		$phrase = 'This is a phrase with test text';
		$expected = $text;
		$result = $this->Text->excerpt($text, $phrase, 13, '...');
		$this->assertEquals($expected, $result);
		
		$text = 'aaaaaaaaaaaaaaaaaaaaaaaabbbbbbbbaaaaaaaaaaaaaaaaaaaaaaaa';
		$phrase = 'bbbbbbbb';
		$result = $this->Text->excerpt($text, $phrase, 10);
		$expected = '...aaaaaaaaaabbbbbbbbaaaaaaaaaa...';
		$this->assertEquals($expected, $result);
	}

/**
 * testExcerptCaseInsensitivity method
 *
 * @return void
 */
	public function testExcerptCaseInsensitivity() {
		$text = 'This is a phrase with test text to play with';

		$expected = '...ase with test text to ...';
		$result = $this->Text->excerpt($text, 'TEST', 9, '...');
		$this->assertEquals($expected, $result);

		$expected = 'This is a...';
		$result = $this->Text->excerpt($text, 'NOT_FOUND', 9, '...');
		$this->assertEquals($expected, $result);
	}

/**
 * testListGeneration method
 *
 * @return void
 */
	public function testListGeneration() {
		$result = $this->Text->toList(array());
		$this->assertEquals($result, '');

		$result = $this->Text->toList(array('One'));
		$this->assertEquals($result, 'One');

		$result = $this->Text->toList(array('Larry', 'Curly', 'Moe'));
		$this->assertEquals($result, 'Larry, Curly and Moe');

		$result = $this->Text->toList(array('Dusty', 'Lucky', 'Ned'), 'y');
		$this->assertEquals($result, 'Dusty, Lucky y Ned');

		$result = $this->Text->toList(array(1 => 'Dusty', 2 => 'Lucky', 3 => 'Ned'), 'y');
		$this->assertEquals($result, 'Dusty, Lucky y Ned');

		$result = $this->Text->toList(array(1 => 'Dusty', 2 => 'Lucky', 3 => 'Ned'), 'and', ' + ');
		$this->assertEquals($result, 'Dusty + Lucky and Ned');

		$result = $this->Text->toList(array('name1' => 'Dusty', 'name2' => 'Lucky'));
		$this->assertEquals($result, 'Dusty and Lucky');

		$result = $this->Text->toList(array('test_0' => 'banana', 'test_1' => 'apple', 'test_2' => 'lemon'));
		$this->assertEquals($result, 'banana, apple and lemon');
	}
}
