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
 * Author(s): Larry E. Masters aka PhpNut <nut@phpnut.com>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.cake.libs
 * @since        CakePHP v .0.10.0.1233
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Short description for file.
 * 
 * Long description for file
 *
 * @package    cake
 * @subpackage cake.cake.1233
 * @since      CakePHP v .0.10.0.1222
 */
class Security extends Object
{
    
    function &getInstance()
    {
        static $instance = array();
        
        if (!$instance)
        {
            $instance[0] =& new Security;
        }
        return $instance[0];
    }
    
    function inactiveMins()
    {
        $security =& Security::getInstance();
        switch (CAKE_SECURITY)
        {
            case 'high':
                return 10;
            break;
            case 'medium':
                return 20;
            break;
            case 'low':
            default :
                return 30;
            break;
        }
    }
} 
    
?>