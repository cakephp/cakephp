<?php
declare(strict_types=1);

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
use Cake\Http\Client\Exception\ClientException;
use Cake\Http\Client\Exception\NetworkException;
use Cake\Http\Client\Exception\RequestException;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;
use Cake\Http\Exception\HttpException;
use Composer\CaBundle\CaBundle;
use Psr\Http\Message\RequestInterface;

/**
 * Implements sending Cake\Http\Client\Request via ext/curl.
 *
 * In addition to the standard options documented in {@link \Cake\Http\Client},
 * this adapter supports all available curl options. Additional curl options
 * can be set via the `curl` option key when making requests or configuring
 * a client.
 */
class Curl implements AdapterInterface
{
    /**
     * @inheritDoc
     */
    public function send(RequestInterface $request, array $options): array
    {
        if (!extension_loaded('curl')) {
            throw new ClientException('curl extension is not loaded.');
        }

        $ch = curl_init();
        $options = $this->buildOptions($request, $options);
        curl_setopt_array($ch, $options);

        /** @var string|false $body */
        $body = $this->exec($ch);
        if ($body === false) {
            $errorCode = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);

            $message = "cURL Error ({$errorCode}) {$error}";
            $errorNumbers = [
                CURLE_FAILED_INIT,
                CURLE_URL_MALFORMAT,
                CURLE_URL_MALFORMAT_USER,
            ];
            if (in_array($errorCode, $errorNumbers, true)) {
                throw new RequestException($message, $request);
            }
            throw new NetworkException($message, $request);
        }

        $responses = $this->createResponse($ch, $body);
        curl_close($ch);

        return $responses;
    }

    /**
     * Convert client options into curl options.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request.
     * @param array<string, mixed> $options The client options
     * @return array
     */
    public function buildOptions(RequestInterface $request, array $options): array
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
        $body->rewind();
        $out[CURLOPT_POSTFIELDS] = $body->getContents();
        // GET requests with bodies require custom request to be used.
        if ($out[CURLOPT_POSTFIELDS] !== '' && isset($out[CURLOPT_HTTPGET])) {
            $out[CURLOPT_CUSTOMREQUEST] = 'get';
        }
        if ($out[CURLOPT_POSTFIELDS] === '') {
            unset($out[CURLOPT_POSTFIELDS]);
        }

        if (empty($options['ssl_cafile'])) {
            $options['ssl_cafile'] = CaBundle::getBundledCaBundlePath();
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
     * @param \Psr\Http\Message\RequestInterface $request The request to get a protocol version for.
     * @return int
     */
    protected function getProtocolVersion(RequestInterface $request): int
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
     * @param resource|\CurlHandle $handle Curl handle
     * @param string $responseData string The response data from curl_exec
     * @return array<\Cake\Http\Client\Response>
     * @psalm-suppress UndefinedDocblockClass
     */
    protected function createResponse($handle, $responseData): array
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        $headers = trim(substr($responseData, 0, $headerSize));
        $body = substr($responseData, $headerSize);
        $response = new Response(explode("\r\n", $headers), $body);

        return [$response];
    }

    /**
     * Execute the curl handle.
     *
     * @param resource|\CurlHandle $ch Curl Resource handle
     * @return string|bool
     * @psalm-suppress UndefinedDocblockClass
     */
    protected function exec($ch)
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        return curl_exec($ch);
    }
}
