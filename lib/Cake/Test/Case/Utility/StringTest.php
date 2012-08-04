<?php
/**
 * StringTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Utility
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('String', 'Utility');

/**
 * StringTest class
 *
 * @package       Cake.Test.Case.Utility
 */
class StringTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$this->Text = new String();
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->Text);
	}

/**
 * testUuidGeneration method
 *
 * @return void
 */
	public function testUuidGeneration() {
		$result = String::uuid();
		$pattern = "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/";
		$match = (bool)preg_match($pattern, $result);
		$this->assertTrue($match);
	}

/**
 * testMultipleUuidGeneration method
 *
 * @return void
 */
	public function testMultipleUuidGeneration() {
		$check = array();
		$count = mt_rand(10, 1000);
		$pattern = "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/";

		for ($i = 0; $i < $count; $i++) {
			$result = String::uuid();
			$match = (bool)preg_match($pattern, $result);
			$this->assertTrue($match);
			$this->assertFalse(in_array($result, $check));
			$check[] = $result;
		}
	}

/**
 * testInsert method
 *
 * @return void
 */
	public function testInsert() {
		$string = 'some string';
		$expected = 'some string';
		$result = String::insert($string, array());
		$this->assertEquals($expected, $result);

		$string = '2 + 2 = :sum. Cake is :adjective.';
		$expected = '2 + 2 = 4. Cake is yummy.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'));
		$this->assertEquals($expected, $result);

		$string = '2 + 2 = %sum. Cake is %adjective.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('before' => '%'));
		$this->assertEquals($expected, $result);

		$string = '2 + 2 = 2sum2. Cake is 9adjective9.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('format' => '/([\d])%s\\1/'));
		$this->assertEquals($expected, $result);

		$string = '2 + 2 = 12sum21. Cake is 23adjective45.';
		$expected = '2 + 2 = 4. Cake is 23adjective45.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('format' => '/([\d])([\d])%s\\2\\1/'));
		$this->assertEquals($expected, $result);

		$string = ':web :web_site';
		$expected = 'www http';
		$result = String::insert($string, array('web' => 'www', 'web_site' => 'http'));
		$this->assertEquals($expected, $result);

		$string = '2 + 2 = <sum. Cake is <adjective>.';
		$expected = '2 + 2 = <sum. Cake is yummy.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('before' => '<', 'after' => '>'));
		$this->assertEquals($expected, $result);

		$string = '2 + 2 = \:sum. Cake is :adjective.';
		$expected = '2 + 2 = :sum. Cake is yummy.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'));
		$this->assertEquals($expected, $result);

		$string = '2 + 2 = !:sum. Cake is :adjective.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('escape' => '!'));
		$this->assertEquals($expected, $result);

		$string = '2 + 2 = \%sum. Cake is %adjective.';
		$expected = '2 + 2 = %sum. Cake is yummy.';
		$result = String::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array('before' => '%'));
		$this->assertEquals($expected, $result);

		$string = ':a :b \:a :a';
		$expected = '1 2 :a 1';
		$result = String::insert($string, array('a' => 1, 'b' => 2));
		$this->assertEquals($expected, $result);

		$string = ':a :b :c';
		$expected = '2 3';
		$result = String::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEquals($expected, $result);

		$string = ':a :b :c';
		$expected = '1 3';
		$result = String::insert($string, array('a' => 1, 'c' => 3), array('clean' => true));
		$this->assertEquals($expected, $result);

		$string = ':a :b :c';
		$expected = '2 3';
		$result = String::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEquals($expected, $result);

		$string = ':a, :b and :c';
		$expected = '2 and 3';
		$result = String::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEquals($expected, $result);

		$string = '":a, :b and :c"';
		$expected = '"1, 2"';
		$result = String::insert($string, array('a' => 1, 'b' => 2), array('clean' => true));
		$this->assertEquals($expected, $result);

		$string = '"${a}, ${b} and ${c}"';
		$expected = '"1, 2"';
		$result = String::insert($string, array('a' => 1, 'b' => 2), array('before' => '${', 'after' => '}', 'clean' => true));
		$this->assertEquals($expected, $result);

		$string = '<img src=":src" alt=":alt" class="foo :extra bar"/>';
		$expected = '<img src="foo" class="foo bar"/>';
		$result = String::insert($string, array('src' => 'foo'), array('clean' => 'html'));

		$this->assertEquals($expected, $result);

		$string = '<img src=":src" class=":no :extra"/>';
		$expected = '<img src="foo"/>';
		$result = String::insert($string, array('src' => 'foo'), array('clean' => 'html'));
		$this->assertEquals($expected, $result);

		$string = '<img src=":src" class=":no :extra"/>';
		$expected = '<img src="foo" class="bar"/>';
		$result = String::insert($string, array('src' => 'foo', 'extra' => 'bar'), array('clean' => 'html'));
		$this->assertEquals($expected, $result);

		$result = String::insert("this is a ? string", "test");
		$expected = "this is a test string";
		$this->assertEquals($expected, $result);

		$result = String::insert("this is a ? string with a ? ? ?", array('long', 'few?', 'params', 'you know'));
		$expected = "this is a long string with a few? params you know";
		$this->assertEquals($expected, $result);

		$result = String::insert('update saved_urls set url = :url where id = :id', array('url' => 'http://www.testurl.com/param1:url/param2:id','id' => 1));
		$expected = "update saved_urls set url = http://www.testurl.com/param1:url/param2:id where id = 1";
		$this->assertEquals($expected, $result);

		$result = String::insert('update saved_urls set url = :url where id = :id', array('id' => 1, 'url' => 'http://www.testurl.com/param1:url/param2:id'));
		$expected = "update saved_urls set url = http://www.testurl.com/param1:url/param2:id where id = 1";
		$this->assertEquals($expected, $result);

		$result = String::insert(':me cake. :subject :verb fantastic.', array('me' => 'I :verb', 'subject' => 'cake', 'verb' => 'is'));
		$expected = "I :verb cake. cake is fantastic.";
		$this->assertEquals($expected, $result);

		$result = String::insert(':I.am: :not.yet: passing.', array('I.am' => 'We are'), array('before' => ':', 'after' => ':', 'clean' => array('replacement' => ' of course', 'method' => 'text')));
		$expected = "We are of course passing.";
		$this->assertEquals($expected, $result);

		$result = String::insert(
			':I.am: :not.yet: passing.',
			array('I.am' => 'We are'),
			array('before' => ':', 'after' => ':', 'clean' => true)
		);
		$expected = "We are passing.";
		$this->assertEquals($expected, $result);

		$result = String::insert('?-pended result', array('Pre'));
		$expected = "Pre-pended result";
		$this->assertEquals($expected, $result);

		$string = 'switching :timeout / :timeout_count';
		$expected = 'switching 5 / 10';
		$result = String::insert($string, array('timeout' => 5, 'timeout_count' => 10));
		$this->assertEquals($expected, $result);

		$string = 'switching :timeout / :timeout_count';
		$expected = 'switching 5 / 10';
		$result = String::insert($string, array('timeout_count' => 10, 'timeout' => 5));
		$this->assertEquals($expected, $result);

		$string = 'switching :timeout_count by :timeout';
		$expected = 'switching 10 by 5';
		$result = String::insert($string, array('timeout' => 5, 'timeout_count' => 10));
		$this->assertEquals($expected, $result);

		$string = 'switching :timeout_count by :timeout';
		$expected = 'switching 10 by 5';
		$result = String::insert($string, array('timeout_count' => 10, 'timeout' => 5));
		$this->assertEquals($expected, $result);
	}

/**
 * test Clean Insert
 *
 * @return void
 */
	public function testCleanInsert() {
		$result = String::cleanInsert(':incomplete', array(
			'clean' => true, 'before' => ':', 'after' => ''
		));
		$this->assertEquals('', $result);

		$result = String::cleanInsert(':incomplete', array(
			'clean' => array('method' => 'text', 'replacement' => 'complete'),
			'before' => ':', 'after' => '')
		);
		$this->assertEquals('complete', $result);

		$result = String::cleanInsert(':in.complete', array(
			'clean' => true, 'before' => ':', 'after' => ''
		));
		$this->assertEquals('', $result);

		$result = String::cleanInsert(':in.complete and', array(
			'clean' => true, 'before' => ':', 'after' => '')
		);
		$this->assertEquals('', $result);

		$result = String::cleanInsert(':in.complete or stuff', array(
			'clean' => true, 'before' => ':', 'after' => ''
		));
		$this->assertEquals('stuff', $result);

		$result = String::cleanInsert(
			'<p class=":missing" id=":missing">Text here</p>',
			array('clean' => 'html', 'before' => ':', 'after' => '')
		);
		$this->assertEquals('<p>Text here</p>', $result);
	}

/**
 * Tests that non-insertable variables (i.e. arrays) are skipped when used as values in
 * String::insert().
 *
 * @return void
 */
	public function testAutoIgnoreBadInsertData() {
		$data = array('foo' => 'alpha', 'bar' => 'beta', 'fale' => array());
		$result = String::insert('(:foo > :bar || :fale!)', $data, array('clean' => 'text'));
		$this->assertEquals('(alpha > beta || !)', $result);
	}

/**
 * testTokenize method
 *
 * @return void
 */
	public function testTokenize() {
		$result = String::tokenize('A,(short,boring test)');
		$expected = array('A', '(short,boring test)');
		$this->assertEquals($expected, $result);

		$result = String::tokenize('A,(short,more interesting( test)');
		$expected = array('A', '(short,more interesting( test)');
		$this->assertEquals($expected, $result);

		$result = String::tokenize('A,(short,very interesting( test))');
		$expected = array('A', '(short,very interesting( test))');
		$this->assertEquals($expected, $result);

		$result = String::tokenize('"single tag"', ' ', '"', '"');
		$expected = array('"single tag"');
		$this->assertEquals($expected, $result);

		$result = String::tokenize('tagA "single tag" tagB', ' ', '"', '"');
		$expected = array('tagA', '"single tag"', 'tagB');
		$this->assertEquals($expected, $result);
	}

	public function testReplaceWithQuestionMarkInString() {
		$string = ':a, :b and :c?';
		$expected = '2 and 3?';
		$result = String::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEquals($expected, $result);
	}

/**
 * test wrap method.
 *
 * @return void
 */
	public function testWrap() {
		$text = 'This is the song that never ends. This is the song that never ends. This is the song that never ends.';
		$result = String::wrap($text, 33);
		$expected = <<<TEXT
This is the song that never ends.
This is the song that never ends.
This is the song that never ends.
TEXT;
		$this->assertTextEquals($expected, $result, 'Text not wrapped.');

		$result = String::wrap($text, array('width' => 20, 'wordWrap' => false));
		$expected = <<<TEXT
This is the song th
at never ends. This
 is the song that n
ever ends. This is 
the song that never
 ends.
TEXT;
		$this->assertTextEquals($expected, $result, 'Text not wrapped.');
	}

/**
 * test wrap() indenting
 *
 * @return void
 */
	public function testWrapIndent() {
		$text = 'This is the song that never ends. This is the song that never ends. This is the song that never ends.';
		$result = String::wrap($text, array('width' => 33, 'indent' => "\t", 'indentAt' => 1));
		$expected = <<<TEXT
This is the song that never ends.
	This is the song that never ends.
	This is the song that never ends.
TEXT;
		$this->assertTextEquals($expected, $result);
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
		$text8 = 'Vive la R' . chr(195) . chr(169) . 'publique de France';
		$text9 = 'НОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыь';
		$text10 = 'http://example.com/something/foo:bar';

		$this->assertSame($this->Text->truncate($text1, 15), 'The quick br...');
		$this->assertSame($this->Text->truncate($text1, 15, array('exact' => false)), 'The quick...');
		$this->assertSame($this->Text->truncate($text1, 100), 'The quick brown fox jumps over the lazy dog');
		$this->assertSame($this->Text->truncate($text2, 10), 'Heiz&ou...');
		$this->assertSame($this->Text->truncate($text2, 10, array('exact' => false)), '...');
		$this->assertSame($this->Text->truncate($text3, 20), '<b>&copy; 2005-20...');
		$this->assertSame($this->Text->truncate($text4, 15), '<img src="my...');
		$this->assertSame($this->Text->truncate($text5, 6, array('ellipsis' => '')), '0<b>1<');
		$this->assertSame($this->Text->truncate($text1, 15, array('html' => true)), 'The quick br...');
		$this->assertSame($this->Text->truncate($text1, 15, array('exact' => false, 'html' => true)), 'The quick...');
		$this->assertSame($this->Text->truncate($text2, 10, array('html' => true)), 'Heiz&ouml;lr...');
		$this->assertSame($this->Text->truncate($text2, 10, array('exact' => false, 'html' => true)), '...');
		$this->assertSame($this->Text->truncate($text3, 20, array('html' => true)), '<b>&copy; 2005-2007, Cake...</b>');
		$this->assertSame($this->Text->truncate($text4, 15, array('html' => true)), '<img src="mypic.jpg"> This image ...');
		$this->assertSame($this->Text->truncate($text4, 45, array('html' => true)), '<img src="mypic.jpg"> This image tag is not XHTML conform!<br><hr/><b>But t...</b>');
		$this->assertSame($this->Text->truncate($text4, 90, array('html' => true)), '<img src="mypic.jpg"> This image tag is not XHTML conform!<br><hr/><b>But the following image tag should be conform <img src="mypic.jpg" alt="Me, myself and I" /></b><br />Grea...');
		$this->assertSame($this->Text->truncate($text5, 6, array('ellipsis' => '', 'html' => true)), '0<b>1<i>2<span class="myclass">3</span>4<u>5</u></i></b>');
		$this->assertSame($this->Text->truncate($text5, 20, array('ellipsis' => '', 'html' => true)), $text5);
		$this->assertSame($this->Text->truncate($text6, 57, array('exact' => false, 'html' => true)), "<p><strong>Extra dates have been announced for this year's...</strong></p>");
		$this->assertSame($this->Text->truncate($text7, 255), $text7);
		$this->assertSame($this->Text->truncate($text7, 15), 'El moño está...');
		$this->assertSame($this->Text->truncate($text8, 15), 'Vive la R' . chr(195) . chr(169) . 'pu...');
		$this->assertSame($this->Text->truncate($text9, 10), 'НОПРСТУ...');
		$this->assertSame($this->Text->truncate($text10, 30), 'http://example.com/somethin...');

		$text = '<p><span style="font-size: medium;"><a>Iamatestwithnospacesandhtml</a></span></p>';
		$result = $this->Text->truncate($text, 10, array(
			'ellipsis' => '...',
			'exact' => false,
			'html' => true
		));
		$expected = '<p><span style="font-size: medium;"><a>...</a></span></p>';
		$this->assertEquals($expected, $result);

		$text = '<p><span style="font-size: medium;">El biógrafo de Steve Jobs, Walter
Isaacson, explica porqué Jobs le pidió que le hiciera su biografía en
este artículo de El País.</span></p>
<p><span style="font-size: medium;"><span style="font-size:
large;">Por qué Steve era distinto.</span></span></p>
<p><span style="font-size: medium;"><a href="http://www.elpais.com/
articulo/primer/plano/Steve/era/distinto/elpepueconeg/
20111009elpneglse_4/Tes">http://www.elpais.com/articulo/primer/plano/
Steve/era/distinto/elpepueconeg/20111009elpneglse_4/Tes</a></span></p>
<p><span style="font-size: medium;">Ya se ha publicado la biografía de
Steve Jobs escrita por Walter Isaacson  "<strong>Steve Jobs by Walter
Isaacson</strong>", aquí os dejamos la dirección de amazon donde
podeís adquirirla.</span></p>
<p><span style="font-size: medium;"><a>http://www.amazon.com/Steve-
Jobs-Walter-Isaacson/dp/1451648537</a></span></p>';
		$result = $this->Text->truncate($text, 500, array(
			'ellipsis' => '... ',
			'exact' => false,
			'html' => true
		));
		$expected = '<p><span style="font-size: medium;">El biógrafo de Steve Jobs, Walter
Isaacson, explica porqué Jobs le pidió que le hiciera su biografía en
este artículo de El País.</span></p>
<p><span style="font-size: medium;"><span style="font-size:
large;">Por qué Steve era distinto.</span></span></p>
<p><span style="font-size: medium;"><a href="http://www.elpais.com/
articulo/primer/plano/Steve/era/distinto/elpepueconeg/
20111009elpneglse_4/Tes">http://www.elpais.com/articulo/primer/plano/
Steve/era/distinto/elpepueconeg/20111009elpneglse_4/Tes</a></span></p>
<p><span style="font-size: medium;">Ya se ha publicado la biografía de
Steve Jobs escrita por Walter Isaacson  "<strong>Steve Jobs by Walter
Isaacson</strong>", aquí os dejamos la dirección de amazon donde
podeís adquirirla.</span></p>
<p><span style="font-size: medium;"><a>... </a></span></p>';
		$this->assertEquals($expected, $result);

		// test deprecated `ending` (`ellipsis` taking precedence if both are defined)
		$result = $this->Text->truncate($text1, 31, array(
			'ending' => '.',
			'exact' => false,
		));
		$expected = 'The quick brown fox jumps.';
		$this->assertEquals($expected, $result);

		$result = $this->Text->truncate($text1, 31, array(
			'ellipsis' => '..',
			'ending' => '.',
			'exact' => false,
		));
		$expected = 'The quick brown fox jumps..';
		$this->assertEquals($expected, $result);
	}

/**
 * testTail method
 *
 * @return void
 */
	public function testTail() {
		$text1 = 'The quick brown fox jumps over the lazy dog';
		$text2 = 'Heiz&ouml;lr&uuml;cksto&szlig;abd&auml;mpfung';
		$text3 = 'El moño está en el lugar correcto. Eso fue lo que dijo la niña, ¿habrá dicho la verdad?';
		$text4 = 'Vive la R' . chr(195) . chr(169) . 'publique de France';
		$text5 = 'НОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыь';

		$result = $this->Text->tail($text1, 13);
		$this->assertEquals('...e lazy dog', $result);

		$result = $this->Text->tail($text1, 13, array('exact' => false));
		$this->assertEquals('...lazy dog', $result);

		$result = $this->Text->tail($text1, 100);
		$this->assertEquals('The quick brown fox jumps over the lazy dog', $result);

		$result = $this->Text->tail($text2, 10);
		$this->assertEquals('...;mpfung', $result);

		$result = $this->Text->tail($text2, 10, array('exact' => false));
		$this->assertEquals('...', $result);

		$result = $this->Text->tail($text3, 255);
		$this->assertEquals($text3, $result);

		$result = $this->Text->tail($text3, 21);
		$this->assertEquals('...á dicho la verdad?', $result);

		$result = $this->Text->tail($text4, 25);
		$this->assertEquals('...a R' . chr(195) . chr(169) . 'publique de France', $result);

		$result = $this->Text->tail($text5, 10);
		$this->assertEquals('...цчшщъыь', $result);

		$result = $this->Text->tail($text5, 6, array('ellipsis' => ''));
		$this->assertEquals('чшщъыь', $result);
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

		$phrases = array('is', 'text');
		$result = $this->Text->highlight($text, $phrases, array('format' => '<b>\1</b>', 'regex' => "|\b%s\b|iu"));
		$expected = 'This <b>is</b> a test <b>text</b>';
		$this->assertEquals($expected, $result);

		$text = 'This is a test text';
		$phrases = null;
		$result = $this->Text->highlight($text, $phrases, array('format' => '<b>\1</b>'));
		$this->assertEquals($text, $result);

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
		$this->assertEquals($expected, $this->Text->highlight($text1, 'strong', $options));

		$expected = '<p><b>strong</b>bow <strong>isn&rsquo;t</strong> real cider</p>';
		$this->assertEquals($expected, $this->Text->highlight($text2, 'strong', $options));

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
		$this->assertEquals('', $result);

		$result = $this->Text->toList(array('One'));
		$this->assertEquals('One', $result);

		$result = $this->Text->toList(array('Larry', 'Curly', 'Moe'));
		$this->assertEquals('Larry, Curly and Moe', $result);

		$result = $this->Text->toList(array('Dusty', 'Lucky', 'Ned'), 'y');
		$this->assertEquals('Dusty, Lucky y Ned', $result);

		$result = $this->Text->toList(array(1 => 'Dusty', 2 => 'Lucky', 3 => 'Ned'), 'y');
		$this->assertEquals('Dusty, Lucky y Ned', $result);

		$result = $this->Text->toList(array(1 => 'Dusty', 2 => 'Lucky', 3 => 'Ned'), 'and', ' + ');
		$this->assertEquals('Dusty + Lucky and Ned', $result);

		$result = $this->Text->toList(array('name1' => 'Dusty', 'name2' => 'Lucky'));
		$this->assertEquals('Dusty and Lucky', $result);

		$result = $this->Text->toList(array('test_0' => 'banana', 'test_1' => 'apple', 'test_2' => 'lemon'));
		$this->assertEquals('banana, apple and lemon', $result);
	}

}
