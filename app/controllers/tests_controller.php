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
 * 
 * @package cake
 * @subpackage cake.app.controllers
 */
class TestsController extends TestsHelper {

/**
 * Runs all library and application tests
 *
 */
	function test_all () 
	{
		$this->layout = null;
		require_once SCRIPTS.'test.php';
	}
}

?>
