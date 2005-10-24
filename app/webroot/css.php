<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 * 
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, CakePHP Authors/Developers
 *
 * Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com>
 *            Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The MIT License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.cake.app.webroot
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Enter description here...
 */
require_once(CONFIGS.'paths.php');
require_once(CAKE.'basics.php');
require_once(LIBS.'folder.php');
require(LIBS.'file.php');
require(LIBS.'legacy.php');

/**
 * Enter description here...
 *
 * @param unknown_type $path
 * @param unknown_type $name
 * @return unknown
 */
function make_clean_css ($path, $name)
{
   require_once(VENDORS.'csspp'.DS.'csspp.php');

   $data = file_get_contents($path);
   $csspp = new csspp();
   $output = $csspp->compress($data);

   $ratio = 100-(round(strlen($output)/strlen($data), 3)*100);
   $output = " /* file: $name, ratio: $ratio% */ " . $output;

   return $output;
}

/**
 * Enter description here...
 *
 * @param unknown_type $path
 * @param unknown_type $content
 * @return unknown
 */
function write_css_cache ($path, $content)
{
   if (!is_dir(dirname($path)))
      mkdir(dirname($path));
   
   $cache = new File($path);
   return $cache->write($content);
}

if (preg_match('|\.\.|', $url) || !preg_match('|^ccss/(.+)$|i', $url, $regs)) 
   die('Wrong file name.');

$filename = 'css/'.$regs[1];
$filepath = CSS.$regs[1];
$cachepath = CACHE.'css'.DS.str_replace(array('/','\\'), '-', $regs[1]);

if (!file_exists($filepath))
   die('Wrong file name.');


if (file_exists($cachepath))
{
   $templateModified = filemtime($filepath);
   $cacheModified = filemtime($cachepath);
   
   if ($templateModified > $cacheModified)
   {
      $output = make_clean_css ($filepath, $filename);
      write_css_cache ($cachepath, $output);
   }
   else 
   {
      $output = file_get_contents($cachepath);
   }
}
else 
{
   $output = make_clean_css ($filepath, $filename);
   write_css_cache ($cachepath, $output);
}

header("Date: ".date("D, j M Y G:i:s ", $templateModified).'GMT');
header("Content-Type: text/css");
header("Expires: ".date("D, j M Y G:i:s T", time()+DAY));
header("Cache-Control: cache"); // HTTP/1.1
header("Pragma: cache"); // HTTP/1.0
print $output;

?>