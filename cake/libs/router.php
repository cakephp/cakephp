<?php
/* SVN FILE: $Id$ */

/**
 * Parses the request URL into controller, action, and parameters.
 *
 * Long description for file
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
 * @subpackage   cake.cake.libs
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Included libraries.
 *
 */
if(!class_exists('Object'))
{
    uses('object');
}
/**
 * Parses the request URL into controller, action, and parameters.
 *
 * @package    cake
 * @subpackage cake.cake.libs
 * @since      CakePHP v 0.2.9
 *
 */
class Router extends Object {

/**
 * Array of routes
 *
 * @var array
 */
    var $routes = array();

    function __construct ()
    {
        if(defined('CAKE_ADMIN'))
        {
            $admin = CAKE_ADMIN;
            if(!empty($admin))
            {
                $this->routes[] = array (
                    '/:'.$admin.'/:controller/:action/* (default)',
                    '/^(?:\/(?:('.$admin.')(?:\\/([a-zA-Z0-9_\\-\\.]+)(?:\\/([a-zA-Z0-9_\\-\\.]+)(?:[\\/\\?](.*))?)?)?))[\/]*$/',
                    array($admin, 'controller', 'action'),
                    array()
                );
            }
        }
    }

/**
 * TODO: Better description. Returns this object's routes array. Returns false if there are no routes available.
 *
 * @param string $route    An empty string, or a route string "/"
 * @param array $default    NULL or an array describing the default route
 * @see routes
 * @return array            Array of routes
 */
    function connect ($route, $default=null)
    {
      $parsed = $names = array ();

      $r = null;
      if (($route == '') || ($route == '/'))
      {
         $regexp = '/^[\/]*$/';
         $this->routes[] = array($route, $regexp, array(), $default);
      }
      else
      {
         $elements = array();
         foreach (explode('/', $route) as $element)
         {
            if (trim($element)) $elements[] = $element;
         }

         if (!count($elements))
         {
            return false;
         }

         foreach ($elements as $element)
         {
            if (preg_match('/^:(.+)$/', $element, $r))
            {
                $parsed[] = '(?:\/([^\/]+))?';
                $names[] = $r[1];
            }
            elseif (preg_match('/^\*$/', $element, $r))
            {
                $parsed[] = '(?:\/(.*))?';
            }
            else
            {
                $parsed[] = '/'.$element;
            }
         }
         $regexp = '#^'.join('', $parsed).'[\/]*$#';
         $this->routes[] = array($route, $regexp, $names, $default);
      }
      return $this->routes;
    }

/**
 * Parses given URL and returns an array of controllers, action and parameters
 * taken from that URL.
 *
 * @param string $url URL to be parsed
 * @return array
 */
    function parse ($url)
    {
// An URL should start with a '/', mod_rewrite doesn't respect that, but no-mod_rewrite version does.
// Here's the fix.
      if ($url && ('/' != $url[0]))
      {
         if (!defined('SERVER_IIS'))
         {
             $url = '/'.$url;
         }
      }

      $out = array();
      $r = null;

      $default_route = array
      (
         '/:controller/:action/* (default)',
         '/^(?:\/(?:([a-zA-Z0-9_\\-\\.]+)(?:\\/([a-zA-Z0-9_\\-\\.]+)(?:[\\/\\?](.*))?)?))[\\/]*$/',
         array('controller', 'action'),
         array());

      $this->connect('/bare/:controller/:action/*', array('bare'=>'1'));
      $this->connect('/ajax/:controller/:action/*', array('bare'=>'1'));

      if(defined('WEBSERVICES') && WEBSERVICES == 'on' )
      {
          $this->connect('/rest/:controller/:action/*', array('webservices'=>'Rest'));
          $this->connect('/rss/:controller/:action/*', array('webservices'=>'Rss'));
          $this->connect('/soap/:controller/:action/*', array('webservices'=>'Soap'));
          $this->connect('/xml/:controller/:action/*', array('webservices'=>'Xml'));
          $this->connect('/xmlrpc/:controller/:action/*', array('webservices'=>'XmlRpc'));
      }

      $this->routes[] = $default_route;
      if (strpos($url, '?') !== false)
      {
          $url = substr($url, 0, strpos($url, '?'));
      }
      foreach ($this->routes as $route)
      {
         list($route, $regexp, $names, $defaults) = $route;


         if (preg_match($regexp, $url, $r))
         {
// $this->log($url.' matched '.$regexp, 'note');
// remove the first element, which is the url
            array_shift($r);

// hack, pre-fill the default route names
            foreach ($names as $name)
            {
                $out[$name] = null;
            }

            $ii = 0;

            if (is_array($defaults))
            {
                foreach ($defaults as $name=>$value)
                {
                  if (preg_match('#[a-zA-Z_\-]#i', $name))
                  {
                     $out[$name] = $value;
                  }
                  else
                  {
                     $out['pass'][] = $value;
                  }
                }
            }

            foreach ($r as $found)
            {
// if $found is a named url element (i.e. ':action')
                if (isset($names[$ii]))
                {
                  $out[$names[$ii]] = $found;
                }
// unnamed elements go in as 'pass'
                else
                {
                  $pass = new NeatArray(explode('/', $found));
                  $pass->cleanup();
                  $out['pass'] = $pass->value;
                }
                $ii++;
            }
            break;
         }
      }

      return $out;
    }
}

?>