<?php
/* SVN FILE: $Id$ */

/**
 * Request object for handling alternative HTTP requests
 *
 * Alternative HTTP requests can come from wireless units like mobile phones, palmtop computers, and the like.
 * These units have no use for Ajax requests, and this Component can tell how Cake should respond to the different
 * needs of a handheld computer and a desktop machine.
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
 * @subpackage   cake.cake.libs.controller.components
 * @since        CakePHP v 0.10.4.1076
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */


if (!defined('REQUEST_MOBILE_UA'))
{
    define('REQUEST_MOBILE_UA', '[AvantGo|BlackBerry|DoCoMo|NetFront|Nokia|PalmOS|PalmSource|portalmmm|Plucker|ReqwirelessWeb|SonyEricsson|Symbian|UP\.Browser|Windows CE|Xiino]');
}

/**
 * Request object for handling alternative HTTP requests
 *
 * @package    cake
 * @subpackage cake.cake.libs.controller.components
 * @since      CakePHP v 0.10.0.1839
 *
 */
class RequestHandlerComponent extends Object
{

    var $controller = true;

    var $ajaxLayout = 'ajax';

    var $disableStartup = false;


/**
 * Startup
 *
 * @param object A reference to the controller
 * @return null
 */
    function startup(&$controller)
    {
        $this->setAjax($controller);
        
    }

/**
 * Sets a controller's layout based on whether or not the current call is Ajax
 *
 * @param object The controller object
 * @return null
 */
    function setAjax(&$controller)
    {
        if ($this->disableStartup)
        {
            return;
        }

        if ($this->isAjax())
        {
            $controller->layout = $this->ajaxLayout;

// Add UTF-8 header for IE6 on XPsp2 bug
            header('Content-Type: text/html; charset=UTF-8');
        }
    }

/**
 * Returns true if the current call is from Ajax, false otherwise
 *
 * @return bool True if call is Ajax
 */
    function isAjax()
    {
        if(env('HTTP_X_REQUESTED_WITH') != null)
        {
            return env('HTTP_X_REQUESTED_WITH') == "XMLHttpRequest";
        }
        else
        {
            return false;
        }
    }


/**
 * Gets Prototype version if call is Ajax, otherwise empty string. 
 * The Prototype library sets a special "Prototype version" HTTP header.
 *
 * @return string Prototype version of component making Ajax call
 */
    function getAjaxVersion() {
        if (env('HTTP_X_PROTOTYPE_VERSION') != null)
        {
            return env('HTTP_X_PROTOTYPE_VERSION');
        }
        return false;
    }

/**
 * Gets the server name from which this request was referred
 *
 * @return string Server address
 */
    function getReferrer ()
    {
        if (env('HTTP_HOST') != null) {
            $sess_host = env('HTTP_HOST');
        }

        if (env('HTTP_X_FORWARDED_HOST') != null)
        {
            $sess_host = env('HTTP_X_FORWARDED_HOST');
        }
        return trim(preg_replace('/:.*/', '', $sess_host));
    }


/**
 * Gets remote client IP
 *
 * @return string Client IP address
 */
    function getClientIP ()
    {

        if (env('HTTP_X_FORWARDED_FOR') != null)
        {
            $ipaddr = preg_replace('/,.*/', '', env('HTTP_X_FORWARDED_FOR'));
        }
        else
        {
            if (env('HTTP_CLIENT_IP') != null)
            {
                $ipaddr = env('HTTP_CLIENT_IP');
            }
            else
            {
                $ipaddr = env('REMOTE_ADDR');
            }
        }

        if (env('HTTP_CLIENTADDRESS') != null)
        {
            $tmpipaddr = env('HTTP_CLIENTADDRESS');
            if (!empty($tmpipaddr))
            {
                $ipaddr = preg_replace('/,.*/', '', $tmpipaddr);
            }
        }
        return trim($ipaddr);
    }


/**
 * Returns true if user agent string matches a mobile web browser
 *
 * @return bool True if user agent is a mobile web browser
 */
    function isMobile()
    {
        return (preg_match(REQUEST_MOBILE_UA, $_SERVER['HTTP_USER_AGENT']) > 0);
    }

/**
 * Strips extra whitespace from output
 *
 * @param string $str
 */
    function stripWhitespace($str)
    {
        $r = preg_replace('/[\n\r\t]+/', '', $str);
        return preg_replace('/\s{2,}/', ' ', $r);
    }

/**
 * Strips image tags from output
 *
 * @param string $str
 */
    function stripImages($str)
    {
        $str = preg_replace('/(<a[^>]*>)(<img[^>]+alt=")([^"]*)("[^>]*>)(<\/a>)/i', '$1$3$5<br />', $str);
        $str = preg_replace('/(<img[^>]+alt=")([^"]*)("[^>]*>)/i', '$2<br />', $str);
        $str = preg_replace('/<img[^>]*>/i', '', $str);
        return $str;
    }

/**
 * Strips scripts and stylesheets from output
 *
 * @param string $str
 */
    function stripScripts($str)
    {
        return preg_replace('/(<link[^>]+rel="[^"]*stylesheet"[^>]*>|<img[^>]*>|style="[^"]*")|<script[^>]*>.*?<\/script>|<style[^>]*>.*?<\/style>|<!--.*?-->/i', '', $str);
    }

/**
 * Strips extra whitespace, images, scripts and stylesheets from output
 *
 * @param string $str
 */
    function stripAll($str) {
        $str = $this->stripWhitespace($str);
        $str = $this->stripImages($str);
        $str = $this->stripScripts($str);
        return $str;
	}

/**
 * Strips the specified tags from output
 *
 * @param string $str 
 * @param string $tag
 * @param string $tag
 * @param string ...
 */
    function stripTags()
    {
        $params = params(func_get_args());
        $str = $params[0];

        for($i = 1; $i < count($params); $i++) {
            $str = preg_replace('/<' . $params[$i] . '[^>]*>/i', '', $str);
            $str = preg_replace('/<\/' . $params[$i] . '[^>]*>/i', '', $str);
        }
        return $str;
    }
}

?>