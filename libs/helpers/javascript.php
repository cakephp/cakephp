<?php
/* SVN FILE: $Id$ */

/**
 * Javascript Helper class file.
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
 * Javascript Helper class for easy use of JavaScript.
 *
 * JavascriptHelper encloses all methods needed while working with JavaScript.
 *
 * @package    cake
 * @subpackage cake.libs.helpers
 * @since      CakePHP v 0.9.2
 */

class JavascriptHelper extends Helper
{
    /**
     * Returns a JavaScript script tag.
     *
     * @param  string $script The JavaScript to be wrapped in SCRIPT tags.
     * @return string The full SCRIPT element, with the JavaScript inside it.
     */
    function codeBlock($script)
    {
        return sprintf($this->tags['javascriptblock'], $script);
    }

    /**
     * Returns a JavaScript include tag (SCRIPT element)
     *
     * @param  string $url URL to JavaScript file.
     * @return string
     */
    function link($url)
    {
    	if(strpos($url, ".") === false) $url .= ".js";
      return sprintf($this->tags['javascriptlink'], $this->base . JS_URL . $url);
    }

    /**
     * Returns a JavaScript include tag for an externally-hosted script
     *
     * @param  string $url URL to JavaScript file.
     * @return string
     */
    function linkOut($url)
    {
    		if(strpos($url, ".") === false) $url .= ".js";
        return sprintf($this->tags['javascriptlink'], $url);
    }

/**
  * Escape carriage returns and single and double quotes for JavaScript segments. 
  * 
  * @param string $script string that might have javascript elements
  * @return string escaped string
  */
	function escapeScript ($script)
	{
		$script = str_replace(array("\r\n","\n","\r"),'\n', $script);
		$script = str_replace(array('"', "'"), array('\"', "\\'"), $script);
		return $script;
	}

/**
  * Attach an event to an element. Used with the Prototype library.
  * 
  * @param string $object Object to be observed
  * @param string $event event to observe
  * @param string $observer function to call
  * @param boolean $useCapture default true
  * @return boolean true on success
  */
	function event ($object, $event, $observer, $useCapture = true)
	{
		return $this->codeBlock("Event.observe($object, '$event', $observer, $useCapture);");
	}


/**
  * Includes the Prototype Javascript library (and anything else) inside a single script tag.
  * 
  * Note: The recommended approach is to copy the contents of
  * lib/javascripts/ into your application's
  * public/javascripts/ directory, and use @see javascriptIncludeTag() to
  * create remote script links.
  * @return string script with all javascript in /javascripts folder
  */
	function includeScript ($script = "")
	{
		$dir = VENDORS . "/javascript";
		if($script == "") {
			$files = scandir($dir);
			$javascript = '';
			foreach($files as $file)
			{
				if (substr($file, -3) == '.js')
				{
					$javascript .= file_get_contents("$dir/$file") . "\n\n";
				}
			}
		}
		else
		{
			$javascript = file_get_contents("$dir/$script.js") . "\n\n";
		}
		return $this->codeBlock("\n\n" . $javascript);
	}

}

?>