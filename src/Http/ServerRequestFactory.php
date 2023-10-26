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
use Cake\Http\Uri as CakeUri;
use Cake\Utility\Hash;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use function Laminas\Diactoros\marshalHeadersFromSapi;
use function Laminas\Diactoros\normalizeServer;
use function Laminas\Diactoros\normalizeUploadedFiles;

/**
 * Factory for making ServerRequest instances.
 *
 * This adds in CakePHP specific behavior to populate the basePath and webroot
 * attributes. Furthermore, the Uri's path is corrected to only contain the
 * 'virtual' path for the request.
 */
abstract class ServerRequestFactory implements ServerRequestFactoryInterface
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
        $server = normalizeServer($server ?: $_SERVER);
        $uri = static::createUri($server);

        $webroot = '';
        $base = '';
        if ($uri instanceof CakeUri) {
            // Unwrap our shim for base and webroot.
            // For 5.x we should change the interface on createUri() to return a
            // tuple of [$uri, $base, $webroot] and remove the wrapper.
            $webroot = $uri->getWebroot();
            $base = $uri->getBase();
            $uri->getUri();
        }

        $sessionConfig = (array)Configure::read('Session') + [
            'defaults' => 'php',
            'cookiePath' => $webroot,
        ];
        $session = Session::create($sessionConfig);

        $request = new ServerRequest([
            'environment' => $server,
            'uri' => $uri,
            'cookies' => $cookies ?: $_COOKIE,
            'query' => $query ?: $_GET,
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
            strpos((string)$request->contentType(), 'application/x-www-form-urlencoded') === 0
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

        if (Configure::read('App.uploadedFilesAsObjects', true)) {
            $parsedBody = Hash::merge($parsedBody, $files);
        } else {
            // Make a flat map that can be inserted into body for BC.
            $fileMap = Hash::flatten($files);
            foreach ($fileMap as $key => $file) {
                $error = $file->getError();
                $tmpName = '';
                if ($error === UPLOAD_ERR_OK) {
                    $tmpName = $file->getStream()->getMetadata('uri');
                }
                $parsedBody = Hash::insert($parsedBody, (string)$key, [
                    'tmp_name' => $tmpName,
                    'error' => $error,
                    'name' => $file->getClientFilename(),
                    'type' => $file->getClientMediaType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return $request->withParsedBody($parsedBody);
    }

    /**
     * Marshal a Uri instance based on the values present in the $_SERVER array and headers.
     *
     * Note: Deprecated inside diactoros: This function is deprecated as of 2.11.1, and will be removed in
     *     3.0.0. As of 2.11.1, it is no longer used internally. So this is a copy here.
     *
     * @param array $server SAPI parameters
     * @param array $headers HTTP request headers
     * @return \Laminas\Diactoros\Uri
     */
    protected static function marshalUriInstanceFromSapi(array $server, array $headers): Uri
    {
        /**
         * Retrieve a header value from an array of headers using a case-insensitive lookup.
         *
         * @param array $headers Key/value header pairs
         * @param mixed $default Default value to return if header not found
         * @return mixed
         */
        $getHeaderFromArray = static function (string $name, array $headers, $default = null) {
            $header = strtolower($name);
            $headers = array_change_key_case($headers, CASE_LOWER);
            if (array_key_exists($header, $headers)) {
                return is_array($headers[$header]) ? implode(', ', $headers[$header]) : $headers[$header];
            }

            return $default;
        };

        /**
         * Marshal the host and port from HTTP headers and/or the PHP environment.
         *
         * @return array Array of two items, host and port, in that order (can be
         *     passed to a list() operation).
         */
        $marshalHostAndPort = static function (array $headers, array $server) use ($getHeaderFromArray): array {
            /**
             * @param string|array $host
             * @return array Array of two items, host and port, in that order (can be
             *     passed to a list() operation).
             */
            $marshalHostAndPortFromHeader = static function ($host) {
                if (is_array($host)) {
                    $host = implode(', ', $host);
                }

                $port = null;

                // works for regname, IPv4 & IPv6
                if (preg_match('|\:(\d+)$|', $host, $matches)) {
                    $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
                    $port = (int)$matches[1];
                }

                return [$host, $port];
            };

            /**
             * @return array Array of two items, host and port, in that order (can be
             *     passed to a list() operation).
             */
            $marshalIpv6HostAndPort = static function (array $server, ?int $port): array {
                $host = '[' . $server['SERVER_ADDR'] . ']';
                $port = $port ?: 80;
                if ($port . ']' === substr($host, strrpos($host, ':') + 1)) {
                    // The last digit of the IPv6-Address has been taken as port
                    // Unset the port so the default port can be used
                    $port = null;
                }

                return [$host, $port];
            };

            static $defaults = ['', null];

            $forwardedHost = $getHeaderFromArray('x-forwarded-host', $headers, false);
            if ($forwardedHost !== false) {
                return $marshalHostAndPortFromHeader($forwardedHost);
            }

            $host = $getHeaderFromArray('host', $headers, false);
            if ($host !== false) {
                return $marshalHostAndPortFromHeader($host);
            }

            if (! isset($server['SERVER_NAME'])) {
                return $defaults;
            }

            $host = $server['SERVER_NAME'];
            $port = isset($server['SERVER_PORT']) ? (int)$server['SERVER_PORT'] : null;

            if (
                ! isset($server['SERVER_ADDR'])
                || ! preg_match('/^\[[0-9a-fA-F\:]+\]$/', $host)
            ) {
                return [$host, $port];
            }

            // Misinterpreted IPv6-Address
            // Reported for Safari on Windows
            return $marshalIpv6HostAndPort($server, $port);
        };

        /**
         * Detect the path for the request
         *
         * Looks at a variety of criteria in order to attempt to autodetect the base
         * request path, including:
         *
         * - IIS7 UrlRewrite environment
         * - REQUEST_URI
         * - ORIG_PATH_INFO
         *
         * From Laminas\Http\PhpEnvironment\Request class
         */
        $marshalRequestPath = static function (array $server): string {
            // IIS7 with URL Rewrite: make sure we get the unencoded url
            // (double slash problem).
            $iisUrlRewritten = $server['IIS_WasUrlRewritten'] ?? null;
            $unencodedUrl = $server['UNENCODED_URL'] ?? '';
            if ($iisUrlRewritten === '1' && ! empty($unencodedUrl)) {
                return $unencodedUrl;
            }

            $requestUri = $server['REQUEST_URI'] ?? null;

            if ($requestUri !== null) {
                return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
            }

            $origPathInfo = $server['ORIG_PATH_INFO'] ?? null;
            if (empty($origPathInfo)) {
                return '/';
            }

            return $origPathInfo;
        };

        $uri = new Uri('');

        // URI scheme
        $scheme = 'http';
        $marshalHttpsValue = static function ($https): bool {
            if (is_bool($https)) {
                return $https;
            }

            if (! is_string($https)) {
                throw new \Laminas\Diactoros\Exception\InvalidArgumentException(sprintf(
                    'SAPI HTTPS value MUST be a string or boolean; received %s',
                    gettype($https)
                ));
            }

            return strtolower($https) === 'on';
        };
        if (array_key_exists('HTTPS', $server)) {
            $https = $marshalHttpsValue($server['HTTPS']);
        } elseif (array_key_exists('https', $server)) {
            $https = $marshalHttpsValue($server['https']);
        } else {
            $https = false;
        }

        if (
            $https
            || strtolower($getHeaderFromArray('x-forwarded-proto', $headers, '')) === 'https'
        ) {
            $scheme = 'https';
        }
        $uri = $uri->withScheme($scheme);

        // Set the host
        [$host, $port] = $marshalHostAndPort($headers, $server);
        if (! empty($host)) {
            $uri = $uri->withHost($host);
            if (! empty($port)) {
                $uri = $uri->withPort($port);
            }
        }

        // URI path
        $path = $marshalRequestPath($server);

        // Strip query string
        $path = explode('?', $path, 2)[0];

        // URI query
        $query = '';
        if (isset($server['QUERY_STRING'])) {
            $query = ltrim($server['QUERY_STRING'], '?');
        }

        // URI fragment
        $fragment = '';
        if (strpos($path, '#') !== false) {
            $parts = explode('#', $path, 2);
            assert(count($parts) >= 2);
            [$path, $fragment] = $parts;
        }

        return $uri
            ->withPath($path)
            ->withFragment($fragment)
            ->withQuery($query);
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
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        $serverParams['REQUEST_METHOD'] = $method;
        $options = ['environment' => $serverParams];

        if ($uri instanceof UriInterface) {
            $options['uri'] = $uri;
        } else {
            $options['url'] = $uri;
        }

        return new ServerRequest($options);
    }

    /**
     * Create a new Uri instance from the provided server data.
     *
     * @param array $server Array of server data to build the Uri from.
     *   $_SERVER will be added into the $server parameter.
     * @return \Psr\Http\Message\UriInterface New instance.
     */
    public static function createUri(array $server = []): UriInterface
    {
        $server += $_SERVER;
        $server = normalizeServer($server);
        $headers = marshalHeadersFromSapi($server);

        return static::marshalUriFromSapi($server, $headers);
    }

    /**
     * Build a UriInterface object.
     *
     * Add in some CakePHP specific logic/properties that help
     * preserve backwards compatibility.
     *
     * @param array $server The server parameters.
     * @param array $headers The normalized headers
     * @return \Cake\Http\Uri A constructed Uri
     */
    protected static function marshalUriFromSapi(array $server, array $headers): UriInterface
    {
        $uri = static::marshalUriInstanceFromSapi($server, $headers);
        [$base, $webroot] = static::getBase($uri, $server);

        // Look in PATH_INFO first, as this is the exact value we need prepared
        // by PHP.
        $pathInfo = Hash::get($server, 'PATH_INFO');
        if ($pathInfo) {
            $uri = $uri->withPath($pathInfo);
        } else {
            $uri = static::updatePath($base, $uri);
        }

        if (!$uri->getHost()) {
            $uri = $uri->withHost('localhost');
        }

        return new CakeUri($uri, $base, $webroot);
    }

    /**
     * Updates the request URI to remove the base directory.
     *
     * @param string $base The base path to remove.
     * @param \Psr\Http\Message\UriInterface $uri The uri to update.
     * @return \Psr\Http\Message\UriInterface The modified Uri instance.
     */
    protected static function updatePath(string $base, UriInterface $uri): UriInterface
    {
        $path = $uri->getPath();
        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        if ($path === '/index.php' && $uri->getQuery()) {
            $path = $uri->getQuery();
        }
        if (empty($path) || $path === '/' || $path === '//' || $path === '/index.php') {
            $path = '/';
        }
        $endsWithIndex = '/' . (Configure::read('App.webroot') ?: 'webroot') . '/index.php';
        $endsWithLength = strlen($endsWithIndex);
        if (
            strlen($path) >= $endsWithLength &&
            substr($path, -$endsWithLength) === $endsWithIndex
        ) {
            $path = '/';
        }

        return $uri->withPath($path);
    }

    /**
     * Calculate the base directory and webroot directory.
     *
     * @param \Psr\Http\Message\UriInterface $uri The Uri instance.
     * @param array $server The SERVER data to use.
     * @return array An array containing the [baseDir, webroot]
     */
    protected static function getBase(UriInterface $uri, array $server): array
    {
        $config = (array)Configure::read('App') + [
            'base' => null,
            'webroot' => null,
            'baseUrl' => null,
        ];
        $base = $config['base'];
        $baseUrl = $config['baseUrl'];
        $webroot = $config['webroot'];

        if ($base !== false && $base !== null) {
            return [$base, $base . '/'];
        }

        if (!$baseUrl) {
            $base = dirname(Hash::get($server, 'PHP_SELF'));
            // Clean up additional / which cause following code to fail..
            $base = preg_replace('#/+#', '/', $base);

            $indexPos = strpos($base, '/' . $webroot . '/index.php');
            if ($indexPos !== false) {
                $base = substr($base, 0, $indexPos) . '/' . $webroot;
            }
            if ($webroot === basename($base)) {
                $base = dirname($base);
            }

            if ($base === DIRECTORY_SEPARATOR || $base === '.') {
                $base = '';
            }
            $base = implode('/', array_map('rawurlencode', explode('/', $base)));

            return [$base, $base . '/'];
        }

        $file = '/' . basename($baseUrl);
        $base = dirname($baseUrl);

        if ($base === DIRECTORY_SEPARATOR || $base === '.') {
            $base = '';
        }
        $webrootDir = $base . '/';

        $docRoot = Hash::get($server, 'DOCUMENT_ROOT');
        $docRootContainsWebroot = strpos($docRoot, $webroot);

        if (!empty($base) || !$docRootContainsWebroot) {
            if (strpos($webrootDir, '/' . $webroot . '/') === false) {
                $webrootDir .= $webroot . '/';
            }
        }

        return [$base . $file, $webrootDir];
    }
}
