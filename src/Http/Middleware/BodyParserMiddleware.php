<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use Cake\Http\Exception\BadRequestException;
use Cake\Utility\Exception\XmlException;
use Cake\Utility\Xml;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Parse encoded request body data.
 *
 * Enables JSON and XML request payloads to be parsed into the request's
 * Provides CSRF protection & validation.
 *
 * You can also add your own request body parsers using the `addParser()` method.
 */
class BodyParserMiddleware
{
    /**
     * Registered Parsers
     *
     * @var array
     */
    protected $parsers = [];

    /**
     * The HTTP methods to parse data on.
     *
     * @var array
     */
    protected $methods = ['PUT', 'POST', 'PATCH', 'DELETE'];

    /**
     * Constructor
     *
     * ### Options
     *
     * - `json` Set to false to disable json body parsing.
     * - `xml` Set to true to enable XML parsing. Defaults to false, as XML
     *   handling requires more care than JSON does.
     * - `methods` The HTTP methods to parse on. Defaults to PUT, POST, PATCH DELETE.
     *
     * @param array $options The options to use. See above.
     */
    public function __construct(array $options = [])
    {
        $options += ['json' => true, 'xml' => false, 'methods' => null];
        if ($options['json']) {
            $this->addParser(
                ['application/json', 'text/json'],
                [$this, 'decodeJson']
            );
        }
        if ($options['xml']) {
            $this->addParser(
                ['application/xml', 'text/xml'],
                [$this, 'decodeXml']
            );
        }
        if ($options['methods']) {
            $this->setMethods($options['methods']);
        }
    }

    /**
     * Set the HTTP methods to parse request bodies on.
     *
     * @param array $methods The methods to parse data on.
     * @return $this
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * Add a parser.
     *
     * Map a set of content-type header values to be parsed by the $parser.
     *
     * ### Example
     *
     * An naive CSV request body parser could be built like so:
     *
     * ```
     * $parser->addParser(['text/csv'], function ($body) {
     *   return str_getcsv($body);
     * });
     * ```
     *
     * @param array $types An array of content-type header values to match. eg. application/json
     * @param callable $parser The parser function. Must return an array of data to be inserted
     *   into the request.
     * @return $this
     */
    public function addParser(array $types, callable $parser)
    {
        foreach ($types as $type) {
            $type = strtolower($type);
            $this->parsers[$type] = $parser;
        }

        return $this;
    }

    /**
     * Apply the middleware.
     *
     * Will modify the request adding a parsed body if the content-type is known.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Cake\Http\Response A response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        if (!in_array($request->getMethod(), $this->methods)) {
            return $next($request, $response);
        }
        list($type) = explode(';', $request->getHeaderLine('Content-Type'));
        $type = strtolower($type);
        if (!isset($this->parsers[$type])) {
            return $next($request, $response);
        }

        $parser = $this->parsers[$type];
        $result = $parser($request->getBody()->getContents());
        if (!is_array($result)) {
            throw new BadRequestException();
        }
        $request = $request->withParsedBody($result);

        return $next($request, $response);
    }

    /**
     * Decode JSON into an array.
     *
     * @param string $body The request body to decode
     * @return array
     */
    protected function decodeJson($body)
    {
        return json_decode($body, true);
    }

    /**
     * Decode XML into an array.
     *
     * @param string $body The request body to decode
     * @return array
     */
    protected function decodeXml($body)
    {
        try {
            $xml = Xml::build($body, ['return' => 'domdocument', 'readFile' => false]);
            // We might not get child nodes if there are nested inline entities.
            if ((int)$xml->childNodes->length > 0) {
                return Xml::toArray($xml);
            }

            return [];
        } catch (XmlException $e) {
            return [];
        }
    }
}
