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
 *
 * Parts of this file are derived from laminas-diactoros
 * @copyright Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC. (https://getlaminas.org/)
 * @license   https://github.com/laminas/laminas-diactoros/blob/3.9.x/LICENSE.md New BSD License
 */
namespace Cake\Http;

use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Utility\Hash;
use InvalidArgumentException;
use Nyholm\Psr7\UploadedFile;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

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
        ?array $files = null,
    ): ServerRequest {
        $server = static::normalizeServer($server ?? $_SERVER);
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
        // `Uri` instance initially created always takes values of `HTTP_X_FORWARDED_PROTO`
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
     * Override values are upper-cased and may only contain the letters A-Z, optionally
     * separated by single hyphens or underscores (e.g. `VERSION-CONTROL`).
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
            $request = $request->withEnv('REQUEST_METHOD', static::normalizeOverrideMethod($parsedBody['_method']));
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
     * Normalize and validate a method override value.
     *
     * Only letters, optionally separated by single hyphens or underscores, are accepted to prevent
     * arbitrary client supplied strings from ending up in `REQUEST_METHOD`, while
     * still allowing custom methods like the WebDAV `VERSION-CONTROL`.
     *
     * @param mixed $method The method from the `_method` field or `X-Http-Method-Override` header.
     * @return string
     * @throws \Cake\Http\Exception\BadRequestException When the method is not a valid override value.
     */
    protected static function normalizeOverrideMethod(mixed $method): string
    {
        if (is_string($method)) {
            $method = strtoupper($method);
            if (preg_match('/^[A-Z]+(?:[-_][A-Z]+)*$/D', $method)) {
                return $method;
            }
        }

        throw new BadRequestException('Invalid HTTP method override.');
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
        $files = static::normalizeUploadedFiles($files);
        $request = $request->withUploadedFiles($files);

        $parsedBody = $request->getParsedBody();
        if (!is_array($parsedBody)) {
            return $request;
        }

        $parsedBody = Hash::merge($parsedBody, $files);

        return $request->withParsedBody($parsedBody);
    }

    /**
     * Populate `HTTP_AUTHORIZATION` from the Apache request headers.
     *
     * Some SAPI/httpd combinations don't expose the Authorization header
     * in `$_SERVER`.
     *
     * @param array $server The SERVER data to normalize.
     * @return array
     */
    protected static function normalizeServer(array $server): array
    {
        if (isset($server['HTTP_AUTHORIZATION']) || !function_exists('apache_request_headers')) {
            return $server;
        }

        $headers = array_change_key_case((array)apache_request_headers());
        if (isset($headers['authorization'])) {
            $server['HTTP_AUTHORIZATION'] = $headers['authorization'];
        }

        return $server;
    }

    /**
     * Normalize a `$_FILES` structure into a tree of UploadedFileInterface instances.
     *
     * @param array $files The files data to normalize.
     * @return array<string|int, \Psr\Http\Message\UploadedFileInterface|array>
     * @throws \InvalidArgumentException For invalid file values.
     */
    protected static function normalizeUploadedFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = static::createUploadedFiles(
                    $value['tmp_name'],
                    $value['size'] ?? null,
                    $value['error'] ?? null,
                    $value['name'] ?? null,
                    $value['type'] ?? null,
                );
            } elseif (is_array($value)) {
                $normalized[$key] = static::normalizeUploadedFiles($value);
            } else {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $normalized;
    }

    /**
     * Create uploaded file instances from a (possibly nested) `$_FILES` specification.
     *
     * PHP stores nested uploads as separate trees per attribute, e.g.
     * `['tmp_name' => ['a' => ...], 'size' => ['a' => ...]]`.
     *
     * @param mixed $tmpName The `tmp_name` value or tree.
     * @param mixed $size The `size` value or tree.
     * @param mixed $error The `error` value or tree.
     * @param mixed $name The `name` value or tree.
     * @param mixed $type The `type` value or tree.
     * @return \Psr\Http\Message\UploadedFileInterface|array
     * @throws \InvalidArgumentException When the specification is incomplete.
     */
    protected static function createUploadedFiles(
        mixed $tmpName,
        mixed $size,
        mixed $error,
        mixed $name,
        mixed $type,
    ): UploadedFileInterface|array {
        if (!is_array($tmpName)) {
            if ($size === null || $error === null) {
                throw new InvalidArgumentException(
                    'Uploaded file specifications must contain each of the keys `tmp_name`, `size`, and `error`.',
                );
            }

            return new UploadedFile($tmpName, (int)$size, (int)$error, $name, $type);
        }
        if (!is_array($size) || !is_array($error)) {
            throw new InvalidArgumentException(
                'Nested uploaded file specifications must contain `tmp_name`, `size`, and `error` arrays.',
            );
        }

        $normalized = [];
        foreach ($tmpName as $key => $value) {
            $normalized[$key] = static::createUploadedFiles(
                $value,
                $size[$key] ?? null,
                $error[$key] ?? null,
                is_array($name) ? $name[$key] ?? null : null,
                is_array($type) ? $type[$key] ?? null : null,
            );
        }

        return $normalized;
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
            $uri = new UriFactory()->createUri($uri);
        }
        $options['uri'] = $uri;

        return new ServerRequest($options);
    }
}
