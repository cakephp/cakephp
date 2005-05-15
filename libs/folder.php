<?PHP 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <http://sputnik.pl/cake>                                    + //
// + Copyright: (c) 2005 Michal Tatarynowicz                          + //
// +                                                                  + //
// + Author(s): (c) 2005 Michal Tatarynowicz <tatarynowicz@gmail.com> + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under the Public Domain Licence                         + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Folder
  * Folder structure browser, lists folders and files.
  *
  * @filesource 
  * @modifiedby $LastChangedBy$  
  * @lastmodified $Date$
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @copyright Copyright (c) 2005, Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @license Public_Domain
  *
  */

/**
  * Enter description here...
  *
  */
uses('object');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Folder extends Object {
    
/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $path = null;

/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $sort = false;

/**
  * Enter description here...
  *
  * @param unknown_type $path
  */
    function __construct ($path) {
        $this->path = $path . (preg_match('#\/$#', $path)? '': '/');
        parent::__construct();
    }

/**
  * Enter description here...
  *
  * @param unknown_type $sort
  * @return unknown
  */
    function ls($sort=true) {
        if ($dir = opendir($this->path)) {
            $dirs = $files = array();
            while (false !== ($n = readdir($dir))) {
                if (!preg_match('#^\.+$#', $n)) {
                    if (is_dir($this->path.$n))
                    $dirs[] = $n;
                    else
                    $files[] = $n;
                }
            }

            if ($sort || $this->sort) {
                sort($dirs);
                sort($files);
            }

            return array($dirs,$files);
        }
        else {
            return false;
        }
    }

/**
  * Enter description here...
  *
  * @param unknown_type $path
  * @return unknown
  */
    function cd ($path) {
        $new_path = preg('#^/#', $path)? $path: $this->path.$path;
        return is_dir($new_path)? $this->path = $new_path: false;
    }

}

?>