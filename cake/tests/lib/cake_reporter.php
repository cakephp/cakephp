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
 * @package			cake
 * @subpackage		cake.cake.tests.libs
 * @since			CakePHP(tm) v 1.2.0.4433
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * Short description for class.
 *
 * @package    cake
 * @subpackage cake.cake.tests.lib
 */
class CakeHtmlReporter extends SimpleReporter {
    var $_character_set;
	var $_show_passes = false;

    /**
     *    Does nothing yet. The first output will
     *    be sent on the first test start. For use
     *    by a web browser.
     *    @access public
     */
    function CakeHtmlReporter($character_set = 'ISO-8859-1') {
		if (isset($_GET['show_passes']) && $_GET['show_passes']) {
			$this->_show_passes = true;
		}
        $this->SimpleReporter();
        $this->_character_set = $character_set;
    }

    /**
     *    Paints the top of the web page setting the
     *    title to the name of the starting test.
     *    @param string $test_name      Name class of test.
     *    @access public
     */
	function paintHeader($testName) {
		$this->sendNoCacheHeaders();
		$baseUrl = BASE;
		print "<h2>$testName</h2>\n";
		print "<ul class='tests'>\n";
		flush();
	}

    /**
     *    Send the headers necessary to ensure the page is
     *    reloaded on every request. Otherwise you could be
     *    scratching your head over out of date test data.
     *    @access public
     *    @static
     */
    function sendNoCacheHeaders() {
        if (! headers_sent()) {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
        }
    }

    /**
     *    Paints the end of the test with a summary of
     *    the passes and failures.
     *    @param string $test_name        Name class of test.
     *    @access public
     */
    function paintFooter($test_name) {
        $colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? "red" : "green");
		print "</ul>\n";
        print "<div style=\"";
        print "padding: 8px; margin: 1em 0; background-color: $colour; color: white;";
        print "\">";
        print $this->getTestCaseProgress() . "/" . $this->getTestCaseCount();
        print " test cases complete:\n";
        print "<strong>" . $this->getPassCount() . "</strong> passes, ";
        print "<strong>" . $this->getFailCount() . "</strong> fails and ";
        print "<strong>" . $this->getExceptionCount() . "</strong> exceptions.";
        print "</div>\n";
        print "</body>\n</html>\n";
    }

    /**
     *    Paints the test failure with a breadcrumbs
     *    trail of the nesting test suites below the
     *    top level test.
     *    @param string $message    Failure message displayed in
     *                              the context of the other tests.
     *    @access public
     */
    function paintFail($message) {
        parent::paintFail($message);
		print "<li class='fail'>\n";
        print "<span>Failed</span>";
		print "<div class='msg'>" . $this->_htmlEntities($message) . "</div>\n";
		$breadcrumb = Set::filter($this->getTestList());
		array_shift($breadcrumb);
        print "<div>" . implode(" -&gt; ", $breadcrumb) . "</div>\n";
		print "</li>\n";
    }

    /**
     *    Paints the test pass with a breadcrumbs
     *    trail of the nesting test suites below the
     *    top level test.
     *    @param string $message    Pass message displayed in
     *                              the context of the other tests.
     *    @access public
     */
    function paintPass($message) {
        parent::paintPass($message);
		if ($this->_show_passes) {
			print "<li class='pass'>\n";
	        print "<span>Passed</span> ";
			$breadcrumb = Set::filter($this->getTestList());
			array_shift($breadcrumb);
	        print implode(" -&gt; ", $breadcrumb);
			print "<br />" . $this->_htmlEntities($message) . "\n";
			print "</li>\n";
		}
    }

    /**
     *    Paints a PHP error.
     *    @param string $message        Message is ignored.
     *    @access public
     */
    function paintError($message) {
        parent::paintError($message);
		print "<li class='fail'>\n";
        print "<span>Error</span>";
		print "<div class='msg'>" . $this->_htmlEntities($message) . "</div>\n";
		$breadcrumb = Set::filter($this->getTestList());
		array_shift($breadcrumb);
        print "<div>" . implode(" -&gt; ", $breadcrumb) . "</div>\n";
		print "</li>\n";
    }

    /**
     *    Paints a PHP exception.
     *    @param Exception $exception        Exception to display.
     *    @access public
     */
    function paintException($exception) {
        parent::paintException($exception);
		print "<li class='fail'>\n";
        print "<span>Exception</span>";
        $message = 'Unexpected exception of type [' . get_class($exception) .
                '] with message ['. $exception->getMessage() .
                '] in ['. $exception->getFile() .
                ' line ' . $exception->getLine() . ']';
		print "<div class='msg'>" . $this->_htmlEntities($message) . "</div>\n";
		$breadcrumb = Set::filter($this->getTestList());
		array_shift($breadcrumb);
        print "<div>" . implode(" -&gt; ", $breadcrumb) . "</div>\n";
		print "</li>\n";
    }
		
	/**
	 *    Prints the message for skipping tests.
     *    @param string $message    Text of skip condition.
     *    @access public
     */
	function paintSkip($message) {
        parent::paintSkip($message);
		print "<li class='skipped'>\n";
        print "<span>Skipped</span> ";
        print $this->_htmlEntities($message);
		print "</li>\n";
	}

    /**
     *    Paints formatted text such as dumped variables.
     *    @param string $message        Text to show.
     *    @access public
     */
    function paintFormattedMessage($message) {
        print '<pre>' . $this->_htmlEntities($message) . '</pre>';
    }

    /**
     *    Character set adjusted entity conversion.
     *    @param string $message    Plain text or Unicode message.
     *    @return string            Browser readable message.
     *    @access protected
     */
    function _htmlEntities($message) {
        return htmlentities($message, ENT_COMPAT, $this->_character_set);
    }
	
}

?>