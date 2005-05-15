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
  * Purpose: Time
  * Time related functions, formatting for dates etc.
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
uses ('object');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Time extends Object {

/**
  * Enter description here...
  *
  * @param unknown_type $date_string
  * @return unknown
  */
    function nice ($date_string=null) {
        $date = $date_string? strtotime($date_string): time();
        return date("D, M jS Y, H:i", $date);
    }

/**
  * Enter description here...
  *
  * @param unknown_type $date_string
  * @return unknown
  */
    function nice_short ($date_string=null) {
        $date = $date_string? Time::fromString($date_string): time();

        $y = Time::isThisYear($date)? '': ' Y';

        if (Time::isToday($date))
        return "Today, ".date("H:i", $date);
        elseif (Time::wasYesterday($date))
        return "Yesterday, ".date("H:i", $date);
        else
        return date("M jS{$y}, H:i", $date);
    }

/**
  * Enter description here...
  *
  * @param unknown_type $date
  * @return unknown
  */
    function isToday ($date) {
        return date('Y-m-d', $date) == date('Y-m-d', time());
    }

/**
  * Enter description here...
  *
  * @param unknown_type $date
  * @return unknown
  */
    function isThisYear ($date) {
        return date('Y', $date) == date('Y', time());
    }

/**
  * Enter description here...
  *
  * @param unknown_type $date
  * @return unknown
  */
    function wasYesterday ($date) {
        return date('Y-m-d', $date) == date('Y-m-d', strtotime('yesterday'));
    }

/**
  * Enter description here...
  *
  * @param unknown_type $date_string
  * @return unknown
  */
    function fromString ($date_string) {
        return strtotime($date_string);
    }

}

?>