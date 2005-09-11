<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 * 
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP Test Suite <https://trac.cakephp.org/wiki/Cake/Manual/TestSuite/>
 * Copyright (c) 2005, CakePHP Test Suite Authors/Developers
 * Author(s): Larry E. Masters aka PhpNut <phpnut@gmail.com>
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Test Suite Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Test Suite Authors/Developers 
 * @link         https://trac.cakephp.org/wiki/TestSuite/Authors/ Authors/Developers
 * @package      tests
 * @subpackage   tests.libs
 * @since        CakePHP Test Suite v 1.0.0.0
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
 
/**
 * Short description for class.
 * 
 * @package    tests
 * @subpackage tests.libs
 * @since      CakePHP Test Suite v 1.0.0.0
 */
class CakeHtmlReporter extends HtmlReporter
{

        
/**
 *    Does nothing yet. The first output will
 *    be sent on the first test start. For use
 *    by a web browser.
 *    @access public
 */
    function CakeHtmlReporter($character_set = 'ISO-8859-1')
    {
        parent::HtmlReporter($character_set);
    }
    
/**
 *    Paints the top of the web page setting the
 *    title to the name of the starting test.
 *    @param string $test_name      Name class of test.
 *    @access public
 */
    function paintHeader($test_name)
    {
        $this->sendNoCacheHeaders();
        print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
        print "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
        print "<head>\n";
        print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" .
                $this->_character_set . "\">\n";
        print "<title>CakePHP Test Suite v 1.0.0.0 :: $test_name</title>\n";
        print "<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/default.css\" />\n";
        print "<style type=\"text/css\">\n";
        print $this->_getCss() . "\n";
        print "</style>\n";
        print "</head>\n<body>\n";
        print "<div id=\"main\">\n";
        print "<div id=\"header\">\n";
        print "<div id=\"headerLogo\"><img src=\"/img/logo.png\" alt=\"\" /></div>\n";
        print "<div id=\"headerNav\">\n";
        print "<h2>Test Suite v 1.0.0.0</h2>\n";
        print "</div>\n";
        print "</div>\n";
        print "<h2>$test_name</h2>\n";
       
       flush();
    }
}

?>