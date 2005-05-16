<?PHP 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Time
  * Time related functions, formatting for dates etc.
  *
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
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
	function niceShort ($date_string=null) {
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