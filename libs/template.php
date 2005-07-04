<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
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
 * Purpose: Renderer
 * Templating for Controller class.
 *
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, Cake Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

uses('object');

/**
 * Templating for Controller class. Takes care of rendering views.
 *
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 */
class Template extends Object
{

	/**
	 * Enter description here...
	 *
	 * @var unknown_type
	 * @access public
	 */
	var $base = null;

	/**
	 * Enter description here...
	 *
	 * @var string
	 * @access public
	 */
	var $layout = 'default';

	/**
	 * Enter description here...
	 *
	 * @var boolean
	 * @access public
	 */
	var $autoRender = true;

	/**
	 * Enter description here...
	 *
	 * @var boolean
	 * @access public
	 */
	var $autoLayout = true;

	/**
	 * Variables for the view
	 *
	 * @var array
	 * @access private
	 */
	var $_viewVars = array();

	/**
	 * Enter description here...
	 *
	 * @var boolean
	 * @access private
	 */
	var $pageTitle = false;

	/**
	 * Set the title element of the page.
	 *
	 * @param string $pageTitle Text for the title
	 */
	function setTitle($pageTitle)
	{
		$this->pageTitle = $pageTitle;
	}


}

?>
