<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 * 
 * This file is application-wide controller file. You can put all 
 * application-wide controller-related methods here.
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
 * This file is application-wide controller file. You can put all 
 * application-wide controller-related methods here.
 *
 * Add your application-wide methods in the class below, your controllers 
 * will inherit them.
 * 
 * @package    cake
 * @subpackage cake.app.controllers
 */
class PagesController extends PagesHelper{
   
/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $helpers = array('html', 'ajax');
   

/**
 * Displays a view
 *
 */
   function display()
   {
      if (!func_num_args())
      {
         $this->redirect('/');
      }

      $path = func_get_args();
      
      if (!count($path))
      {
         $this->redirect('/');
      }

      $this->set('page', $path[0]);
      $this->set('subpage', empty($path[1])? null: $path[1]);
      $this->set('title', ucfirst($path[count($path)-1]));
      $this->render(join('/', $path));
   }

}

?>