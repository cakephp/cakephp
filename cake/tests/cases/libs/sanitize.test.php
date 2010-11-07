<?php
/**
 * SanitizeTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5428
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Sanitize');

/**
 * DataTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class SanitizeDataTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'SanitizeDataTest'
 * @access public
 */
	var $name = 'SanitizeDataTest';

/**
 * useTable property
 *
 * @var string 'data_tests'
 * @access public
 */
	var $useTable = 'data_tests';
}

/**
 * Article class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class SanitizeArticle extends CakeTestModel {

/**
 * name property
 *
 * @var string 'Article'
 * @access public
 */
	var $name = 'SanitizeArticle';

/**
 * useTable property
 *
 * @var string 'articles'
 * @access public
 */
	var $useTable = 'articles';
}

/**
 * SanitizeTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class SanitizeTest extends CakeTestCase {

/**
 * autoFixtures property
 *
 * @var bool false
 * @access public
 */
	var $autoFixtures = false;

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array('core.data_test', 'core.article');

/**
 * startTest method
 *
 * @param mixed $method
 * @access public
 * @return void
 */
	function startTest($method) {
		parent::startTest($method);
		$this->_initDb();
	}

/**
 * testEscapeAlphaNumeric method
 *
 * @access public
 * @return void
 */
	function testEscapeAlphaNumeric() {
		$resultAlpha = Sanitize::escape('abc', 'test_suite');
		$this->assertEqual($resultAlpha, 'abc');

		$resultNumeric = Sanitize::escape('123', 'test_suite');
		$this->assertEqual($resultNumeric, '123');

		$resultNumeric = Sanitize::escape(1234, 'test_suite');
		$this->assertEqual($resultNumeric, 1234);

		$resultNumeric = Sanitize::escape(1234.23, 'test_suite');
		$this->assertEqual($resultNumeric, 1234.23);

		$resultNumeric = Sanitize::escape('#1234.23', 'test_suite');
		$this->assertEqual($resultNumeric, '#1234.23');

		$resultNull = Sanitize::escape(null, 'test_suite');
		$this->assertEqual($resultNull, null);

		$resultNull = Sanitize::escape(false, 'test_suite');
		$this->assertEqual($resultNull, false);

		$resultNull = Sanitize::escape(true, 'test_suite');
		$this->assertEqual($resultNull, true);
	}

/**
 * testClean method
 *
 * @access public
 * @return void
 */
	function testClean() {
		$string = 'test & "quote" \'other\' ;.$ symbol.' . "\r" . 'another line';
		$expected = 'test &amp; &quot;quote&quot; &#039;other&#039; ;.$ symbol.another line';
		$result = Sanitize::clean($string, array('connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$string = 'test & "quote" \'other\' ;.$ symbol.' . "\r" . 'another line';
		$expected = 'test & ' . Sanitize::escape('"quote"', 'test_suite') . ' ' . Sanitize::escape('\'other\'', 'test_suite') . ' ;.$ symbol.another line';
		$result = Sanitize::clean($string, array('encode' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$string = 'test & "quote" \'other\' ;.$ \\$ symbol.' . "\r" . 'another line';
		$expected = 'test & "quote" \'other\' ;.$ $ symbol.another line';
		$result = Sanitize::clean($string, array('encode' => false, 'escape' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$string = 'test & "quote" \'other\' ;.$ \\$ symbol.' . "\r" . 'another line';
		$expected = 'test & "quote" \'other\' ;.$ \\$ symbol.another line';
		$result = Sanitize::clean($string, array('encode' => false, 'escape' => false, 'dollar' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$string = 'test & "quote" \'other\' ;.$ symbol.' . "\r" . 'another line';
		$expected = 'test & "quote" \'other\' ;.$ symbol.' . "\r" . 'another line';
		$result = Sanitize::clean($string, array('encode' => false, 'escape' => false, 'carriage' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$array = array(array('test & "quote" \'other\' ;.$ symbol.' . "\r" . 'another line'));
		$expected = array(array('test &amp; &quot;quote&quot; &#039;other&#039; ;.$ symbol.another line'));
		$result = Sanitize::clean($array, array('connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$array = array(array('test & "quote" \'other\' ;.$ \\$ symbol.' . "\r" . 'another line'));
		$expected = array(array('test & "quote" \'other\' ;.$ $ symbol.another line'));
		$result = Sanitize::clean($array, array('encode' => false, 'escape' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$array = array(array('test odd Ä spacesé'));
		$expected = array(array('test odd &Auml; spaces&eacute;'));
		$result = Sanitize::clean($array, array('odd_spaces' => false, 'escape' => false, 'connection' => 'test_suite'));
		$this->assertEqual($result, $expected);

		$array = array(array('\\$', array('key' => 'test & "quote" \'other\' ;.$ \\$ symbol.' . "\r" . 'another line')));
		$expected = array(array('$', array('key' => 'test & "quote" \'other\' ;.$ $ symbol.another line')));
		$result = Sanitize::clean($array, array('encode' => false, 'escape' => false));
		$this->assertEqual($result, $expected);

		$string = '';
		$expected = '';
		$result = Sanitize::clean($string);
		$this->assertEqual($string, $expected);

		$data = array(
			'Grant' => array(
				'title' => '2 o clock grant',
				'grant_peer_review_id' => 3,
				'institution_id' => 5,
				'created_by' => 1,
				'modified_by' => 1,
				'created' => '2010-07-15 14:11:00',
				'modified' => '2010-07-19 10:45:41'
			),
			'GrantsMember' => array(
				0 => array(
					'id' => 68,
					'grant_id' => 120,
					'member_id' => 16,
					'program_id' => 29,
					'pi_percent_commitment' => 1
				)
			)
		);
		$result = Sanitize::clean($data);
		$this->assertEqual($result, $data);
	}

/**
 * testHtml method
 *
 * @access public
 * @return void
 */
	function testHtml() {
		$string = '<p>This is a <em>test string</em> & so is this</p>';
		$expected = 'This is a test string &amp; so is this';
		$result = Sanitize::html($string, array('remove' => true));
		$this->assertEqual($result, $expected);

		$string = 'The "lazy" dog \'jumped\' & flew over the moon. If (1+1) = 2 <em>is</em> true, (2-1) = 1 is also true';
		$expected = 'The &quot;lazy&quot; dog &#039;jumped&#039; &amp; flew over the moon. If (1+1) = 2 &lt;em&gt;is&lt;/em&gt; true, (2-1) = 1 is also true';
		$result = Sanitize::html($string);
		$this->assertEqual($result, $expected);
		
		$string = 'The "lazy" dog \'jumped\'';
		$expected = 'The &quot;lazy&quot; dog \'jumped\'';
		$result = Sanitize::html($string, array('quotes' => ENT_COMPAT));
		$this->assertEqual($result, $expected);
		
		$string = 'The "lazy" dog \'jumped\'';
		$result = Sanitize::html($string, array('quotes' => ENT_NOQUOTES));
		$this->assertEqual($result, $string);
		
		$string = 'The "lazy" dog \'jumped\' & flew over the moon. If (1+1) = 2 <em>is</em> true, (2-1) = 1 is also true';
		$expected = 'The &quot;lazy&quot; dog &#039;jumped&#039; &amp; flew over the moon. If (1+1) = 2 &lt;em&gt;is&lt;/em&gt; true, (2-1) = 1 is also true';
		$result = Sanitize::html($string);
		$this->assertEqual($result, $expected);
	}

/**
 * testStripWhitespace method
 *
 * @access public
 * @return void
 */
	function testStripWhitespace() {
		$string = "This     sentence \t\t\t has lots of \n\n white\nspace \rthat \r\n needs to be    \t    \n trimmed.";
		$expected = "This sentence has lots of whitespace that needs to be trimmed.";
		$result = Sanitize::stripWhitespace($string);
		$this->assertEqual($result, $expected);
	}

/**
 * testParanoid method
 *
 * @access public
 * @return void
 */
	function testParanoid() {
		$string = 'I would like to !%@#% & dance & sing ^$&*()-+';
		$expected = 'Iwouldliketodancesing';
		$result = Sanitize::paranoid($string);
		$this->assertEqual($result, $expected);

		$string = array('This |s th% s0ng that never ends it g*es',
						'on and on my friends, b^ca#use it is the',
						'so&g th===t never ends.');
		$expected = array('This s th% s0ng that never ends it g*es',
						'on and on my friends bcause it is the',
						'sog tht never ends.');
		$result = Sanitize::paranoid($string, array('%', '*', '.', ' '));
		$this->assertEqual($result, $expected);

		$string = "anything' OR 1 = 1";
		$expected = 'anythingOR11';
		$result = Sanitize::paranoid($string);
		$this->assertEqual($result, $expected);

		$string = "x' AND email IS NULL; --";
		$expected = 'xANDemailISNULL';
		$result = Sanitize::paranoid($string);
		$this->assertEqual($result, $expected);

		$string = "x' AND 1=(SELECT COUNT(*) FROM users); --";
		$expected = "xAND1SELECTCOUNTFROMusers";
		$result = Sanitize::paranoid($string);
		$this->assertEqual($result, $expected);

		$string = "x'; DROP TABLE members; --";
		$expected = "xDROPTABLEmembers";
		$result = Sanitize::paranoid($string);
		$this->assertEqual($result, $expected);
	}

/**
 * testStripImages method
 *
 * @access public
 * @return void
 */
	function testStripImages() {
		$string = '<img src="/img/test.jpg" alt="my image" />';
		$expected = 'my image<br />';
		$result = Sanitize::stripImages($string);
		$this->assertEqual($result, $expected);

		$string = '<img src="javascript:alert(\'XSS\');" />';
		$expected = '';
		$result = Sanitize::stripImages($string);
		$this->assertEqual($result, $expected);

		$string = '<a href="http://www.badsite.com/phising"><img src="/img/test.jpg" alt="test image alt" title="test image title" id="myImage" class="image-left"/></a>';
		$expected = '<a href="http://www.badsite.com/phising">test image alt</a><br />';
		$result = Sanitize::stripImages($string);
		$this->assertEqual($result, $expected);

		$string = '<a onclick="medium()" href="http://example.com"><img src="foobar.png" onclick="evilFunction(); return false;"/></a>';
		$expected = '<a onclick="medium()" href="http://example.com"></a>';
		$result = Sanitize::stripImages($string);
		$this->assertEqual($result, $expected);
	}

/**
 * testStripScripts method
 *
 * @access public
 * @return void
 */
	function testStripScripts() {
		$string = '<link href="/css/styles.css" media="screen" rel="stylesheet" />';
		$expected = '';
		$result = Sanitize::stripScripts($string);
		$this->assertEqual($result, $expected);

		$string = '<link href="/css/styles.css" media="screen" rel="stylesheet" />' . "\n" . '<link rel="icon" href="/favicon.ico" type="image/x-icon" />' . "\n" . '<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />' . "\n" . '<link rel="alternate" href="/feed.xml" title="RSS Feed" type="application/rss+xml" />';
		$expected = "\n" . '<link rel="icon" href="/favicon.ico" type="image/x-icon" />' . "\n" . '<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />'."\n".'<link rel="alternate" href="/feed.xml" title="RSS Feed" type="application/rss+xml" />';
		$result = Sanitize::stripScripts($string);
		$this->assertEqual($result, $expected);

		$string = '<script type="text/javascript"> alert("hacked!");</script>';
		$expected = '';
		$result = Sanitize::stripScripts($string);
		$this->assertEqual($result, $expected);

		$string = '<script> alert("hacked!");</script>';
		$expected = '';
		$result = Sanitize::stripScripts($string);
		$this->assertEqual($result, $expected);

		$string = '<style>#content { display:none; }</style>';
		$expected = '';
		$result = Sanitize::stripScripts($string);
		$this->assertEqual($result, $expected);

		$string = '<style type="text/css"><!-- #content { display:none; } --></style>';
		$expected = '';
		$result = Sanitize::stripScripts($string);
		$this->assertEqual($result, $expected);

		$string = <<<HTML
text
<style type="text/css">
<!-- 
#content { display:none; } 
-->
</style>
text
HTML;
		$expected = "text\n\ntext";
		$result = Sanitize::stripScripts($string);
		$this->assertEqual($result, $expected);

		$string = <<<HTML
text
<script type="text/javascript">
<!-- 
alert('wooo');
-->
</script>
text
HTML;
		$expected = "text\n\ntext";
		$result = Sanitize::stripScripts($string);
		$this->assertEqual($result, $expected);
	}

/**
 * testStripAll method
 *
 * @access public
 * @return void
 */
	function testStripAll() {
		$string = '<img """><script>alert("xss")</script>"/>';
		$expected ='"/>';
		$result = Sanitize::stripAll($string);
		$this->assertEqual($result, $expected);

		$string = '<IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041>';
		$expected = '';
		$result = Sanitize::stripAll($string);
		$this->assertEqual($result, $expected);

		$string = '<<script>alert("XSS");//<</script>';
		$expected = '<';
		$result = Sanitize::stripAll($string);
		$this->assertEqual($result, $expected);

		$string = '<img src="http://google.com/images/logo.gif" onload="window.location=\'http://sam.com/\'" />'."\n".
					"<p>This is ok      \t\n   text</p>\n".
					'<link rel="stylesheet" href="/css/master.css" type="text/css" media="screen" title="my sheet" charset="utf-8">'."\n".
					'<script src="xss.js" type="text/javascript" charset="utf-8"></script>';
		$expected = '<p>This is ok text</p>';
		$result = Sanitize::stripAll($string);
		$this->assertEqual($result, $expected);

	}

/**
 * testStripTags method
 *
 * @access public
 * @return void
 */
	function testStripTags() {
		$string = '<h2>Headline</h2><p><a href="http://example.com">My Link</a> could go to a bad site</p>';
		$expected = 'Headline<p>My Link could go to a bad site</p>';
		$result = Sanitize::stripTags($string, 'h2', 'a');
		$this->assertEqual($result, $expected);

		$string = '<script type="text/javascript" src="http://evildomain.com"> </script>';
		$expected = ' ';
		$result = Sanitize::stripTags($string, 'script');
		$this->assertEqual($result, $expected);

		$string = '<h2>Important</h2><p>Additional information here <a href="/about"><img src="/img/test.png" /></a>. Read even more here</p>';
		$expected = 'Important<p>Additional information here <img src="/img/test.png" />. Read even more here</p>';
		$result = Sanitize::stripTags($string, 'h2', 'a');
		$this->assertEqual($result, $expected);

		$string = '<h2>Important</h2><p>Additional information here <a href="/about"><img src="/img/test.png" /></a>. Read even more here</p>';
		$expected = 'Important<p>Additional information here . Read even more here</p>';
		$result = Sanitize::stripTags($string, 'h2', 'a', 'img');
		$this->assertEqual($result, $expected);

		$string = '<b>Important message!</b><br>This message will self destruct!';
		$expected = 'Important message!<br>This message will self destruct!';
		$result = Sanitize::stripTags($string, 'b');
		$this->assertEqual($result, $expected);

		$string = '<b>Important message!</b><br />This message will self destruct!';
		$expected = 'Important message!<br />This message will self destruct!';
		$result = Sanitize::stripTags($string, 'b');
		$this->assertEqual($result, $expected);

		$string = '<h2 onclick="alert(\'evil\'); onmouseover="badness()">Important</h2><p>Additional information here <a href="/about"><img src="/img/test.png" /></a>. Read even more here</p>';
		$expected = 'Important<p>Additional information here . Read even more here</p>';
		$result = Sanitize::stripTags($string, 'h2', 'a', 'img');
		$this->assertEqual($result, $expected);
	}

/**
 * testFormatColumns method
 *
 * @access public
 * @return void
 */
	function testFormatColumns() {
		$this->loadFixtures('DataTest', 'Article');

		$this->DataTest =& new SanitizeDataTest(array('alias' => 'DataTest'));
		$data = array('DataTest' => array(
						'id' => 'z',
						'count' => '12a',
						'float' => '2.31456',
						'updated' => '2008-01-01'
						)
					);
		$this->DataTest->set($data);
		$expected = array('DataTest' => array(
			'id' => '0',
			'count' => '12',
			'float' => 2.31456,
			'updated' => '2008-01-01 00:00:00',
		));
		Sanitize::formatColumns($this->DataTest);
		$result = $this->DataTest->data;
		$this->assertEqual($result, $expected);

		$this->Article =& new SanitizeArticle(array('alias' => 'Article'));
		$data = array('Article' => array(
			'id' => 'ZB',
			'user_id' => '12',
			'title' => 'title of article',
			'body' => 'body text',
			'published' => 'QQQQQQQ',
		));
		$this->Article->set($data);
		$expected = array('Article' => array(
			'id' => '0',
			'user_id' => '12',
			'title' => 'title of article',
			'body' => 'body text',
			'published' => 'QQQQQQQ',
		));
		Sanitize::formatColumns($this->Article);
		$result = $this->Article->data;
		$this->assertEqual($result, $expected);
	}
}
