<?php
/* SVN FILE: $Id$ */

/**
 * Javascript Helper class file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2006, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs.view.helpers
 * @since        CakePHP v 0.10.0.1076
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
 * @subpackage cake.cake.libs.view.helpers
 * @since      CakePHP v 0.10.0.1076
 */

class JavascriptHelper extends Helper
{

  var $_cachedEvents = array();
  var $_cacheEvents = false;

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
      return sprintf($this->tags['javascriptlink'], $this->webroot.JS_URL.$this->themeWeb.$url);
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
 * Escape a string to be JavaScript friendly.
 *
 * List of escaped ellements:
 *    + "\r\n" => '\n'
 *    + "\r"    => '\n'
 *    + "\n"    => '\n'
 *    + '"'    => '\"'
 *    + "'"    => "\\'"
 *
 * @param  string $script String that needs to get escaped.
 * @return string Escaped string.
 */
    function escapeString($string)
    {
        $escape = array(
        "\r\n" => '\n',
        "\r"    => '\n',
        "\n"    => '\n',
        '"'    => '\"',
        "'"    => "\\'"
        );

        return str_replace(array_keys($escape), array_values($escape), $string);
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
    if($useCapture == true)
    {
      $useCapture = "true";
    }
    else
    {
      $useCapture = "false";
    }

    $b = "Event.observe($object, '$event', function(event){ $observer }, $useCapture);";
    if($this->_cacheEvents === true)
    {
      $this->_cachedEvents[] = $b;
    }
    else
    {
      return $this->codeBlock($b);
    }
  }


/**
 * Cache JavaScript events created with event()
 *
 * @return null
 */
    function cacheEvents ()
    {
        $this->_cacheEvents = true;
    }
 

/**
 * Write cached JavaScript events
 *
 * @return string A single code block of all cached JavaScript events created with event()
 */
  function writeEvents ()
  {
    $this->_cacheEvents = false;
    return $this->codeBlock("\n" . implode("\n", $this->_cachedEvents) . "\n");
  }

/**
 * Includes the Prototype Javascript library (and anything else) inside a single script tag.
 *
 * Note: The recommended approach is to copy the contents of
 * javascripts into your application's
 * public/javascripts/ directory, and use @see javascriptIncludeTag() to
 * create remote script links.
 * @return string script with all javascript in/javascripts folder
 */
    function includeScript ($script = "")
    {
        if($script == "")
        {
            $files = scandir(JS);
            $javascript = '';
            foreach($files as $file)
            {
                if (substr($file, -3) == '.js')
                {
                    $javascript .= file_get_contents(JS."{$file}") . "\n\n";
                }
            }
        }
        else
        {
            $javascript = file_get_contents(JS."$script.js") . "\n\n";
        }
        return $this->codeBlock("\n\n" . $javascript);
    }

/**
 * Generates a JavaScript object in JavaScript Object Notation (JSON)
 * from an array
 *
 * @param array $data Data to be converted
 * @param boolean $block Wraps return value in a <script/> block if true
 * @param string $prefix Prepends the string to the returned data
 * @param string $postfix Appends the string to the returned data
 * @param array $stringKeys A list of array keys to be treated as a string
 * @param boolean $quoteKeys If false, treats $stringKey as a list of keys *not* to be quoted
 * @param string $q The type of quote to use
 * @return string A JSON code block
 */
    function object ($data = array(), $block = false, $prefix = '', $postfix = '', $stringKeys = array(), $quoteKeys = true, $q = "'")
    {
        $out = array();
        foreach($data as $key => $val)
        {
        	if (is_array($val))
        	{
        	    $out[] = $key.':'.$this->object($val, false, '', '', $stringKeys, $quoteKeys, $q);
        	}
        	else
        	{
        	    if ((!count($stringKeys) && !is_numeric($val) && !is_bool($val)) || ($quoteKeys && in_array($key, $stringKeys)) || (!$quoteKeys && !in_array($key, $stringKeys)))
        	    {
        	        $val = $q.$val.$q;
        	    }
        	    if (trim($val) == '')
        	    {
        	        $val = 'null';
        	    }
        	    
        	    $out[] = $key.':'.$val;
        	}
        }
        $rt = $prefix.'{'.join(', ', $out).'}'.$postfix;
        if ($block)
        {
            return $this->codeBlock($rt);
        }
        return $rt;
    }
}

?>