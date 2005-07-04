<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
//////////////////////////////////////////////////////////////////////////


/**
 *
 * @filesource 
 * @package cake
 * @subpackage cake.app.controllers
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 */

/**
 * This file is application-wide controller file. You can put all 
 * application-wide controller-related methods here.
 *
 * Add your application-wide methods in the class below, your controllers 
 * will inherit them.
 * 
 * @package cake
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