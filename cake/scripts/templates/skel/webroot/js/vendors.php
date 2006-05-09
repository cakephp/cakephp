<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 *
 * This file includes js vendor-files from /vendor/ directory if they need to
 * be accessible to the public.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2006, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.app.webroot.js
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Enter description here...
 */
if(is_file('../../vendors/javascript/'.$_GET['file']) && (preg_match('/(.+)\\.js/', $_GET['file'])))
{
    readfile('../../vendors/javascript/'.$_GET['file']);
}

?>
