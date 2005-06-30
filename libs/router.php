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
  * Purpose: Router
  * Parses the request URL into controller, action, and parameters.
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

uses('object', 'narray');

/**
  * Parses the request URL into controller, action, and parameters.
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class Router extends Object {

/**
  * Array of routes
  *
  * @var array
  */
	var $routes = array();
	
/**
  * TODO: Better description. Returns this object's routes array. Returns false if there are no routes available.
  *
  * @param string $route An empty string, or a route string "/"
  * @param array $default NULL or an array describing the default route
  * @see routes
  * @return array Array of routes
  */
	function connect ($route, $default=null) {
		$parsed = $names = array ();

		$r = null;
		if (($route == '') || ($route == '/')) {
			$regexp = '/^[\/]*$/';
			$this->routes[] = array($route, $regexp, array(), $default);
		}
		else {
			$elements = array();
			foreach (explode('/', $route) as $element)
				if (trim($element)) $elements[] = $element;

			if (!count($elements))
				return false;

			foreach ($elements as $element) {
				if (preg_match('/^:(.+)$/', $element, $r)) {
						$parsed[] = '(?:\/([^\/]+))?';
					   $names[] = $r[1];
					}
					elseif (preg_match('/^\*$/', $element, $r)) {
						$parsed[] = '(?:\/(.*))?';
					}
					else {
						$parsed[] = '/'.$element;
					}
				}
				$regexp = '#^'.join('', $parsed).'[\/]*$#';
			$this->routes[] = array($route, $regexp, $names, $default);
			}

		return $this->routes;
	}

/**
  * TODO: Better description. Returns an array of routes.
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
			$url = '/'.$url;
		}
		
		$out = array();
		$r = null;

		$default_route = array
		(
			'/:controller/:action/* (default)',
			"#^(?:\/(?:([a-z0-9_\-]+)(?:\/([a-z0-9_\-]+)(?:\/(.*))?)?))[\/]*$#",
			array('controller', 'action'),
			array()
		);

		$this->routes[] = $default_route;

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
					$out[$name] = null;

				$ii = 0;

				if (is_array($defaults)) 
				{
					foreach ($defaults as $name=>$value) 
					{
						if (preg_match('#[a-z_\-]#i', $name))
							$out[$name] = $value;
						else
							$out['pass'][] = $value;
					}
				}

				foreach ($r as $found) {
					// if $found is a named url element (i.e. ':action')
					if (isset($names[$ii])) 
					{
						$out[$names[$ii]] = $found;
					}
					// unnamed elements go in as 'pass'
					else 
					{
						$pass = new Narray(explode('/', $found));
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