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

/**
 * Implements sending Cake\Http\Client\Request
 * via CURL.
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

        $bodyBuffer = tmpfile();
        curl_setopt(CURLOPT_FILE, $bodyBuffer);

        $this->exec($ch);
        curl_close($ch);
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

        if (isset($options['timeout'])) {
            $out[CURLOPT_TIMEOUT] = $options['timeout'];
        }

        return $out;
    }

    /**
     * Execute the curl handle.
     *
     * @param resource $ch Curl Resource handle
     * @return void
     */
    protected function exec($ch)
    {
        curl_exec($ch);
    }
}
