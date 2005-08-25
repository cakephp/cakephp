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
 * @subpackage   cake.app.controllers
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Short description for class.
 * 
 * Long description for class
 *
 * @package    cake
 * @subpackage cake.app.controllers
 */
class TestsController extends TestsHelper {
    
   function index () 
   {
      $this->layout = null;
      require_once TESTS.'index.php';
   }
   
   function groups () 
   {
      $this->layout = null;
      require_once TESTS.'index.php';
   }
   
   function cases () 
   {
      $this->layout = null;
      require_once TESTS.'index.php';
   }
/**
 * Runs all library and application tests
 *
 */
//   function test_all () 
//   {
//      $this->layout = null;
//      require_once SCRIPTS.'test.php';
//   }
}

?>
