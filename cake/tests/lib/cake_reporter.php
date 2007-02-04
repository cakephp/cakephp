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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
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
class CakeHtmlReporter extends HtmlReporter {
/**
 *    Does nothing yet. The first output will
 *    be sent on the first test start. For use
 *    by a web browser.
 *    @access public
 */
	function CakeHtmlReporter($characterSet = 'ISO-8859-1') {
		parent::HtmlReporter($characterSet);
	}
/**
 *    Paints the top of the web page setting the
 *    title to the name of the starting test.
 *    @param string $test_name      Name class of test.
 *    @access public
 */
	function paintHeader($testName) {
		$this->sendNoCacheHeaders();
		$baseUrl = BASE_URL;
		print "<h2>$testName</h2>\n";
		flush();
	}
/**
 * Paints the end of the test with a summary of
 * the passes and failures.
 *  @param string $test_name Name class of test.
 * @access public
 *
 */
	function paintFooter($testName) {
    	$colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? "red" : "green");
    	print "<div style=\"";
    	print "padding: 8px; margin-top: 1em; background-color: $colour; color: white;";
    	print "\">";
    	print $this->getTestCaseProgress() . "/" . $this->getTestCaseCount();
    	print " test cases complete:\n";
    	print "<strong>" . $this->getPassCount() . "</strong> passes, ";
    	print "<strong>" . $this->getFailCount() . "</strong> fails and ";
    	print "<strong>" . $this->getExceptionCount() . "</strong> exceptions.";
    	print "</div>\n";
	}
}
?>