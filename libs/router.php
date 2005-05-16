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
  * Purpose: Router
  * Parses the request URL into controller, action, and params
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
uses('object');

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Router extends Object {

/**
  * Enter description here...
  *
  * @var unknown_type
  */
    var $routes = array();

/**
  * Enter description here...
  *
  */
    function __construct () {
        parent::__construct();
    }

/**
  * Enter description here...
  *
  * @param unknown_type $route
  * @param unknown_type $default
  */
    function connect ($route, $default=null) {
        $parsed = $names = array ();

        $r = null;
        if ($route == '' || $route == '/') {
            $this->routes[] = array('/^[\/]*$/', array(), $default);
        }
        else {
            if (@preg_match_all('|(?:/([^/]+))|', $route, $r)) {

                foreach ($r[1] as $element) {
                    if (preg_match('/^:(.+)$/', $element, $r)) {
                        $parsed[] = '(?:\/([^\/]+))?';
                        $names[] = $r[1];
                    }
                    elseif (preg_match('/^\*$/', $element, $r)) {
                        $parsed[] = '(.*)';
                    }
                    else {
                        $parsed[] = '/'.$element;
                    }
                }
                $regexp = '#^'.join('', $parsed).'$#';

                $this->routes[] = array($regexp,$names,$default);
            }
        }

    }

/**
  * Enter description here...
  *
  * @param unknown_type $url
  * @return unknown
  */
    function parse ($url) {
        $out = array();
        $r = null;

        foreach ($this->routes as $route) {
            list($regexp,$names,$defaults) = $route;

            if (@preg_match($regexp, $url, $r)) {

                array_shift($r);
                $ii = 0;
                foreach ($r as $found) {
                    if (isset($names[$ii]))
                    $out[$names[$ii]] = $found;
                    elseif (preg_match_all('/(?:\/([^\/]+))/', $found, $r)) {
                        $out['pass'] = $r[1];
                    }
                    $ii++;
                }

                if (is_array($defaults)) {
                    foreach ($defaults as $name => $value) {
                        if (preg_match('/[a-zA-Z_\-]/', $name))
                        $out[$name] = $value;
                        else
                        $out['pass'][] = $value;
                    }
                }
                break;
            }
        }

        return $out;
    }
}

?>