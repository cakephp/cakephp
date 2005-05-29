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
  * Purpose: Time
  * Time related functions, formatting for dates etc.
  *
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
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
  * @param string $date_string
  * @return unknown
  */
	function fromString ($date_string) {
		return strtotime($date_string);
	}

/**
  * Formats date for Atom RSS feeds
  *
  * @param datetime $date
  * @return string
  */
	function toRss ($date) {
		return date('Y-m-d', $date).'T'.date('H:i:s', $date).'Z';
	}

/**     
 *      This function returns either a relative date or a formatted date depending
 *      on the difference between the current datetime and the datetime passed.
 *      $datetime should be in a <i>strtotime<i/i> parsable format like MySQL datetime.
 *      
 *      Relative dates look something like this:
 *          3 weeks, 4 days ago
 *	    15 seconds ago
 *      Formatted dates look like this:
 *          on 02/18/2004
 *      
 *      The function includes 'ago' or 'on' and assumes you'll properly add a word
 *      like 'Posted ' before the function output.
 *      
 * @param $datetimne	time in strtotime parsable format
 * @return	 string	relative time string.
 */

	function timeAgoInWords ($datetime) {

	    $in_seconds=strtotime($datetime);
	    $diff = time()-$in_seconds;
	    $months = floor($diff/2419200);
	    $diff -= $months*2419200;
	    $weeks = floor($diff/604800);
	    $diff -= $weeks*604800;
	    $days = floor($diff/86400);
	    $diff -= $days*86400;
	    $hours = floor($diff/3600);
	    $diff -= $hours*3600;
	    $minutes = floor($diff/60);
	    $diff -= $minutes*60;
	    $seconds = $diff;
	
	    if ($months>0) {
	        // over a month old, just show date (mm/dd/yyyy format)
	        return 'on '.date("j/n/Y", $in_seconds);
	    } else {
	        $relative_date='';
	        if ($weeks>0) {
	            // weeks and days
	            $relative_date .= ($relative_date?', ':'').$weeks.' week'.($weeks>1?'s':'');
	            $relative_date .= $days>0?($relative_date?', ':'').$days.' day'.($days>1?'s':''):'';
	        } elseif ($days>0) {
	            // days and hours
	            $relative_date .= ($relative_date?', ':'').$days.' day'.($days>1?'s':'');
	            $relative_date .= $hours>0?($relative_date?', ':'').$hours.' hour'.($hours>1?'s':''):'';
	        } elseif ($hours>0) {
	            // hours and minutes
	            $relative_date .= ($relative_date?', ':'').$hours.' hour'.($hours>1?'s':'');
	            $relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' minute'.($minutes>1?'s':''):'';
	        } elseif ($minutes>0) {
	            // minutes only
	            $relative_date .= ($relative_date?', ':'').$minutes.' minute'.($minutes>1?'s':'');
	        } else {
	            // seconds only
	            $relative_date .= ($relative_date?', ':'').$seconds.' second'.($seconds>1?'s':'');
	        }
	    }
	    // show relative date and add proper verbiage
	    return $relative_date.' ago';
	}
	
}

?>