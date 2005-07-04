<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + CakePHP : Rapid Development Framework <http://www.cakephp.org/>  + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
//////////////////////////////////////////////////////////////////////////

class TestsController extends TestsHelper {

/**
 * Runs all library and application tests
 *
 * @package cake
 * @subpackage cake.app
 */
	function test_all () 
	{
		$this->layout = null;
		require_once SCRIPTS.'test.php';
	}
}

?>
