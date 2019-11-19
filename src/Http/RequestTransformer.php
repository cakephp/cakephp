<?php
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

use Cake\Utility\Hash;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

/**
 * Translate and transform from PSR7 requests into CakePHP requests.
 *
 * This is an important step for maintaining backwards compatibility
 * with existing CakePHP applications, which depend on the CakePHP request object.
 *
 * There is no reverse transform as the 'application' cannot return a mutated
 * request object.
 *
 * @internal
 * @deprecated 3.4.0 No longer used. Will be removed in 4.0.0
 */
class RequestTransformer
{
    /**
     * Transform a PSR7 request into a CakePHP one.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The PSR7 request.
     * @return \Cake\Http\ServerRequest The transformed request.
     */
    public static function toCake(PsrRequest $request)
    {
        $post = $request->getParsedBody();
        $headers = [];
        foreach ($request->getHeaders() as $k => $value) {
            $name = sprintf('HTTP_%s', strtoupper(str_replace('-', '_', $k)));
            $headers[$name] = implode(',', $value);
        }
        $server = $headers + $request->getServerParams();

        $files = static::getFiles($request);
        if (!empty($files)) {
            $post = Hash::merge($post, $files);
        }

        $input = $request->getBody()->getContents();
        $input = $input === '' ? null : $input;

        return new ServerRequest([
            'query' => $request->getQueryParams(),
            'post' => $post,
            'cookies' => $request->getCookieParams(),
            'environment' => $server,
            'params' => static::getParams($request),
            'url' => $request->getUri()->getPath(),
            'base' => $request->getAttribute('base', ''),
            'webroot' => $request->getAttribute('webroot', '/'),
            'session' => $request->getAttribute('session', null),
            'input' => $input,
        ]);
    }

    /**
     * Extract the routing parameters out of the request object.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to extract params from.
     * @return array The routing parameters.
     */
    protected static function getParams(PsrRequest $request)
    {
        $params = (array)$request->getAttribute('params', []);
        $params += [
            'plugin' => null,
            'controller' => null,
            'action' => null,
            '_ext' => null,
            'pass' => [],
        ];

        return $params;
    }

    /**
     * Extract the uploaded files out of the request object.
     *
     * CakePHP expects to get arrays of file information and
     * not the parsed objects that PSR7 requests contain. Downsample the data here.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request to extract files from.
     * @return array The routing parameters.
     */
    protected static function getFiles($request)
    {
        return static::convertFiles([], $request->getUploadedFiles());
    }

    /**
     * Convert a nested array of files to arrays.
     *
     * @param array $data The data to add files to.
     * @param array $files The file objects to convert.
     * @param string $path The current array path.
     * @return array Converted file data
     */
    protected static function convertFiles($data, $files, $path = '')
    {
        foreach ($files as $key => $file) {
            $newPath = $path;
            if ($newPath === '') {
                $newPath = $key;
            }
            if ($newPath !== $key) {
                $newPath .= '.' . $key;
            }

            if (is_array($file)) {
                $data = static::convertFiles($data, $file, $newPath);
            } else {
                $data = Hash::insert($data, $newPath, static::convertFile($file));
            }
        }

        return $data;
    }

    /**
     * Convert a single file back into an array.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file The file to convert.
     * @return array
     */
    protected static function convertFile($file)
    {
        $error = $file->getError();
        $tmpName = '';
        if ($error === UPLOAD_ERR_OK) {
            $tmpName = $file->getStream()->getMetadata('uri');
        }

        return [
            'name' => $file->getClientFilename(),
            'type' => $file->getClientMediaType(),
            'tmp_name' => $tmpName,
            'error' => $error,
            'size' => $file->getSize(),
        ];
    }
}
