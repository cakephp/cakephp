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
 * Implements sending Cake\Http\Client\Request
 * via ext/curl.
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
            throw new HttpException("cURL Error ({$errorCode}) {$error}");
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
            CURLOPT_HTTP_VERSION => $request->getProtocolVersion(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $headers
        ];
        switch ($request->getMethod()) {
            case Request::METHOD_GET:
                $out[CURLOPT_HTTPGET] = true;
                break;

            case Request::METHOD_POST:
                $out[CURLOPT_POST] = true;
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
        }

        if (empty($options['ssl_cafile'])) {
            $options['ssl_cafile'] = CORE_PATH . 'config' . DIRECTORY_SEPARATOR . 'cacert.pem';
        }
        $optionMap = [
            'timeout' => CURLOPT_TIMEOUT,
            'ssl_verify_peer' => CURLOPT_SSL_VERIFYPEER,
            'ssl_verify_host' => CURLOPT_SSL_VERIFYHOST,
            'ssl_verify_status' => CURLOPT_SSL_VERIFYSTATUS,
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

        return $out;
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
        $meta = curl_getinfo($handle);
        $headers = trim(substr($responseData, 0, $meta['header_size']));
        $body = substr($responseData, $meta['header_size']);
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
