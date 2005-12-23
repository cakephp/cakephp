<?php
/* SVN FILE: $Id: html.php 578 2005-08-12 04:09:07Z phpnut $ */

/**
 * Time Helper class file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, Cake Software Foundation, Inc. 
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @copyright    Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs.view.helpers
 * @since        CakePHP v 0.10.0.1076
 * @version      $Revision: 578 $
 * @modifiedby   $LastChangedBy: phpnut $
 * @lastmodified $Date: 2005-08-11 22:09:07 -0600 (Thu, 11 Aug 2005) $
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Time Helper class for easy use of time data.
 *
 * Manipulation of time data.
 *
 * @package    cake
 * @subpackage cake.cake.libs.view.helpers
 * @since      CakePHP v 0.10.0.1076
 */
class TimeHelper extends Helper
{
	/**
	 * Returns given string trimmed to given length, adding an ending (default: "..") if necessary.
	 *
	 * @param string $string String to trim
	 * @param integer $length Length of returned string, excluding ellipsis
	 * @param string $ending Ending to be appended after trimmed string
	 * @return string Trimmed string
	 */
	function trim($string, $length, $ending='..')
	{
		return substr($string, 0, $length).(strlen($string)>$length? $ending: null);
	}
	
	
	/**
     * Returns a UNIX timestamp, given either a UNIX timestamp or a valid strtotime() date string.
     *
     * @param string $date_string Datetime string
     * @return string Formatted date string
     */
	function fromString ($date_string) 
	{
    	return is_integer($date_string)
           ? $date_string
           : strtotime($date_string);
	}


	/**
     * Returns a nicely formatted date string for given Datetime string.
     *
     * @param string $date_string 	Datetime string or Unix timestamp
     * @param  boolean $return 		Whether this method should return a value
     *                         		or output it. This overrides AUTO_OUTPUT.
     * @return string 				Formatted date string
     */
	function nice ($date_string=null, $return = false) 
	{
		$date = $date_string? strtotime($date_string): time();
		$date = $date_string? $this->fromString($date_string): time();
		$ret = date("D, M jS Y, H:i", $date);
		
		return $this->output($ret, $return);
	}
	

	/**
     * Returns a formatted descriptive date string for given datetime string.
     *
     * If the given date is today, the returned string could be "Today, 16:54".
     * If the given date was yesterday, the returned string could be "Yesterday, 16:54".
     * If $date_string's year is the current year, the returned string does not
     * include mention of the year.
     *
     * @param string $date_string Datetime string or Unix timestamp
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.
     * @return string Described, relative date string
     */
	function niceShort ($date_string=null,$return = false)
	{
		$date = $date_string? $this->fromString($date_string): time();

		$y = $this->isThisYear($date)? '': ' Y';

		if ($this->isToday($date))
		{ 
			$ret = "Today, ".date("H:i", $date);
		}
		elseif ($this->wasYesterday($date))
		{
			$ret = "Yesterday, ".date("H:i", $date);
		}
		else
		{
			$ret = date("M jS{$y}, H:i", $date);
		}
			
		return $this->output($ret, $return);
	}

	
	/**
     * Returns true if given datetime string is today.
     *
     * @param string $date_string Datetime string or Unix timestamp
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.
     * @return boolean True if datetime string is today
     */
	function isToday ($date_string)
	{
		$date = $this->fromString($date_string, $return = false);
		$ret = date('Y-m-d', $date) == date('Y-m-d', time());
		
		return $this->output($ret, $return);
	}
	
	
	/**
     * Returns a partial SQL string to search for all records between two dates.
     *
     * @param string $date_string Datetime string or Unix timestamp
     * @param string $end Datetime string or Unix timestamp
     * @param string $field_name Name of database field to compare with
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.
     * @return string Partial SQL string.
     */
	function daysAsSql ($begin, $end, $field_name, $return = false)
	{
		$begin = $this->fromString($begin);
		$end = $this->fromString($end);
		$begin = date('Y-m-d', $begin).' 00:00:00';
		$end   = date('Y-m-d', $end).  ' 23:59:59';
		
		$ret = "($field_name >= '$begin') AND ($field_name <= '$end')";
		
		return $this->output($ret, $return);
	}

	
	/**
     * Returns a partial SQL string to search for all records between two times
     * occurring on the same day.
     *
     * @param string $date_string Datetime string or Unix timestamp
     * @param string $field_name Name of database field to compare with
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.
     * @return string Partial SQL string.
     */
	function dayAsSql ($date_string, $field_name, $return = false)
	{
		$date = $this->fromString($date_string);
		$ret = $this->daysAsSql($date_string, $date_string, $field_name);
		
		return $this->output($ret, $return);
	}
	
	
    /**
     * Returns true if given datetime string is within current year.
     *
     * @param string $date_string Datetime string or Unix timestamp
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.
     * @return boolean True if datetime string is within current year
     */
    function isThisYear ($date_string, $return = false) {
    	$date = $this->fromString($date_string);
		$ret = date('Y', $date) == date('Y', time());
		
		return $this->output($ret, $return);
	}	
	
	
    /**
     * Returns true if given datetime string was yesterday.
     *
     * @param string $date_string Datetime string or Unix timestamp
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.
     * @return boolean True if datetime string was yesterday
     */
	function wasYesterday ($date_string, $return = false) {
		$date = $this->fromString($date_string);
		$ret = date('Y-m-d', $date) == date('Y-m-d', strtotime('yesterday'));
		
		return $this->output($ret, $return);
	}	
	
	
    /**
     * Returns true if given datetime string is tomorrow.
     *
     * @param string $date_string Datetime string or Unix timestamp
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.
     * @return boolean True if datetime string was yesterday
     */
	function isTomorrow ($date_string, $return = false) {
		$date = $this->fromString($date_string);
		$ret = date('Y-m-d', $date) == date('Y-m-d', strtotime('tomorrow'));
		
		return $this->output($ret, $return);
	}	
	
	
    /**
     * Returns a UNIX timestamp from a textual datetime description. Wrapper for PHP function strtotime().
     *
     * @param string $date_string Datetime string to be represented as a Unix timestamp
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.
     * @return int Unix timestamp
     */
	function toUnix ($date_string, $return = false) {
		$ret = strtotime($date_string);
		
		return $this->output($ret, $return);
	}	
	
    /**
     * Returns a date formatted for Atom RSS feeds.
     *
     * @param string $date_string Datetime string or Unix timestamp
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.
     * @return string Formatted date string
     */
	function toAtom ($date_string, $return = false) {
		$date = $this->fromString($date_string);
		$ret = date('Y-m-d\TH:i:s\Z', $date);
		
		return $this->output($ret, $return);
	}

    /**
     * Formats date for RSS feeds
     *
     * @param string $date_string Datetime string or Unix timestamp
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.    
     * @return string Formatted date string
     */
	function toRSS ($date_string, $return = false) {
		$date = TimeHelper::fromString($date_string);
		$ret = date("r", $date);
		
		return $this->output($ret, $return);
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
     * like 'Posted ' before the function output.
     *      
     * @param string $date_string Datetime string or Unix timestamp
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.
     * @return string Relative time string.
     */
	function timeAgoInWords ($datetime_string, $return = false)
	{
		$datetime = $this->fromString($datetime_string);
		
		$in_seconds = $datetime;
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
			$ret = 'on '.date("j/n/Y", $in_seconds);
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
		$ret = $relative_date.' ago';
		
		return $this->output($ret, $return);
	}
	
	
   /**     
     * Alias for timeAgoInWords   
     * @param string $date_string Datetime string or Unix timestamp
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.        
     * @return string Relative time string.
     */
	function relativeTime ($datetime_string, $return = false) 
	{
		$ret = $this->timeAgoInWords ($datetime_string);
		
		return $this->output($ret, $return);	
	}
	
	/**
     * Returns true if specified datetime was within the interval specified, else false.
     *
     * @param mixed $timeInterval the numeric value with space then time
     *               type. Example of valid types: 6 hours, 2 days, 1 minute.
     * @param mixed $date the datestring or unix timestamp to compare
     * @param  boolean $return Whether this method should return a value
     *                         or output it. This overrides AUTO_OUTPUT.
     * @return boolean
     */
   function wasWithinLast($timeInterval, $date_string, $return = false)
   {
   	  $date = $this->fromString($date_string);
   	  
      $result = preg_split('/\\s/', $timeInterval);

      $numInterval = $result[0];
      $textInterval = $result[1];
      $currentTime = floor(time());
      $seconds = ($currentTime - floor($date));
      
      switch($textInterval) 
      {
         
         case "seconds":
         case "second":
		 $timePeriod = $seconds;
         $ret = $return;
         break;
         
         case "minutes":
         case "minute":
         $minutes = floor($seconds / 60);
		 $timePeriod = $minutes;
         break;
         

         case "hours":
         case "hour":
         $hours = floor($seconds / 3600);
		 $timePeriod = $hours;
         break;

         case "days":
         case "day":
         $days = floor($seconds / 86400);
		 $timePeriod = $days;
         break;

         case "weeks":
         case "week":
         $weeks = floor($seconds / 604800);
         $timePeriod = $weeks;
         break;
             

         case "months":
         case "month":
         $months = floor($seconds / 2629743.83);
		 $timePeriod = $months;
         break;

         
         case "years":
         case "year":
         $years = floor($seconds / 31556926);
		 $timePeriod = $years;
         break;


         default:
         $days = floor($seconds / 86400);
		 $timePeriod = $days;
         break;
      }

      if ($timePeriod <= $numInterval) 
      {
        $ret = true;
      } 
      else 
      {
      	$ret = false;
      }
      
      return $this->output($ret, $return);
      
   }


}

?>