<?php

uses ('test', 'flay');

class FlayTest extends TestCase {
	var $abc;

	// constructor of the test suite
	function FlayTest($name) {
		$this->TestCase($name);
	}

	// called before the test functions will be executed
	// this function is defined in PHPUnit_TestCase and overwritten
	// here
	function setUp() {
		$this->abc = new Flay ();
	}

	// called after the test functions are executed
   // this function is defined in PHPUnit_TestCase and overwritten
   // here
   function tearDown() {
        unset($this->abc);
   }


	function testToHtml () {
		$tests_to_html = array(
			array(
			'text'=>"",
			'html'=>false
			),
			array(
			'text'=>"This is a text.",
			'html'=>"<p>This is a text.</p>\n"
			),
			array(
			'text'=>"This is a line.\n\n\nThis is\n another one.\n\n",
			'html'=>"<p>This is a line.</p>\n<p>This is<br />\n another one.</p>\n"
			),
			array(
			'text'=>"This line has *bold*, _italic_, and a _combo *bold* and italic_ texts.",
			'html'=>"<p>This line has <strong>bold</strong>, <em>italic</em>, and a <em>combo <strong>bold</strong> and italic</em> texts.</p>\n"
			),
			array(
			'text'=>"This line has <b>tags</b> which are <br />not allowed.",
			'html'=>"<p>This line has &lt;b&gt;tags&lt;/b&gt; which are &lt;br /&gt;not allowed.</p>\n",
			),
			array(
			'text'=>"[http://sputnik.pl] is a link, but so is [http://sputnik.pl/bla/ this one], and [this is not.",
			'html'=>"<p><a href=\"http://sputnik.pl\" target=\"_blank\">http://sputnik.pl</a> is a link, but so is <a href=\"http://sputnik.pl/bla/\" target=\"_blank\">this one</a>, and [this is not.</p>\n"
			),
			array(
			'text'=>"Why don't we try to insert an image.\n\n[http://sputnik.pl/test.jpg]",
			'html'=>"<p>Why don't we try to insert an image.</p>\n<p><img src=\"http://sputnik.pl/test.jpg\" alt=\"\" /></p>\n"
			),
			array(
			'text'=>"Auto-link my.name+real@my-server.com and example@example.com, should work.",
			'html'=>"<p>Auto-link <a href=\"mailto:my.name+real@my-server.com\">my.name+real@my-server.com</a> and <a href=\"mailto:example@example.com\">example@example.com</a>, should work.</p>\n"
			),
			array(
			'text'=>"\"\"\"This is a blockquote\"\"\"",
			'html'=>"<blockquote>\n<p>This is a blockquote</p>\n</blockquote>\n"
			),
			array(
			'text'=>"How about a blockquote?\"\"\"This is a multiline blockquote.\n\nThis is the second line.\"\"\"\nAnd this is not.",
			'html'=>"<p>How about a blockquote?</p>\n<blockquote>\n<p>This is a multiline blockquote.</p>\n<p>This is the second line.</p>\n</blockquote>\n<p>And this is not.</p>\n"
			),
			array(
			'text'=>"Now auto-link an url such as http://sputnik.pl or www.robocik-malowany.com/dupa[4] - or any other.",
			'html'=>"<p>Now auto-link an url such as <a href=\"http://sputnik.pl\">http://sputnik.pl</a> or <a href=\"http://www.robocik-malowany.com/dupa[4]\">www.robocik-malowany.com/dupa[4]</a> &ndash; or any other.</p>\n"
			),
			array(
			'text'=>"===This be centered===",
			'html'=>"<center>\n<p>This be centered</p>\n</center>\n"
			),
			array(
			'text'=>"===This be centered.\n\nAnd this be centered too,\nalong with this.===\nThis, alas, be not.",
			'html'=>"<center>\n<p>This be centered.</p>\n<p>And this be centered too,<br />\nalong with this.</p>\n</center>\n<p>This, alas, be not.</p>\n"
			),
			array(
			'text'=>"This tests (C)2004 Someone Else, \"Layer Your Apps(R)\" and Cake(TM).",
			'html'=>"<p>This tests &copy;2004 Someone Else, \"Layer Your Apps&reg;\" and Cake&trade;.</p>\n"
			),
		);

		foreach ($tests_to_html as $test) {
			$this->assertEquals($this->abc->toHtml($test['text']), $test['html']);
		}

	}
}


?>