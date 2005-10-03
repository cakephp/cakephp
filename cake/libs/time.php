<?php
/* SVN FILE: $Id$ */

/**
 * Time library for Cake.
 * 
 * Methods for handling and formatting date and time information.
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
uses ('object');

/**
 * Time-related functions, formatting for dates etc.
 *
 * The Time class handles and formats date and time information.
 *
 * @package    cake
 * @subpackage cake.libs
 * @since      CakePHP v 0.2.9
 */
class Time extends Object 
{

/**
  * Returns a formatted date string for given Datetime string.
  *
  * @param string $date_string Datetime string
  * @return string Formatted date string
  */
   function nice ($date_string=null) 
   {
      $date = $date_string? strtotime($date_string): time();
      return date("D, M jS Y, H:i", $date);
   }

/**
  * Returns a formatted descriptive date string for given datetime string.
  * If the given date is today, the returned string could be "Today, 16:54".
  * If the given date was yesterday, the returned string could be "Yesterday, 16:54".
  * If $date_string's year is the current year, the returned string does not
  * include mention of the year.
  *
  * @param string $date_string Datetime string
  * @return string Described, relative date string
  */
   function niceShort ($date_string=null) 
   {
      $date = $date_string? Time::fromString($date_string): time();

      $y = Time::isThisYear($date)? '': ' Y';

      if (Time::isToday($date)) 
      {
         return "Today, ".date("H:i", $date);
      }
      elseif (Time::wasYesterday($date))
      {
         return "Yesterday, ".date("H:i", $date);
      }
      else
      {
         return date("M jS{$y}, H:i", $date);
      }
   }

/**
  * Returns true if given datetime string is today.
  *
  * @param string $date Datetime string
  * @return boolean True if datetime string is today
  */
   function isToday ($date) 
   {
      return date('Y-m-d', $date) == date('Y-m-d', time());
   }
   
/**
 * Returns SQL for selecting a date range between the datetime pair $begin and $end.
 *
 * @param string $begin Start of date range as a Datetime string 
 * @param string $end End of date range as a Datetime string 
 * @param string $field_name Name of database date field
 * @return string SQL code for selecting the date range
 */
   function daysAsSql ($begin, $end, $field_name)
   {
      $begin = date('Y-m-d', $begin).' 00:00:00';
      $end   = date('Y-m-d', $end).  ' 23:59:59';
      
      return "($field_name >= '$begin') AND ($field_name <= '$end')";
   }
   
/**
 * Returns SQL for selecting a date range that includes the whole day of given datetime string.
 *
 * @param string $date Datetime string 
 * @param string $field_name Name of database date field
 * @return SQL for selecting the date range of that full day
 * @see Time::daysAsSql()
 */
   function dayAsSql ($date, $field_name)
   {
      return Time::daysAsSql($date, $date, $field_name);
   }

/**
  * Returns true if given datetime string is within current year.
  *
  * @param string $date Datetime string
  * @return boolean True if datetime string is within current year
  */
   function isThisYear ($date) 
   {
      return date('Y', $date) == date('Y', time());
   }

/**
  * Returns true if given datetime string was yesterday.
  *
  * @param string $date Datetime string
  * @return boolean True if datetime string was yesterday
  */
   function wasYesterday ($date) 
   {
      return date('Y-m-d', $date) == date('Y-m-d', strtotime('yesterday'));
   }

/**
  * Returns a Unix timestamp from a textual datetime description. Wrapper for PHP function strtotime().
  *
  * @param string $date_string Datetime string to be represented as a Unix timestamp
  * @return int Unix timestamp
  */
   function fromString ($date_string) 
   {
      return strtotime($date_string);
   }

/**
  * Returns a date formatted for Atom RSS feeds.
  *
  * @param string $date Datetime string
  * @return string Formatted date string
  */
   function toAtom ($date) 
   {
      return date('Y-m-d\TH:i:s\Z', $date);
   }

/**
  * Formats date for RSS feeds
  *
  * @param datetime $date Datetime string
  * @return string Formatted date string
  * @todo Is this for RSS 0.9.2 or RSS 2.0?
  */
   function toRSS ($date) 
   {
      return date('D, d M Y H:i:s O', $date);
   }

/**     
 * Returns either a relative date or a formatted date depending
 * on the difference between the current time and given datetime.
 * $datetime should be in a <i>strtotime</i>-parsable format like MySQL datetime.
 *      
 *      Relative dates look something like this:
 *          3 weeks, 4 days ago
 *          15 seconds ago
 *      Formatted dates look like this:
 *          on 02/18/2004
 *      
 * The returned string includes 'ago' or 'on' and assumes you'll properly add a word
 *      like 'Posted ' before the function output.
 *      
 * @param $datetime   Time in strtotime-parsable format
 * @return string Relative time string.
 */

   function timeAgoInWords ($datetime)
   {
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
   
      if ($months>0) 
      {
         // over a month old, just show date (mm/dd/yyyy format)
         return 'on '.date("j/n/Y", $in_seconds);
      } 
      else 
      {
         $relative_date='';
         if ($weeks>0) 
         {
            // weeks and days
            $relative_date .= ($relative_date?', ':'').$weeks.' week'.($weeks>1?'s':'');
            $relative_date .= $days>0?($relative_date?', ':'').$days.' day'.($days>1?'s':''):'';
         } 
         elseif ($days>0) 
         {
            // days and hours
            $relative_date .= ($relative_date?', ':'').$days.' day'.($days>1?'s':'');
            $relative_date .= $hours>0?($relative_date?', ':'').$hours.' hour'.($hours>1?'s':''):'';
         } 
         elseif ($hours>0) 
         {
            // hours and minutes
            $relative_date .= ($relative_date?', ':'').$hours.' hour'.($hours>1?'s':'');
            $relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' minute'.($minutes>1?'s':''):'';
         } 
         elseif ($minutes>0) 
         {
            // minutes only
            $relative_date .= ($relative_date?', ':'').$minutes.' minute'.($minutes>1?'s':'');
         } 
         else 
         {
            // seconds only
            $relative_date .= ($relative_date?', ':'').$seconds.' second'.($seconds>1?'s':'');
         }
      }
      // show relative date and add proper verbiage
      return $relative_date.' ago';
   }
   
}

?>