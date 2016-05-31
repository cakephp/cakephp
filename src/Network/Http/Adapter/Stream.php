<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network\Http\Adapter;

use Cake\Core\Exception\Exception;
use Cake\Network\Http\FormData;
use Cake\Network\Http\Request;
use Cake\Network\Http\Response;

/**
 * Implements sending Cake\Network\Http\Request
 * via php's stream API.
 *
 * This approach and implementation is partly inspired by Aura.Http
 */
class Stream
{

    /**
     * Context resource used by the stream API.
     *
     * @var resource
     */
    protected $_context;

    /**
     * Array of options/content for the HTTP stream context.
     *
     * @var array
     */
    protected $_contextOptions;

    /**
     * Array of options/content for the SSL stream context.
     *
     * @var array
     */
    protected $_sslContextOptions;

    /**
     * The stream resource.
     *
     * @var resource
     */
    protected $_stream;

    /**
     * Connection error list.
     *
     * @var array
     */
    protected $_connectionErrors = [];

    /**
     * Send a request and get a response back.
     *
     * @param \Cake\Network\Http\Request $request The request object to send.
     * @param array $options Array of options for the stream.
     * @return array Array of populated Response objects
     */
    public function send(Request $request, array $options)
    {
        $this->_stream = null;
        $this->_context = [];
        $this->_contextOptions = [];
        $this->_sslContextOptions = [];
        $this->_connectionErrors = [];

        $this->_buildContext($request, $options);
        return $this->_send($request);
    }

    /**
     * Create the response list based on the headers & content
     *
     * Creates one or many response objects based on the number
     * of redirects that occurred.
     *
     * @param array $headers The list of headers from the request(s)
     * @param string $content The response content.
     * @return array The list of responses from the request(s)
     */
    public function createResponses($headers, $content)
    {
        $indexes = $responses = [];
        foreach ($headers as $i => $header) {
            if (strtoupper(substr($header, 0, 5)) === 'HTTP/') {
                $indexes[] = $i;
            }
        }
        $last = count($indexes) - 1;
        foreach ($indexes as $i => $start) {
            $end = isset($indexes[$i + 1]) ? $indexes[$i + 1] - $start : null;
            $headerSlice = array_slice($headers, $start, $end);
            $body = $i == $last ? $content : '';
            $responses[] = new Response($headerSlice, $body);
        }
        return $responses;
    }

    /**
     * Build the stream context out of the request object.
     *
     * @param \Cake\Network\Http\Request $request The request to build context from.
     * @param array $options Additional request options.
     * @return void
     */
    protected function _buildContext(Request $request, $options)
    {
        $this->_buildContent($request, $options);
        $this->_buildHeaders($request, $options);
        $this->_buildOptions($request, $options);

        $url = $request->url();
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme === 'https') {
            $this->_buildSslContext($request, $options);
        }
        $this->_context = stream_context_create([
            'http' => $this->_contextOptions,
            'ssl' => $this->_sslContextOptions,
        ]);
    }

    /**
     * Build the header context for the request.
     *
     * Creates cookies & headers.
     *
     * @param \Cake\Network\Http\Request $request The request being sent.
     * @param array $options Array of options to use.
     * @return void
     */
    protected function _buildHeaders(Request $request, $options)
    {
        $headers = [];
        foreach ($request->headers() as $name => $value) {
            $headers[] = "$name: $value";
        }

        $cookies = [];
        foreach ($request->cookies() as $name => $value) {
            $cookies[] = "$name=$value";
        }
        if ($cookies) {
            $headers[] = 'Cookie: ' . implode('; ', $cookies);
        }
        $this->_contextOptions['header'] = implode("\r\n", $headers);
    }

    /**
     * Builds the request content based on the request object.
     *
     * If the $request->body() is a string, it will be used as is.
     * Array data will be processed with Cake\Network\Http\FormData
     *
     * @param \Cake\Network\Http\Request $request The request being sent.
     * @param array $options Array of options to use.
     * @return void
     */
    protected function _buildContent(Request $request, $options)
    {
        $content = $request->body();
        if (empty($content)) {
            return;
        }
        if (is_string($content)) {
            $this->_contextOptions['content'] = $content;
            return;
        }
        if (is_array($content)) {
            $formData = new FormData();
            $formData->addMany($content);
            $type = $formData->contentType();
            $request->header('Content-Type', $type);
            $this->_contextOptions['content'] = (string)$formData;
            return;
        }
        $this->_contextOptions['content'] = $content;
    }

    /**
     * Build miscellaneous options for the request.
     *
     * @param \Cake\Network\Http\Request $request The request being sent.
     * @param array $options Array of options to use.
     * @return void
     */
    protected function _buildOptions(Request $request, $options)
    {
        $this->_contextOptions['method'] = $request->method();
        $this->_contextOptions['protocol_version'] = $request->version();
        $this->_contextOptions['ignore_errors'] = true;

        if (isset($options['timeout'])) {
            $this->_contextOptions['timeout'] = $options['timeout'];
        }
        if (isset($options['redirect'])) {
            $this->_contextOptions['max_redirects'] = (int)$options['redirect'];
        }
        if (isset($options['proxy']['proxy'])) {
            $this->_contextOptions['proxy'] = $options['proxy']['proxy'];
        }
    }

    /**
     * Build SSL options for the request.
     *
     * @param \Cake\Network\Http\Request $request The request being sent.
     * @param array $options Array of options to use.
     * @return void
     */
    protected function _buildSslContext(Request $request, $options)
    {
        $sslOptions = [
            'ssl_verify_peer',
            'ssl_verify_peer_name',
            'ssl_verify_depth',
            'ssl_allow_self_signed',
            'ssl_cafile',
            'ssl_local_cert',
            'ssl_passphrase',
        ];
        if (empty($options['ssl_cafile'])) {
            $options['ssl_cafile'] = CORE_PATH . 'config' . DIRECTORY_SEPARATOR . 'cacert.pem';
        }
        if (!empty($options['ssl_verify_host'])) {
            $url = $request->url();
            $host = parse_url($url, PHP_URL_HOST);
            $this->_sslContextOptions['peer_name'] = $host;
        }
        foreach ($sslOptions as $key) {
            if (isset($options[$key])) {
                $name = substr($key, 4);
                $this->_sslContextOptions[$name] = $options[$key];
            }
        }
    }

    /**
     * Open the stream and send the request.
     *
     * @param \Cake\Network\Http\Request $request The request object.
     * @return array Array of populated Response objects
     * @throws \Cake\Core\Exception\Exception
     */
    protected function _send(Request $request)
    {
        $deadline = false;
        if (isset($this->_contextOptions['timeout']) && $this->_contextOptions['timeout'] > 0) {
            $deadline = time() + $this->_contextOptions['timeout'];
        }

        $url = $request->url();
        $this->_open($url);
        $content = '';
        $timedOut = false;

        while (!feof($this->_stream)) {
            if ($deadline !== false) {
                stream_set_timeout($this->_stream, max($deadline - time(), 1));
            }

            $content .= fread($this->_stream, 8192);

            $meta = stream_get_meta_data($this->_stream);
            if ($meta['timed_out'] || ($deadline !== false && time() > $deadline)) {
                $timedOut = true;
                break;
            }
        }
        $meta = stream_get_meta_data($this->_stream);
        fclose($this->_stream);

        if ($timedOut) {
            throw new Exception('Connection timed out ' . $url);
        }

        $headers = $meta['wrapper_data'];
        if (isset($headers['headers']) && is_array($headers['headers'])) {
            $headers = $headers['headers'];
        }
        return $this->createResponses($headers, $content);
    }

    /**
     * Open the socket and handle any connection errors.
     *
     * @param string $url The url to connect to.
     * @return void
     * @throws \Cake\Core\Exception\Exception
     */
    protected function _open($url)
    {
        set_error_handler([$this, '_connectionErrorHandler']);
        $this->_stream = fopen($url, 'rb', false, $this->_context);
        restore_error_handler();

        if (!$this->_stream || !empty($this->_connectionErrors)) {
            throw new Exception(implode("\n", $this->_connectionErrors));
        }
    }

    /**
     * Local error handler to capture errors triggered during
     * stream connection.
     *
     * @param int $code Error code.
     * @param string $message Error message.
     * @return void
     */
    protected function _connectionErrorHandler($code, $message)
    {
        $this->_connectionErrors[] = $message;
    }

    /**
     * Get the context options
     *
     * Useful for debugging and testing context creation.
     *
     * @return array
     */
    public function contextOptions()
    {
        return array_merge($this->_contextOptions, $this->_sslContextOptions);
    }
}
