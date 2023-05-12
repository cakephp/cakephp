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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use function Laminas\Diactoros\normalizeServer;
use function Laminas\Diactoros\normalizeUploadedFiles;

/**
 * Factory for making ServerRequest instances.
 *
 * This adds in CakePHP specific behavior to populate the basePath and webroot
 * attributes. Furthermore the Uri's path is corrected to only contain the
 * 'virtual' path for the request.
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * Create a request from the supplied superglobal values.
     *
     * If any argument is not supplied, the corresponding superglobal value will
     * be used.
     *
     * @param array|null $server $_SERVER superglobal
     * @param array|null $query $_GET superglobal
     * @param array|null $parsedBody $_POST superglobal
     * @param array|null $cookies $_COOKIE superglobal
     * @param array|null $files $_FILES superglobal
     * @return \Cake\Http\ServerRequest
     * @throws \InvalidArgumentException for invalid file values
     */
    public static function fromGlobals(
        ?array $server = null,
        ?array $query = null,
        ?array $parsedBody = null,
        ?array $cookies = null,
        ?array $files = null
    ): ServerRequest {
        $server = normalizeServer($server ?? $_SERVER);
        ['uri' => $uri, 'base' => $base, 'webroot' => $webroot] = UriFactory::marshalUriAndBaseFromSapi($server);

        $sessionConfig = (array)Configure::read('Session') + [
            'defaults' => 'php',
            'cookiePath' => $webroot,
        ];
        $session = Session::create($sessionConfig);

        $request = new ServerRequest([
            'environment' => $server,
            'uri' => $uri,
            'cookies' => $cookies ?? $_COOKIE,
            'query' => $query ?? $_GET,
            'webroot' => $webroot,
            'base' => $base,
            'session' => $session,
            'input' => $server['CAKEPHP_INPUT'] ?? null,
        ]);

        $request = static::marshalBodyAndRequestMethod($parsedBody ?? $_POST, $request);
        // This is required as `ServerRequest::scheme()` ignores the value of
        // `HTTP_X_FORWARDED_PROTO` unless `trustProxy` is enabled, while the
        // `Uri` instance intially created always takes values of `HTTP_X_FORWARDED_PROTO`
        // into account.
        $uri = $request->getUri()->withScheme($request->scheme());
        $request = $request->withUri($uri, true);

        return static::marshalFiles($files ?? $_FILES, $request);
    }

    /**
     * Sets the REQUEST_METHOD environment variable based on the simulated _method
     * HTTP override value. The 'ORIGINAL_REQUEST_METHOD' is also preserved, if you
     * want the read the non-simulated HTTP method the client used.
     *
     * Request body of content type "application/x-www-form-urlencoded" is parsed
     * into array for PUT/PATCH/DELETE requests.
     *
     * @param array $parsedBody Parsed body.
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @return \Cake\Http\ServerRequest
     */
    protected static function marshalBodyAndRequestMethod(array $parsedBody, ServerRequest $request): ServerRequest
    {
        $method = $request->getMethod();
        $override = false;

        if (
            in_array($method, ['PUT', 'DELETE', 'PATCH'], true) &&
            str_starts_with((string)$request->contentType(), 'application/x-www-form-urlencoded')
        ) {
            $data = (string)$request->getBody();
            parse_str($data, $parsedBody);
        }
        if ($request->hasHeader('X-Http-Method-Override')) {
            $parsedBody['_method'] = $request->getHeaderLine('X-Http-Method-Override');
            $override = true;
        }

        $request = $request->withEnv('ORIGINAL_REQUEST_METHOD', $method);
        if (isset($parsedBody['_method'])) {
            $request = $request->withEnv('REQUEST_METHOD', $parsedBody['_method']);
            unset($parsedBody['_method']);
            $override = true;
        }

        if (
            $override &&
            !in_array($request->getMethod(), ['PUT', 'POST', 'DELETE', 'PATCH'], true)
        ) {
            $parsedBody = [];
        }

        return $request->withParsedBody($parsedBody);
    }

    /**
     * Process uploaded files and move things onto the parsed body.
     *
     * @param array $files Files array for normalization and merging in parsed body.
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @return \Cake\Http\ServerRequest
     */
    protected static function marshalFiles(array $files, ServerRequest $request): ServerRequest
    {
        $files = normalizeUploadedFiles($files);
        $request = $request->withUploadedFiles($files);

        $parsedBody = $request->getParsedBody();
        if (!is_array($parsedBody)) {
            return $request;
        }

        $parsedBody = Hash::merge($parsedBody, $files);

        return $request->withParsedBody($parsedBody);
    }

    /**
     * Create a new server request.
     *
     * Note that server-params are taken precisely as given - no parsing/processing
     * of the given values is performed, and, in particular, no attempt is made to
     * determine the HTTP method or URI, which must be provided explicitly.
     *
     * @param string $method The HTTP method associated with the request.
     * @param \Psr\Http\Message\UriInterface|string $uri The URI associated with the request. If
     *     the value is a string, the factory MUST create a UriInterface
     *     instance based on it.
     * @param array $serverParams Array of SAPI parameters with which to seed
     *     the generated request instance.
     * @return \Psr\Http\Message\ServerRequestInterface
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        $serverParams['REQUEST_METHOD'] = $method;
        $options = ['environment' => $serverParams];

        if (is_string($uri)) {
            $uri = (new UriFactory())->createUri($uri);
        }
        $options['uri'] = $uri;

        return new ServerRequest($options);
    }
}
