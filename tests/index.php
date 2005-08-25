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
 *
 * Author(s): Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Test Suite Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Test Suite Authors/Developers 
 * @link         https://trac.cakephp.org/wiki/Cake/Manual/TestSuite/Authors Authors/Developers
 * @package      test_suite
 * @subpackage   test_suite.tests_1_x
 * @since        CakePHP Test Suite v 1.0.0.0
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

error_reporting(E_ALL);
set_time_limit(600);
ini_set('memory_limit','128M');

/**
 * Get root directory
 */
    if (!defined('DS'))
    {
        define('DS', DIRECTORY_SEPARATOR);
    }
    if (!defined('ROOT'))
    {
        define('ROOT', dirname(dirname(dirname(__FILE__))).DS);
    }
    
    require_once ROOT . 'config/paths.php';
    require_once TESTS . 'test_paths.php';
    require_once TESTS . 'suite_libs'.DS.'test_manager.php';
    require_once SIMPLE_TEST . 'unit_tester.php';
    require_once SIMPLE_TEST . 'web_tester.php';
    require_once SIMPLE_TEST . 'mock_objects.php';
    
    function CakePHPTestHeader()
    {
        $header = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta http-equiv='Content-Type'content='text/html; charset=iso-8859-1' />
            <title>CakePHP Test Suite v 1.0.0.0</title>
            <link rel="stylesheet" type="text/css" href="/css/default.css" />
        </head>
        <body>

EOF;
echo $header;
    }
    
    function CakePHPTestSuiteHeader()
    {  
        $groups = class_exists('Object') ? 'groups' : $_SERVER['PHP_SELF'].'?show=groups';
        $cases = class_exists('Object') ? 'cases' : $_SERVER['PHP_SELF'].'?show=cases';
        
        $suiteHeader = <<<EOD
<div id="main">
	<div id="header">
		<div id="headerLogo"><img src="/img/logo.png" alt="" /></div>
		<div id="headerNav">
		<h2>Test Suite v 1.0.0.0</h2>
		</div>
	</div>
	<p><a href='$groups'>Test Groups</a>  ||   <a href='$cases'>Test Cases</a></p>
EOD;
echo $suiteHeader;
    }
    function CakePHPTestSuiteFooter()
    {
        $footer = <<<EOD
        </body>
    </html>
EOD;
echo $footer;
    }

CakePHPTestHeader();
CakePHPTestSuiteHeader();

    if (isset($_GET['cases']))
    {
        CakePHPTestCaseList();
    }
    elseif (isset($_GET['groups']))
    {
        CakePHPTestGroupTestList();
    }
CakePHPTestSuiteFooter();
?>