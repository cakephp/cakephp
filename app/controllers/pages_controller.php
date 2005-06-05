<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 *  
 * 
 *
 * @filesource 
 * @author Cake Authors/Developers
 * @copyright Copyright (c) 2005, Cake Authors/Developers
 * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.app.controllers
 * @since Cake v 1.0.0.158
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * 
 * 
 *
 * @package cake
 * @subpackage cake.app.controllers
 * @since Cake v 1.0.0.158
 *
 */
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