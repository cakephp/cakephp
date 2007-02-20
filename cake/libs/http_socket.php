<?php
/* SVN FILE: $Id$ */
/**
 * HTTP Socket connection class.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 1.2.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses('socket');

/**
 * Cake network socket connection class.
 *
 * Core base class for network communication.
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class HttpSocket extends CakeSocket {
/**
 * Object description
 *
 * @var string
 */
	var $description = 'HTTP-based DataSource Interface';

/**
 * URI path (not including host name) to current HTTP resource
 *
 * @var string
 */
	var $path = '/';

/**
 * URL query parameters
 *
 * @var array
 */
	var $query = array();

/**
 * Data to send in a POST or PUT request.  Accepts a URL-encoded string, an array, an object,
 * or an XML object
 *
 * @var mixed
 */
	var $data = array();
/**
 * Base configuration settings for the socket connection
 *
 * @var array
 */
	var $_baseConfig = array(
		'persistent'	=> false,
		'host'			=> 'localhost',
		'port'			=> 80,
		'login'			=> null,
		'password'		=> null,
		'timeout'		=> 30
	);
/**
 * The line break type to use for building the header. According to "RFC 2616 - Hypertext Transfer
 * Protocol -- HTTP/1.1", clients MUST accept CRLF, LF and CR if used consistently.
 *
 * @var string
 */
	var $headerSeparator = "\r\n";
/**
 * Called when creating a new instance of this object
 *
 * @param array $config Socket configuration, which will be merged with the base configuration
 */
	function __construct($config = array()) {
		// Execute CakeSocket::__construct
		parent::__construct();
		$config = (array)$config;

		// This is used to check if a string was passed to $config (and turned into an array above)
		if (isset($config[0]) && !isset($config['host'])) {
			// If so use it's value as the 'host' property
			$config['host'] = $config[0];
			unset($config[0]);
		}

		// Merge custom user $config over the HttpSocket::_baseConfig
		$this->config = am($this->_baseConfig, $config);
		// Set the unique resource identifier
		$this->setURI();
	}
/**
 * Parses the current URI string
 *
 * @param $uri A string or array constructed by parse_url() containing the URI or URI information
 * @return boolean False if $uri (or $this->config['host']) is not a valid, fully qualified URL
 */
	function setURI($uri = null) {
		// If no $uri was proivded
		if (empty($uri)) {
			// Use our current config['host'] value
			$uri = $this->config['host'];
		}
		
		/**
		 * @todo Generic function that can validate fully qualified URL's or just hosts
		 */
		/*
		if (!Validation::url($uri)) {
			return false;
		}
		*/
		
		// If we were not given an array as $uri
		if (!is_array($uri)) {
			// Parse the $uri string into an array using php's parse_url function
			$uri = parse_url($uri);
		}
		
		// If no, or no valid $uri scheme (http/https) was provided
		if (!isset($uri['scheme']) || !in_array($uri['scheme'], array('http', 'https'))) {
			// Return 'false' to indicate this function has failed
			return false;
		}
		
		// Loop through all $uri parts
		foreach ($uri as $key => $val) {
			// Use a special treatment for each one of them
			switch ($key) {
				case 'host':
					// Directly map the 'host' $key to config['host']
					$this->config['host'] = $val;
				break;
				case 'scheme':
					// Directly map the 'scheme' $key to config['scheme']
					$this->config['scheme'] = $uri['scheme'];
					break;
				case 'port':
					// Directly map the 'port' $key to config['port']
					$this->config['port'] = $val;
				break;
				case 'user':
				case 'username':
					// Map the 'user' / 'username' $key to config['username']
					$this->config['username'] = $val;
				break;
				case 'pass':
				case 'password':
					// Map the 'pass' / 'password' $key to config['password']
					$this->config['password'] = $val;
				break;
				case 'query':
					// Reset our query property
					$this->query = array();
					
					// Extract all query items using the '&' separator
					$items = explode('&', $val);
					
					// Loop through all $items
					foreach ($items as $item) {
						// Extract the query key / value of each $item using the '=' separator
						list($qKey, $qValue) = explode('=', $item);
						
						// Map url-decoded query items to our query property
						$this->query[urldecode($qKey)] = urldecode($qValue);
					}
				break;
				case 'path':
					// Map the 'path' $key to our 'path' property
					$this->path = $val;
				break;
			}
		}
		return true;
	}
/**
 * Takes a $uri array and turns it into a fully qualified URL string
 *
 * @param array $uri A $uri array, or uses $this->config if left empty
 * @param string $uriTemplate The URI template/format to use
 * @return string A fully qualified URL formated according to $uriTemplate
 */
	function getURI($uri = array(), $uriTemplate = '%scheme://%username:%password@%host:%port/%path?%query')	{
		// If no $uri was provided
		if (empty($uri)) {
			// Use our config property and merge our local query/path over it as well
			$uri = am($this->config, array('query' => $this->query, 'path' => $this->path));
		} else {
			// Otherwise merge the provided $uri array over our local query/path property
			$uri = am(array('query' => $this->query, 'path' => $this->path), $uri);
		}

		// Make sure the path does not start with a '/'
		$uri['path'] = preg_replace('/^\//', null, $uri['path']);

		// Serialize our $uri['query']
		$uri['query'] = $this->serialize($uri['query']);

		// If no query was provided
		if (empty($uri['query'])) {
			// Strip the query part from our $uriTemplate
			$uriTemplate = str_replace('?%query', null, $uriTemplate);
		}

		// If no username was provided
		if (!isset($uri['username']) || empty($uri['username'])) {
			// Strip that from our $uriTemplate as well
			$uriTemplate = str_replace('%username:%password@', null, $uriTemplate);
		}
		// A map for the default ports of http and https
		$defaultPorts = array('http' => 80, 'https' => 443);

		// If our $uri uses the default port for it's scheme
		if ($defaultPorts[$uri['scheme']] == $uri['port']) {
			// Strip the port part from the $uriTemplate
			$uriTemplate = str_replace(':%port', null, $uriTemplate);
		}
		// Loop through all $property's in our $uri
		foreach ($uri as $property => $value) {
			// And fill in the $uriTemplate with their $value's
			$uriTemplate = str_replace('%'.$property, $value, $uriTemplate);
		}
		// Return the populated $uriTemplate which is not a fully qualified URL
		return $uriTemplate;
	}
/**
 * Determine the status of, and ability to connect to the current host
 *
 * @todo Ping the current host If $this->path is non-empty and != '/', query the path for a non-404 response
 * @return boolean Success
 */
	function isConnected() {
		// Return true until implemented
		return true;
	}
/**
 * Returns the results of a Http request for the contents of a $path (possibly including a query) relative 
 * to the current config['host'] using some optional $options.
 *
 * @return array An array structure containing HTTP headers and response body
 */
 	function request($path = null, $options = array()) {
		// If we were given an array as the first parameter
		if (is_array($path)) {
			// Assume it's a convenience usage of the $options parameter
			$options = $path;
		} elseif (!empty($path)) {
			/**
			 * @todo check if somebody might have passed a fully qualified URL as a $path and don't prepent the scheme/host in those cases
			 */			
			// Set's the URI for this request
			$this->setURI($this->config['scheme'].'://'.$this->config['host'].$path);
		}

 		// If our $options contain a data property
 		if (!empty($options['data'])) {
 			// Set this to be our local data property
 			$this->data = $options['data'];
 		}
		
		// Merge the user provided $options over some assumed defaults for them (conventions over configuration)
 		$options = am(array(
 			'method'	=> ife(isset($options['data']) || !empty($this->data), 'POST', 'GET'),
 			'data' => $this->data,
 			'type' => 'xml',
 			'host' => $this->config['host'],
 			'connection' => 'close'
 		), $options);

		// Build the header for this request using our given $options
 		$header = $this->buildHeader($options);

 		// Connect to the current host
 		$this->connect();

 		// Send him the built header
		$this->write($header);

		// Start with an empty $response variable
		$response = null;

		// Fetch one $package after the other until CakeSocket::read returns false and we are done
		while ($package = $this->read()) {
			// Append the $package to our $response string
			$response = $response.$package;
		};

		/**
		 * @todo Parse the returned headers and return an array structure were those are seperate from the response contents
		 */
		// Return the $response data we got from the server
		return array($response);
 	}
/**
 * Request a URL using the GET method
 *
 * @param array $options
 * @return An array structure containing HTTP headers and response body
 */
 	function get($path = null, $options = array()) {
 		// Make sure 'GET' is used for this request
 		$options['method'] = 'GET';
 		// Issue the request and return it's results
		return $this->request($path, $options);
 	}
/**
 * Request a URL using the POST method
 *
 * @param array $options
 * @return An array structure containing HTTP headers and response body
 */
 	function post() {
 		// Make sure 'POST' is used for this request
 		$options['method'] = 'POST';
 		// Issue the request and return it's results
		return $this->request($path, $options);
 	}
/**
 * Request a URL using the PUT method
 *
 * @param array $options
 * @return An array structure containing HTTP headers and response body
 */
 	function put() {
 		// Make sure 'PUT' is used for this request
 		$options['method'] = 'PUT';
 		// Issue the request and return it's results
		return $this->request($path, $options);
 	}
/**
 * Request a URL using the DELETE method
 *
 * @param array $options
 * @return An array structure containing HTTP headers and response body
 */
 	function delete() {
 		// Make sure 'DELETE' is used for this request
 		$options['method'] = 'DELETE';
 		// Issue the request and return it's results
		return $this->request($path, $options);
 	}
/**
 * Takes an array of items and serializes them for a GET/POST request
 *
 * @param array $items An associative array of items to serialize
 * @return string A string ready to be sent via HTTP
 * @todo Implement http_build_query for php5 and an alternative solution for php4, see http://us2.php.net/http_build_query
 */
	function serialize($items) {
		// Start a new array
		$serializedItems = array();

		// Loop through all $items to serialize
		foreach ($items as $key => $value) {
			// Urlencode them into the array of $serializedItems
			$serializedItems[] = urlencode($key).'='.urlencode($value);
		}
		
		// Glue the items together using the '&' separator and return the results
		return join('&', $serializedItems);	
	}
/**
 * Builds a HTTP header string for the given $options
 *
 * @param array $options An array of options to use for building the header
 * @return string An HTTP header string
 * @todo Determine how to handle Content-Length:
 */
	function buildHeader($options) {
		// Use the __buildHeader function to get an array of the parts of this header
		$headerParts = $this->__buildHeader($options);
		// The first part is the command that also contains the method for the request
		$header = array($headerParts[0]);
		// Which we can then remove from the $headerParts
		unset($headerParts[0]);

		// In order to be able to move through the rest of them by their key/value
		foreach ($headerParts as $key => $value) {
			// And to add them to the $header array one by one
			$header[] = $key.': '.$value;
		}
		// Glues the $header parts together using the headerSeparator property and appends the header ending
		return join($this->headerSeparator, $header).str_repeat($this->headerSeparator, 2);
	}
/**
 * Builds a HTTP header array using some given $options
 *
 * @param array $options An array of options to use for building the header
 * @return array An associative array containing the built header
 * @todo Determine how to handle Content-Length:
 */
	function __buildHeader($options) {
		// Generate the first request comment of the header
		$header[0] = $options['method']." ".$this->getURI(null, '/%path?%query')." HTTP/1.1";
		
		// The following $options values don't need any further parsing and can be mapped directly
		$mapDirectly = array('host', 'connection');
		foreach ($mapDirectly as $key) {
			// Determine the HTTP equilivent of our $options $ley
			$httpKey = str_replace(' ', '-', Inflector::camelize($key));
			
			// Map the our options keys to the http header ones
			$header[$httpKey] = $options[$key];
		}
		// Return the generated header
		return $header;
	}
}

?>