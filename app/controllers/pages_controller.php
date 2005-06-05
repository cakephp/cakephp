<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
//////////////////////////////////////////////////////////////////////////

class PagesController extends PagesHelper {

/**
 *  Displays a view
 */
	function display () {

		if (!func_num_args())
			$this->redirect('/');

		$path = func_get_args();
		if (!count($path))
			$this->redirect('/');

		$this->set('page', $path[0]);
		$this->set('subpage', empty($path[1])? null: $path[1]);
		$this->set('title', ucfirst($path[count($path)-1]));
		$this->render(join('/', $path));
	}

}

?>