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
 * Portions modified from WACT Test Suite
 * Author(s): Harry Fuecks
 *            Jon Ramsey
 *            Jason E. Sweat
 *            Franco Ponticelli
 *            Lorenzo Alberton
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Test Suite Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Test Suite Authors/Developers 
 * @link         https://trac.cakephp.org/wiki/Cake/Manual/TestSuite/Authors Authors/Developers
 * @package      tests
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
    
    require_once ROOT . 'config'.DS.'paths.php';
    require_once TESTS . 'test_paths.php';
    require_once TESTS . 'lib'.DS.'test_manager.php';
    require_once SIMPLE_TEST . 'reporter.php';

    if (!isset(  $_SERVER['SERVER_NAME'] ))
    {
        $_SERVER['SERVER_NAME'] = '';
    }
    
    if (empty( $_GET['output']))
    {
        TestManager::setOutputFromIni(TESTS . 'config.ini.php');
        $_GET['output'] = TEST_OUTPUT;
    }
    
/**
 *
 * Used to determine output to display
 */
define('CAKE_TEST_OUTPUT_HTML',1);
define('CAKE_TEST_OUTPUT_XML',2);
define('CAKE_TEST_OUTPUT_TEXT',3);

    if ( isset($_GET['output']) && $_GET['output'] == 'xml' )
    {
        define('CAKE_TEST_OUTPUT', CAKE_TEST_OUTPUT_XML);
    }
    elseif  ( isset($_GET['output']) && $_GET['output'] == 'html' )
    {
        define('CAKE_TEST_OUTPUT', CAKE_TEST_OUTPUT_HTML);
    }
    else
    {
        define('CAKE_TEST_OUTPUT', CAKE_TEST_OUTPUT_TEXT);
    }
    
    function & CakeTestsGetReporter()
    {
        static $Reporter = NULL;
        if ( !$Reporter )
        {
            switch ( CAKE_TEST_OUTPUT )
            {
                case CAKE_TEST_OUTPUT_XML:
                    require_once SIMPLE_TEST . 'xml.php';
                    $Reporter = new XmlReporter();
                break;
                
                case CAKE_TEST_OUTPUT_HTML:
                    require_once TESTS . 'lib'.DS.'cake_reporter.php';
                    $Reporter = new CakeHtmlReporter();
                break;
                
                default:
                    $Reporter = new TextReporter();
                break;
            }
        }
        return $Reporter;
    }
    
    function CakePHPTestRunMore()
    {
        switch ( CAKE_TEST_OUTPUT )
        {
            case CAKE_TEST_OUTPUT_XML:
            break;
            case CAKE_TEST_OUTPUT_HTML:
                echo "<p><a href='" . $_SERVER['PHP_SELF'] . "'>Run more tests</a></p>";
            break;
            
            case CAKE_TEST_OUTPUT_TEXT:
            default:
            break;
        }
    }
    
    function CakePHPTestCaseList()
    {
        switch ( CAKE_TEST_OUTPUT )
        {
            case CAKE_TEST_OUTPUT_XML:
                if (isset($_GET['app']))
                {
                    echo XmlTestManager::getTestCaseList(APP_TEST_CASES);
                }
                else
                {
                    echo XmlTestManager::getTestCaseList(CORE_TEST_CASES);  
                }
            break;
            
            case CAKE_TEST_OUTPUT_HTML:
                if (isset($_GET['app']))
                {
                    echo HtmlTestManager::getTestCaseList(APP_TEST_CASES);
                }
                else
                {
                    echo HtmlTestManager::getTestCaseList(CORE_TEST_CASES);  
                }
            break;
            
            case CAKE_TEST_OUTPUT_TEXT:
            default:
                if (isset($_GET['app']))
                {
                    echo TextTestManager::getTestCaseList(APP_TEST_CASES);
                }
                else
                {
                    echo TextTestManager::getTestCaseList(CORE_TEST_CASES);  
                }
            break;
    }
}

    function CakePHPTestGroupTestList() 
    {
        switch ( CAKE_TEST_OUTPUT )
        {
            case CAKE_TEST_OUTPUT_XML:
                if (isset($_GET['app']))
                {
                    echo XmlTestManager::getGroupTestList(APP_TEST_GROUPS);
                }
                else
                {
                    echo XmlTestManager::getGroupTestList(CORE_TEST_GROUPS);  
                }
            break;
            
            case CAKE_TEST_OUTPUT_HTML:
                if (isset($_GET['app']))
                {
                    echo HtmlTestManager::getGroupTestList(APP_TEST_GROUPS);
                }
                else
                {
                    echo HtmlTestManager::getGroupTestList(CORE_TEST_GROUPS);  
                }
            break;
            
            case CAKE_TEST_OUTPUT_TEXT:
            default:
                if (isset($_GET['app']))
                {
                    echo TextTestManager::getGroupTestList(APP_TEST_GROUPS);
                }
                else
                {
                    echo TextTestManager::getGroupTestList(CORE_TEST_GROUPS);  
                }
            break;
        }
    }

    function CakePHPTestHeader()
    {
        switch ( CAKE_TEST_OUTPUT )
        {
            case CAKE_TEST_OUTPUT_XML:
                header('Content-Type: text/xml; charset="utf-8"');
            break;
            case CAKE_TEST_OUTPUT_HTML:
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
            break;
            case CAKE_TEST_OUTPUT_TEXT:
            default:
                header('Content-type: text/plain');
            break;
        }
    }
    
    function CakePHPTestSuiteHeader()
    {
        switch ( CAKE_TEST_OUTPUT )
        {
            case CAKE_TEST_OUTPUT_XML:
            break;
            
            case CAKE_TEST_OUTPUT_HTML:
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
	<p><a href='$groups'>Core Test Groups</a>  ||   <a href='$cases'>Core Test Cases</a></p>
	<p><a href='$groups&app=true'>App Test Groups</a>  ||   <a href='$cases&app=true'>App Test Cases</a></p>
EOD;
            echo $suiteHeader;
            break;
            
            case CAKE_TEST_OUTPUT_TEXT:
            default:
            break;
        }
    }
    
    
    function CakePHPTestSuiteFooter()
    {
        switch ( CAKE_TEST_OUTPUT )
        {
            case CAKE_TEST_OUTPUT_XML:
            break;
            
            case CAKE_TEST_OUTPUT_HTML:
                $footer = <<<EOD
  </body>
</html>
EOD;
                echo $footer;
            break;
        
            case CAKE_TEST_OUTPUT_TEXT:
            default:
            break;
        }
    }


if (isset($_GET['group']))
{
    if ('all' == $_GET['group'])
    {
        TestManager::runAllTests(CakeTestsGetReporter());
    }
    else
    {
        if (isset($_GET['app']))
        {
        TestManager::runGroupTest(ucfirst($_GET['group']),
                                  APP_TEST_GROUPS,
                                  CakeTestsGetReporter());
        }
        else
        {
        TestManager::runGroupTest(ucfirst($_GET['group']),
                                  CORE_TEST_GROUPS,
                                  CakeTestsGetReporter());
        }
    }
    
    CakePHPTestRunMore();
    exit();
}

if (isset($_GET['case']))
{
    TestManager::runTestCase($_GET['case'], CakeTestsGetReporter());
    CakePHPTestRunMore();
    exit();
}

CakePHPTestHeader();
CakePHPTestSuiteHeader();

if (isset($_GET['show']) && $_GET['show'] == 'cases')
{
    CakePHPTestCaseList();
}
else
{
    CakePHPTestGroupTestList();
}
CakePHPTestSuiteFooter();

?>