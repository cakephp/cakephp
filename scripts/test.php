<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
  * Enter description here...
  * 
  * @filesource 
  * @author CakePHP Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.scripts
  * @since CakePHP v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

/**
 * Helper function for outputing data. Should be moved somevhere else.
 * @param mixed $data Data to be dumped
 * @return string Dumped data
 */
function dump($data)
{
	ob_start();
	var_dump($data);
	return ob_get_clean();
}

/**
 * Defining some constatnts and requireing some files - so that this script can
 * be run from CLI.
 */
if (!defined('DS'))
{
	define ('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT'))
{
	define ('ROOT', dirname(dirname(__FILE__)).DS);
}
require_once ROOT.'config'.DS.'paths.php';
require_once CONFIGS.'core.php';
require_once CONFIGS.'tags.php';
require_once LIBS.'basics.php';

if (file_exists(CONFIGS.'database.php'))
	require_once CONFIGS.'database.php';

/**
 * Simpletest setup.
 */
if (!defined('SIMPLE_TEST'))
{
	define('SIMPLE_TEST', VENDORS.'simpletest/');
}
require_once SIMPLE_TEST.'unit_tester.php';
require_once SIMPLE_TEST.'reporter.php';

$groupTest = new GroupTest('Cake tests');

/**
 * We need to loop thru tests folder.
 */
uses('folder');
$testsFolder = new Folder(TESTS);
foreach ($testsFolder->findRecursive('.*\.php') as $test)
{
	$groupTest->addTestFile($test);
}

/**
 * Better formatting of HTML reporter.
 */
class CakeHtmlReporter extends HtmlReporter
{
	function _getCss()
	{
		return '.error { margin: 10px 0px; border: 1px solid #d7d4c7; padding: 4px; } .fail { color: red; font-weight: bold; } pre { background-color: lightgray; } .msg { margin-top: 5px; } body { font-family: Verdana; font-size: small; }';
	}
	function paintFail($message)
	{
		print '<div class="error">';
		print "<div class=\"fail\">Fail</div>";
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		print implode(" &raquo; ", $breadcrumb);
		print '<div class="msg">' . $this->_htmlEntities($message) . "</div>\n";
		print '</div>';
	}
	function paintException($message)
	{
		print '<div class="error">';
		print "<div class=\"fail\">Exception</div>";
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		print implode(" &raquo; ", $breadcrumb);
		print '<div class="msg">' . $this->_htmlEntities($message) . "</div>\n";
		print '</div>';
	}
}

/**
 * We need to run different reporters, depending on wheter we are CLI or not.
 */
if (TextReporter::inCli())
{
	/**
	 * Native text-reporter.
	 */
	if ($argv[1] == '-h' || $argv[1] == '--human-readable' || !is_file(VENDORS.'php/rephlux/cli_reporter.php'))
	{
		exit($groupTest->run(new TextReporter()) ? 0 : 1);
	}
	/**
	 * Rephlux reporter.
	 */
	else
	{
		require_once VENDORS.'php/rephlux/cli_reporter.php';
		exit($groupTest->run(new CLIReporter()) ? 0 : 1);
	}
}
else
{
	$groupTest->run(new CakeHtmlReporter());
}
?>