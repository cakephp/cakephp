<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client\Adapter;

use Cake\Http\Client\AdapterInterface;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;
use Cake\Http\Exception\HttpException;

/**
 * Implements sending Cake\Http\Client\Request via ext/curl.
 *
 * In addition to the standard options documented in Cake\Http\Client,
 * this adapter supports all available curl options. Additional curl options
 * can be set via the `curl` option key when making requests or configuring
 * a client.
 */
class Curl implements AdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function send(Request $request, array $options)
    {
        $ch = curl_init();
        $options = $this->buildOptions($request, $options);
        curl_setopt_array($ch, $options);

        $body = $this->exec($ch);
        if ($body === false) {
            $errorCode = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);

            $status = 500;
            if ($errorCode === CURLE_OPERATION_TIMEOUTED) {
                $status = 504;
            }
            throw new HttpException("cURL Error ({$errorCode}) {$error}", $status);
        }

        $responses = $this->createResponse($ch, $body);
        curl_close($ch);

        return $responses;
    }

    /**
     * Convert client options into curl options.
     *
     * @param \Cake\Http\Client\Request $request The request.
     * @param array $options The client options
     * @return array
     */
    public function buildOptions(Request $request, array $options)
    {
        $headers = [];
        foreach ($request->getHeaders() as $key => $values) {
            $headers[] = $key . ': ' . implode(', ', $values);
        }

        $out = [
            CURLOPT_URL => (string)$request->getUri(),
            CURLOPT_HTTP_VERSION => $this->getProtocolVersion($request),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $headers,
        ];
        switch ($request->getMethod()) {
            case Request::METHOD_GET:
                $out[CURLOPT_HTTPGET] = true;
                break;

            case Request::METHOD_POST:
                $out[CURLOPT_POST] = true;
                break;

            case Request::METHOD_HEAD:
                $out[CURLOPT_NOBODY] = true;
                break;

            default:
                $out[CURLOPT_POST] = true;
                $out[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
                break;
        }

        $body = $request->getBody();
        if ($body) {
            $body->rewind();
            $out[CURLOPT_POSTFIELDS] = $body->getContents();
            // GET requests with bodies require custom request to be used.
            if (isset($out[CURLOPT_HTTPGET])) {
                $out[CURLOPT_CUSTOMREQUEST] = 'get';
            }
        }

        if (empty($options['ssl_cafile'])) {
            $options['ssl_cafile'] = CORE_PATH . 'config' . DIRECTORY_SEPARATOR . 'cacert.pem';
        }
        if (!empty($options['ssl_verify_host'])) {
            // Value of 1 or true is deprecated. Only 2 or 0 should be used now.
            $options['ssl_verify_host'] = 2;
        }
        $optionMap = [
            'timeout' => CURLOPT_TIMEOUT,
            'ssl_verify_peer' => CURLOPT_SSL_VERIFYPEER,
            'ssl_verify_host' => CURLOPT_SSL_VERIFYHOST,
            'ssl_cafile' => CURLOPT_CAINFO,
            'ssl_local_cert' => CURLOPT_SSLCERT,
            'ssl_passphrase' => CURLOPT_SSLCERTPASSWD,
        ];
        foreach ($optionMap as $option => $curlOpt) {
            if (isset($options[$option])) {
                $out[$curlOpt] = $options[$option];
            }
        }
        if (isset($options['proxy']['proxy'])) {
            $out[CURLOPT_PROXY] = $options['proxy']['proxy'];
        }
        if (isset($options['proxy']['username'])) {
            $password = !empty($options['proxy']['password']) ? $options['proxy']['password'] : '';
            $out[CURLOPT_PROXYUSERPWD] = $options['proxy']['username'] . ':' . $password;
        }
        if (isset($options['curl']) && is_array($options['curl'])) {
            // Can't use array_merge() because keys will be re-ordered.
            foreach ($options['curl'] as $key => $value) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * Convert HTTP version number into curl value.
     *
     * @param \Cake\Http\Client\Request $request The request to get a protocol version for.
     * @return int
     */
    protected function getProtocolVersion(Request $request)
    {
        switch ($request->getProtocolVersion()) {
            case '1.0':
                return CURL_HTTP_VERSION_1_0;
            case '1.1':
                return CURL_HTTP_VERSION_1_1;
            case '2':
            case '2.0':
                if (defined('CURL_HTTP_VERSION_2TLS')) {
                    return CURL_HTTP_VERSION_2TLS;
                }
                if (defined('CURL_HTTP_VERSION_2_0')) {
                    return CURL_HTTP_VERSION_2_0;
                }
                throw new HttpException('libcurl 7.33 or greater required for HTTP/2 support');
        }

        return CURL_HTTP_VERSION_NONE;
    }

    /**
     * Convert the raw curl response into an Http\Client\Response
     *
     * @param resource $handle Curl handle
     * @param string $responseData string The response data from curl_exec
     * @return \Cake\Http\Client\Response
     */
    protected function createResponse($handle, $responseData)
    {
        $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        $headers = trim(substr($responseData, 0, $headerSize));
        $body = substr($responseData, $headerSize);
        $response = new Response(explode("\r\n", $headers), $body);

        return [$response];
    }

    /**
     * Execute the curl handle.
     *
     * @param resource $ch Curl Resource handle
     * @return string
     */
    protected function exec($ch)
    {
        return curl_exec($ch);
    }
}
