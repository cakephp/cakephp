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
  * Purpose: 
  * Enter description here...
  * 
  * @filesource 
  * @modifiedby $LastChangedBy$  
  * @lastmodified $Date$
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @copyright Copyright (c) 2005, Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @package cake
  * @subpackage cake.config
  * @since Cake v 0.2.9
  * @version $Revision$
  * @license Public_Domain
  *
  */

# Homepage
$Route->connect ('/', array('controller'=>'Memes', 'action'=>'add'));

# Tags
$Route->connect ('/tags/popular', array('controller'=>'Tags', 'action'=>'popular'));
$Route->connect ('/tags/*', array('controller'=>'Memes', 'action'=>'with_tags'));

# Default route
$Route->connect ('/:controller/:action/*');

?>