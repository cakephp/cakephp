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


if(!defined("TAG_JAVASCRIPT")) {
	define("TAG_JAVASCRIPT", '<script type="text/javascript">%s</script>');
}

if(!defined("TAG_JAVASCRIPT_INCLUDE")) {
	define("TAG_JAVASCRIPT_INCLUDE", '<script type="text/javascript" src="%s"></script>');
}

/**
 * Javascript Helper class for easy use of javascript.
 *
 * JavascriptHelper encloses all methods needed while working with javascripts.
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
        return sprintf(TAG_JAVASCRIPT, $script);
    }

    /**
     * Returns a JavaScript include tag
     *
     * @param  string $url URL to JavaScript file.
     * @return string
     */
    function link($url)
    {
        return sprintf(TAG_JAVASCRIPT_INCLUDE, $this->base.$url);
    }

/**
  * Escape carrier returns and single and double quotes for Javascript segments. 
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
  * Attach an event to an element
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
  * Includes the Prototype Javascript library (and anything else) inside a single script tag
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