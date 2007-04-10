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
uses('socket', 'set');

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
 * The default values to use for a request
 *
 * @var array
 */
	var $request = array(
	    'method' => 'GET',
	    'uri' => array(
	    	'scheme' => 'http',
			'user' => null,
			'password' => null,
			'host' => null,
			'port' => 80,
			'path' => '/',
			'query' => null
	    ),
	    'auth' => array(
	    	'method' => 'basic'
    		, 'user' => null
    		, 'password' => null    		
	    ),
	    'version' => '1.1',
	    'body' => '',
	    'requestLine' => null,
	    'header' => array(
	        'Connection' => 'close', 'User-Agent' => 'CakePHP'
	    ),
		'raw' => null
	);	
/**
 * The default strucutre for storing the response
 *
 * @var unknown_type
 */
	var $response = array(
	    'raw' => '',
	    'rawHeader' => '',
	    'rawBody' => '',
	    'statusLine' => '',
	    'code' => '',
	    'header' => array(),
	    'body' => ''
	);
	
    var $config = array();

/**
 * Base configuration settings for the socket connection
 *
 * @var array
 */
	var $_baseConfig = array(
		'persistent'	=> false,
		'host'			=> 'localhost',
		'port'			=> 80,
		'scheme'		=> 'http',
		'timeout'		=> 30,
		'authMethod'	=> 'basic',
		'user'			=> null,
		'password'		=> null,
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
		parent::__construct();

		if (is_string($config)) {
			$uri = $this->parseURI($config);
			
			$config = array_intersect_key($uri, $this->_baseConfig);
		}
		$this->config = am($this->_baseConfig, $config);
	}
			
	function parseURI($uri = null, $overwrite = array()) {
		if (is_array($uri)) {
			return $uri;
		}
	
		if (empty($uri)) {
			$uri = $this->config['host'];
		} elseif (strpos($uri, '/') === 0) {
			$uri = $this->config['scheme'].'://'.$this->config['host'].$uri;
		}
		
		/*
		$Validation =& new Validation();
		if (!$Validation->url($uri)) {
			return false;
		}
		*/
			
		if (!is_array($uri)) {
			$uri = parse_url($uri);
		}
		
		$uri = am($uri, $overwrite);
				
		if (!isset($uri['scheme']) || !in_array($uri['scheme'], array('http', 'https'))) {
			return false;
		}
		
		if (isset($uri['query']) && is_string($uri['query'])) {
			$items = explode('&', $uri['query']);
			$query = array();
			
			foreach ($items as $item) {
				if (isset($item[1]) && !empty($item[0])) {
					list($key, $value) = explode('=', $item);
					$query[urldecode($key)] = urldecode($value);
				}
			}
			
			$uri['query'] = $query;
		}
		
		return $uri;
	}
/**
 * Takes a $uri array and turns it into a fully qualified URL string
 *
 * @param array $uri A $uri array, or uses $this->config if left empty
 * @param string $uriTemplate The URI template/format to use
 * @return string A fully qualified URL formated according to $uriTemplate
 */
	function getURI($uri = array(), $uriTemplate = '%scheme://%username:%password@%host:%port/%path?%query')	{
		$uri['path'] = preg_replace('/^\//', null, $uri['path']);
		$uri['query'] = $this->serialize($uri['query']);

		if (empty($uri['query'])) {
			$uriTemplate = str_replace('?%query', null, $uriTemplate);
		}

		if (!isset($uri['username']) || empty($uri['username'])) {
			$uriTemplate = str_replace('%username:%password@', null, $uriTemplate);
		}
		$defaultPorts = array('http' => 80, 'https' => 443);

		if ($defaultPorts[$uri['scheme']] == $uri['port']) {
			$uriTemplate = str_replace(':%port', null, $uriTemplate);
		}
		foreach ($uri as $property => $value) {
			$uriTemplate = str_replace('%'.$property, $value, $uriTemplate);
		}
		return $uriTemplate;
	}
/**
 * Determine the status of, and ability to connect to the current host
 *
 * @todo Ping the current host If $this->path is non-empty and != '/', query the path for a non-404 response
 * @return boolean Success
 */
	function isConnected() {
		return true;
	}
/**
 * Returns the results of a Http request for the contents of a $path (possibly including a query) relative 
 * to the current config['host'] using some optional $options.
 *
 * @return array An array structure containing HTTP headers and response body
 */
	function request($request = array()) {
		$this->reset(false);
	
		$baseRequest = $this->request;
		$this->request = am($baseRequest, $request);
		
		$this->request['uri'] = am($baseRequest['uri'], $this->parseURI($this->request['uri']));
		
		$configMap = array(
			'uri' => array(
				'host' => 'host',
				'scheme' => 'scheme',
				'port' => 'port'
			),
			'auth' => array(
				'authMethod' => 'method',
				'user' => 'user',
				'password' => 'password'
			)
		);

		foreach ($configMap as $type => $mappings) {
			foreach ($mappings as $configKey => $requestKey) {
				if (empty($this->request[$type][$requestKey])) {
					$this->request[$type][$requestKey] = $this->config[$configKey];
				}
				$this->config[$configKey] = $this->request[$type][$requestKey];
			}
		}

		$this->request = $this->generateRequest($this->request);
		$this->connect();
		$this->write($this->request['raw']);

		$rawResponse = null;

		while ($package = $this->read()) {
			$rawResponse = $rawResponse.$package;
		};

		$this->response = $this->parseResponse($rawResponse);
		return $this->response['body'];
	}

	function parseResponse($rawResponse) {
		$response = $this->response;
		$response['raw'] = $rawResponse;

		$headerEnd = strpos($rawResponse, str_repeat($this->headerSeparator, 2));
		$response['rawHeader'] = substr($rawResponse, 0, $headerEnd);
		
		$headerParts = explode($this->headerSeparator, $response['rawHeader']);
		
		$response['statusLine'] = array_shift($headerParts);
		
		if (preg_match('/HTTP\/[1]\.[01] ([0-9]{3}) .+/', $response['statusLine'], $match)) {
			$response['code'] = $match[1];
		}
		
		foreach ($headerParts as $headerPart) {
			list($key, $value) = preg_split('/\: ?/', $headerPart, 2);
			$response['header'][$key] = $value;
		}
		
		$response['rawBody'] = substr($rawResponse, $headerEnd + strlen($this->headerSeparator)*2);
		
		$encoding = $this->getHeader('Transfer-Encoding');
		$response['body'] = $this->decodeBody($response['rawBody'], $encoding);
		
		return $response;
	}

	function decodeBody($rawBody, $encoding = 'chunked') {
		return $rawBody;
	}
/**
 * Returns the header with a given $name respecting the $matchCase flag. 
 *
 * @param unknown_type $name
 * @param unknown_type $matchCase
 * @return unknown
 */
	function getHeader($name = null, $matchCase = false) {
		if ($name === null) {
			return $this->responseHeader;
		}
		if (isset($this->responseHeader[$name])) {
			return $this->responseHeader[$name];
		}
		if ($matchCase == true) {
			return false;
		}
		foreach ($this->response['header'] as $key => $val) {
			if (low($key) == low($name)) {
				return $val;
			}
		}
		return false;		
	}
	
/**
 * Request a URL using the GET method
 *
 * @param array $options
 * @return An array structure containing HTTP headers and response body
 */
	function get($uri = null, $query = array()) {
		$request = array('method' => 'GET');

		if (is_array($uri)) {
			$request = am($request, $uri);
		} else {
			$overwrite = array();
			if (!empty($query)) {
				$overwrite['query'] = $query;
			}
		
			$uri = $this->parseURI($uri, $overwrite);
			if (!empty($uri)) {
				$request['uri'] = $uri;
			}
		}
		return $this->request($request);
	}
/**
 * Request a URL using the POST method
 *
 * @param array $options
 * @return An array structure containing HTTP headers and response body
 */
	function post($uri = null, $body = array()) {	
		$request = array('method' => 'POST');
		
		if (is_array($uri)) {
			$request = am($request, $uri);
		} else {
			$uri = $this->parseURI($uri);

			if (!empty($uri)) {
				$request['uri'] = am($this->request['uri'], $uri);
			}
			$request['body'] = $body;
		}
		return $this->request($request);
	}
/**
 * Request a URL using the PUT method
 *
 * @param array $options
 * @return An array structure containing HTTP headers and response body
 */
	function put() {
		$options['method'] = 'PUT';
		return $this->request($uri, $options);
	}
/**
 * Request a URL using the DELETE method
 *
 * @param array $options
 * @return An array structure containing HTTP headers and response body
 */
	function delete() {
		$options['method'] = 'DELETE';
		return $this->request($uri, $options);
	}

	function generateRequest($request) {
		if (empty($request['requestLine'])) {
			$request['requestLine'] = $request['method'].' '.$this->getURI($request['uri'], '/%path?%query').' HTTP/1.1';
		}
	
  		
		if (!empty($request['body'])) {
			if (is_array($request['body'])) {
				$request['body'] = $this->serialize($request['body']);
			}
			 			
			if (!isset($request['header']['Content-Type'])) {
				$request['header']['Content-Type']	= 'application/x-www-form-urlencoded';
			}
		}
		$request['header'] = $this->buildHeader($request);

		$request['raw'] = $request['requestLine'].$this->headerSeparator;
		$request['raw'] .= $this->serializeHeader($request['header']);
		$request['raw'] .= $request['body'];

		return $request;
	}
/**
 * Takes an array of items and serializes them for a GET/POST request
 *
 * @param array $items An associative array of items to serialize
 * @return string A string ready to be sent via HTTP
 * @todo Implement http_build_query for php5 and an alternative solution for php4, see http://us2.php.net/http_build_query
 */
	function serialize($items) {
		return substr(Router::queryString($items), 1);
	}

	function serializeHeader($headerParts) {
		foreach ($headerParts as $key => $value) {
			$header[] = $key.': '.$value;
		}
		return join($this->headerSeparator, $header).str_repeat($this->headerSeparator, 2);
	}	

	function buildHeader($request) {
		$header = array();
		$headerMap = array(
			'uri.host' => 'Host'
		);
		
		foreach ($headerMap as $fromPath => $to) {
			$header[$to] = Set::extract($request, $fromPath);
		}
		if (!empty($request['body']) && !isset($request['header']['Content-Length'])) {
			$header['Content-Length'] = strlen($request['body']);
		}
		return am($header, $request['header']);
	}
/**
 * Resets the state of this socket (automatically called before any request)
 *
 */
	function reset($full = true) {
		static $classVars = array()	;
		
		if (empty($classVars)) {
			$classVars = get_class_vars(__CLASS__);
		}
		
		if ($full == false) {
			$this->request = $classVars['request'];
			$this->response = $classVars['response'];
			return true;
		}

		foreach ($classVars as $var => $defaultVal) {
			$this->{$var} = $defaultVal;
		}
		return true;
	}
}

?>