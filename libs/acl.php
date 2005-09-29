<?php
/* SVN FILE: $Id$ */

/**
 * Access Control List factory class.
 * 
 * Permissions system.
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
 * @subpackage   cake.libs.helpers
 * @since        CakePHP v 0.9.2
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Access Control List factory class.
 * 
 * Looks for ACL implementation class in core config, and returns an instance of that class.
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      CakePHP v 0.9.2
 *
 */
class Acl
{
    /**
     * Static function used to gain an instance of the correct ACL class.
     *
     * @return MyACL
     */
    function getACL()
    {
        require_once CONFIGS.'core.php';
        require_once APP.'apis'.DS.ACL_FILENAME;

        $myacl = ACL_CLASSNAME;
        return new $myacl;
    }
}
?>