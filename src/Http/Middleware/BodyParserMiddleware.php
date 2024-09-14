<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware;

use Cake\Http\Exception\BadRequestException;
use Cake\Utility\Exception\XmlException;
use Cake\Utility\Xml;
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Parse encoded request body data.
 *
 * Enables JSON and XML request payloads to be parsed into the request's body.
 * You can also add your own request body parsers using the `addParser()` method.
 */
class BodyParserMiddleware implements MiddlewareInterface
{
    /**
     * Registered Parsers
     *
     * @var array<\Closure>
     */
    protected array $parsers = [];

    /**
     * The HTTP methods to parse data on.
     *
     * @var list<string>
     */
    protected array $methods = ['PUT', 'POST', 'PATCH', 'DELETE'];

    /**
     * Constructor
     *
     * ### Options
     *
     * - `json` Set to false to disable JSON body parsing.
     * - `xml` Set to true to enable XML parsing. Defaults to false, as XML
     *   handling requires more care than JSON does.
     * - `methods` The HTTP methods to parse on. Defaults to PUT, POST, PATCH DELETE.
     *
     * @param array<string, mixed> $options The options to use. See above.
     */
    public function __construct(array $options = [])
    {
        $options += ['json' => true, 'xml' => false, 'methods' => null];
        if ($options['json']) {
            $this->addParser(
                ['application/json', 'text/json'],
                $this->decodeJson(...)
            );
        }
        if ($options['xml']) {
            $this->addParser(
                ['application/xml', 'text/xml'],
                $this->decodeXml(...)
            );
        }
        if ($options['methods']) {
            $this->setMethods($options['methods']);
        }
    }

    /**
     * Set the HTTP methods to parse request bodies on.
     *
     * @param list<string> $methods The methods to parse data on.
     * @return $this
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * Get the HTTP methods to parse request bodies on.
     *
     * @return list<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
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
     * @param list<string> $types An array of content-type header values to match. eg. application/json
     * @param \Closure $parser The parser function. Must return an array of data to be inserted
     *   into the request.
     * @return $this
     */
    public function addParser(array $types, Closure $parser)
    {
        foreach ($types as $type) {
            $type = strtolower($type);
            $this->parsers[$type] = $parser;
        }

        return $this;
    }

    /**
     * Get the current parsers
     *
     * @return array<\Closure>
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    /**
     * Apply the middleware.
     *
     * Will modify the request adding a parsed body if the content-type is known.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!in_array($request->getMethod(), $this->methods, true)) {
            return $handler->handle($request);
        }
        [$type] = explode(';', $request->getHeaderLine('Content-Type'));
        $type = strtolower($type);
        if (!isset($this->parsers[$type])) {
            return $handler->handle($request);
        }

        $parser = $this->parsers[$type];
        $result = $parser($request->getBody()->getContents());
        if (!is_array($result)) {
            throw new BadRequestException();
        }
        $request = $request->withParsedBody($result);

        return $handler->handle($request);
    }

    /**
     * Decode JSON into an array.
     *
     * @param string $body The request body to decode
     * @return array|null
     */
    protected function decodeJson(string $body): ?array
    {
        if ($body === '') {
            return [];
        }
        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return (array)$decoded;
    }

    /**
     * Decode XML into an array.
     *
     * @param string $body The request body to decode
     * @return array
     */
    protected function decodeXml(string $body): array
    {
        try {
            $xml = Xml::build($body, ['return' => 'domdocument', 'readFile' => false]);
            // We might not get child nodes if there are nested inline entities.
            /** @var \DOMNodeList $domNodeList */
            $domNodeList = $xml->childNodes;
            if ((int)$domNodeList->length > 0) {
                return Xml::toArray($xml);
            }

            return [];
        } catch (XmlException) {
            return [];
        }
    }
}
