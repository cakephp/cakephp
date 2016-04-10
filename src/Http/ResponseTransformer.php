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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Cake\Network\Response as CakeResponse;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Zend\Diactoros\CallbackStream;
use Zend\Diactoros\Response as DiactorosResponse;
use Zend\Diactoros\Stream;

/**
 * This class converts PSR7 responses into CakePHP ones and back again.
 *
 * By bridging the CakePHP and PSR7 responses together, applications
 * can be embedded as PSR7 middleware in a fully compatible way.
 *
 * @internal
 */
class ResponseTransformer
{
    /**
     * Convert a PSR7 Response into a CakePHP one.
     *
     * @param PsrResponse $response The response to convert.
     * @return CakeResponse The equivalent CakePHP response
     */
    public static function toCake(PsrResponse $response)
    {
        $data = [
            'status' => $response->getStatusCode(),
            'body' => static::getBody($response),
        ];
        $cake = new CakeResponse($data);
        $cake->header(static::collapseHeaders($response));
        return $cake;
    }

    /**
     * Get the response body from a PSR7 Response.
     *
     * @param PsrResponse $response The response to convert.
     * @return string The response body.
     */
    protected static function getBody(PsrResponse $response)
    {
        $stream = $response->getBody();
        if ($stream->getSize() === 0) {
            return '';
        }
        $stream->rewind();
        return $stream->getContents();
    }

    /**
     * Convert a PSR7 Response headers into a flat array
     *
     * @param PsrResponse $response The response to convert.
     * @return CakeResponse The equivalent CakePHP response
     */
    protected static function collapseHeaders(PsrResponse $response)
    {
        $out = [];
        foreach ($response->getHeaders() as $name => $value) {
            if (count($value) === 1) {
                $out[$name] = $value[0];
            } else {
                $out[$name] = $value;
            }
        }
        return $out;
    }

    /**
     * Convert a CakePHP response into a PSR7 one.
     *
     * @param CakeResponse $response The CakePHP response to convert
     * @return PsrResponse $response The equivalent PSR7 response.
     */
    public static function toPsr(CakeResponse $response)
    {
        $status = $response->statusCode();
        $headers = $response->header();
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = $response->type();
        }
        $stream = static::getStream($response);
        return new DiactorosResponse($stream, $status, $headers);
    }

    /**
     * Get the stream for the new response.
     *
     * @param \Cake\Network\Response $response The cake response to extract the body from.
     * @return Psr\Http\Message\StreamInterface The stream.
     */
    protected static function getStream($response)
    {
        $stream = 'php://memory';
        $body = $response->body();
        if (is_string($body)) {
            $stream = new Stream('php://memory', 'wb');
            $stream->write($body);
            return $stream;
        }
        if (is_callable($body)) {
            $stream = new CallbackStream($body);
            return $stream;
        }
        $file = $response->getFile();
        if ($file) {
            $stream = new Stream($file->path, 'rb');
            return $stream;
        }
        return $stream;
    }
}
