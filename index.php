<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 * 
 *  This file collects requests if:
 *    - no mod_rewrite is avilable or .htaccess files are not supported
 *    - /public is not set as a web root.
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
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 *  Get Cake's root directory
 */
define ('DS', DIRECTORY_SEPARATOR);
define ('ROOT', dirname(__FILE__).DS);

/**
 *  We need to redefine some constants and variables, so that Cake knows it is
 *  working without mod_rewrite.
 */
define ('BASE_URL', $_SERVER['SCRIPT_NAME'].'?url=');

/**
 * As mod_rewrite (or .htaccess files) is not working, we need to take care
 * of what would normally be rewrited, i.e. the static files in /public
 */
   if (empty($_GET['url']) || ($_GET['url'] == '/'))
   {
      require (ROOT.'public/index.php');
   }
   else
   {
      $elements = explode('/index.php?url=', $_SERVER['REQUEST_URI']);
      $base = $elements[0].'/public';
      $path = $elements[1];
      
      $filename = ROOT.'public'.str_replace('/', DS, $path);
      $url = $base.$path;

      if (file_exists($filename))
      {
         if (preg_match('/^.*\.([a-z]+)$/i', $filename, $ext))
         {
            switch ($ext[1])
            {
               case 'jpg':
               case 'jpeg':
                  header('Content-type: image/jpeg');
               break;
               
               case 'css':
                  header('Content-type: text/css');
            }
         }
         
         print (file_get_contents($filename));
         die();
      }
      else 
      {
         require (ROOT.'public/index.php');
      }
   }
?>