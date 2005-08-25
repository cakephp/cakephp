<?php
/* SVN FILE: $Id$ */

/**
 * Templating for Controller class. Takes care of rendering views.
 * 
 * Templating system for Cake.
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
 * @subpackage   cake.libs
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * Enter description here...
  */
uses('object');

/**
 * Templating for Controller class. Takes care of rendering views.
 *
 * Long description for class
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      CakePHP v 0.2.9
 */
class Template extends Object
{

/**
 * Base URL part
 *
 * @var string 
 * @access public
 */
   var $base = null;

/**
 * Layout name
 *
 * @var string
 * @access public
 */
   var $layout = 'default';

/**
 * Turns on or off Cake's conventional mode of rendering views. On by default.
 *
 * @var boolean
 * @access public
 */
   var $autoRender = true;

/**
 * Turns on or off Cake's conventional mode of finding layout files. On by default.
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
 * Title HTML element of current View.
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
