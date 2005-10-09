<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 * 
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect 
 * different urls to chosen controllers and their actions (functions).
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
 * @subpackage   cake.cake.app.config
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Here, we are connecting '/' (base path) to controller called 'Pages', 
 * its action called 'display', and we pass a param to select the view file 
 * to use (in this case, /app/views/pages/home.thtml)...
 */
$Route->connect ('/', array('controller'=>'Pages', 'action'=>'display', 'home'));

/**
 * ...and connect the rest of 'Pages' controller's urls.
 */
$Route->connect ('/pages/*', array('controller'=>'Pages', 'action'=>'display'));

/**
 * Then we connect url '/test' to our test controller. This is helpfull in
 * developement.
 */
$Route->connect ('/tests', array('controller'=>'Tests', 'action'=>'index'));

?>
