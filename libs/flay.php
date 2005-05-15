<?PHP 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <http://sputnik.pl/cake>                                    + //
// + Copyright: (c) 2005 Michal Tatarynowicz                          + //
// +                                                                  + //
// + Author(s): (c) 2005 Michal Tatarynowicz <tatarynowicz@gmail.com> + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under the Public Domain Licence                         + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Flay
  * Text-to-html parser, similar to Textile or RedCloth, only with somehow different syntax. 
  * See Flay::test() for examples.
  * Test with $flay = new Flay(); $flay->test();
  *
  * @filesource 
  * @modifiedby $LastChangedBy$  
  * @lastmodified $Date$
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @copyright Copyright (c) 2005, Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @license Public_Domain
  *
  */

/**
  * Enter description here...
  *
  */
uses('object');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Flay extends Object {
/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $text = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $allow_html = false;

/**
  * Enter description here...
  *
  * @param unknown_type $text
  */
    function __construct ($text=null) {
        $this->text = $text;
        parent::__construct();
    }

/**
  * Enter description here...
  *
  * @param unknown_type $text
  * @return unknown
  */
    function to_html ($text=null) {
        // trim whitespace and disable all HTML
        $text = str_replace('<', '&lt;', str_replace('>', '&gt;', trim($text? $text: $this->text)));

        // multi-paragraph functions
        $text = preg_replace('#(?:[\n]{0,2})"""(.*)"""(?:[\n]{0,2})#s', "\n\n%BLOCKQUOTE%\n\n\\1\n\n%ENDBLOCKQUOTE%\n\n", $text);
        $text = preg_replace('#(?:[\n]{0,2})===(.*)===(?:[\n]{0,2})#s', "\n\n%CENTER%\n\n\\1\n\n%ENDCENTER%\n\n", $text);

        // pre-parse newlines
        $text = preg_replace("#\r\n#", "\n", $text);
        $text = preg_replace("#[\n]{2,}#", "%PARAGRAPH%", $text);
        $text = preg_replace('#[\n]{1}#', "%LINEBREAK%", $text);

        // split into paragraphs and parse
        $out = '';
        foreach (split('%PARAGRAPH%', $text) as $line) {

            if ($line) {

                // pre-parse links
                $links = array();
                $regs = null;
                if (preg_match_all('#\[([^\[]{4,})\]#', $line, $regs)) {
                    foreach ($regs[1] as $reg) {
                        $links[] = $reg;
                        $line = str_replace("[{$reg}]",'%LINK'.(count($links)-1).'%', $line);
                    }
                }

                // MAIN TEXT FUNCTIONS
                // bold
                $line = ereg_replace("\*([^\*]*)\*", "<strong>\\1</strong>", $line);
                // italic
                $line = ereg_replace("_([^_]*)_", "<em>\\1</em>", $line);
                // entities
                $line = str_replace(' - ', ' &ndash; ', $line);
                $line = str_replace(' -- ', ' &mdash; ', $line);
                $line = str_replace('(C)', '&copy;', $line);
                $line = str_replace('(R)', '&reg;', $line);
                $line = str_replace('(TM)', '&trade;', $line);

                // guess e-mails
                $emails = null;
                if (preg_match_all("#([_A-Za-z0-9+-+]+(?:\.[_A-Za-z0-9+-]+)*@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*)#", $line, $emails)) {
                    foreach ($emails[1] as $email) {
                        $line = str_replace($email, "<a href=\"mailto:{$email}\">{$email}</a>", $line);
                    }
                }
                // guess links
                $urls = null;
                if (preg_match_all("#((?:http|https|ftp|nntp)://[^ ]+)#", $line, $urls)) {
                    foreach ($urls[1] as $url) {
                        $line = str_replace($url, "<a href=\"{$url}\">{$url}</a>", $line);
                    }
                }
                if (preg_match_all("#(www\.[^ ]+)#", $line, $urls)) {
                    foreach ($urls[1] as $url) {
                        $line = str_replace($url, "<a href=\"{$url}\">{$url}</a>", $line);
                    }
                }


                // re-parse links
                if (count($links)) {
                    for ($ii=0; $ii<count($links); $ii++) {

                        if (preg_match('#\.(jpg|jpeg|gif|png)$#', $links[$ii]))
                        $with = "<img src=\"{$links[$ii]}\" alt=\"\" />";
                        elseif (preg_match('#^([^\]\ ]+)(?: ([^\]]+))?$#', $links[$ii], $regs))
                        $with = "<a href=\"{$regs[1]}\" target=\"_blank\">".(isset($regs[2])? $regs[2]: $regs[1])."</a>";
                        else
                        $with = $links[$ii];

                        $line = str_replace("%LINK{$ii}%", $with, $line);
                    }
                }

                // re-parse newlines
                $out .= str_replace('%LINEBREAK%', "<br />\n", "<p>{$line}</p>\n");
            }
        }

        // re-parse multilines
        $out = str_replace('<p>%BLOCKQUOTE%</p>', "<blockquote>", $out);
        $out = str_replace('<p>%ENDBLOCKQUOTE%</p>', "</blockquote>", $out);
        $out = str_replace('<p>%CENTER%</p>', "<center>", $out);
        $out = str_replace('<p>%ENDCENTER%</p>', "</center>", $out);

        return $out;
    }

/**
  * Enter description here...
  *
  * @param unknown_type $verbose
  * @return unknown
  */
    function test ($verbose=false) {
        $errors = $tests = 0;

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
        'html'=>"<p>Now auto-link an url such as <a href=\"http://sputnik.pl\">http://sputnik.pl</a> or <a href=\"www.robocik-malowany.com/dupa[4]\">www.robocik-malowany.com/dupa[4]</a> &ndash; or any other.</p>\n"
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
            $this->text = $test['text'];
            if ($test['html'] != ($o = $this->to_html())) {
                debug ("Flay test error:\n  Provided: {$test['text']}\n  Expected: {$test['html']}\n  Received: {$o}", 1);
                $errors++;
            }
            elseif ($verbose) {
                debug ("Flay test ok:\n  Provided: {$test['text']}\n  Received: {$o}", 1);
            }

            $tests++;
        }

        debug ("<b>Flay: {$tests} tests, {$errors} errors (".($errors?'FAILED':'PASSED').')</b>');

        return !$errors;
    }
}

?>