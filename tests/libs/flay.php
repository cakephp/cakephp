<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * 
 * 
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v 0.2.9
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

/**
 * 
 */
uses ('flay');
/**
 * Enter description here...
 *
 * @package cake
 * @subpackage cake.tests.libs
 * @since CakePHP v .9
 *
 */
class FlayTest extends UnitTestCase
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $flay;

/**
 * Enter description here...
 *
 * @return FlayTest
 */
   function FlayTest()
   {
      $this->UnitTestCase('Flay test');
   }

/**
 * Enter description here...
 *
 */
   function setUp()
   {
      $this->flay = new Flay ();
   }

/**
 * Enter description here...
 *
 */
   function tearDown()
   {
      unset($this->flay);
   }


/**
 * Enter description here...
 *
 */
   function testToHtml()
   {
      $tests_to_html = array(
      array(
      'text'=>"",
      'html'=>""
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

      foreach ($tests_to_html as $test)
      {
         $this->assertEqual($this->flay->toHtml($test['text']), $test['html']);
      }
   }
}


?>