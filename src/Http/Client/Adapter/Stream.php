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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client\Adapter;

use Cake\Http\Client\AdapterInterface;
use Cake\Http\Client\Exception\ClientException;
use Cake\Http\Client\Exception\NetworkException;
use Cake\Http\Client\Exception\RequestException;
use Cake\Http\Client\Response;
use Composer\CaBundle\CaBundle;
use Psr\Http\Message\RequestInterface;

/**
 * Implements sending Cake\Http\Client\Request
 * via php's stream API.
 *
 * This approach and implementation is partly inspired by Aura.Http
 */
class Stream implements AdapterInterface
{
    /**
     * Context resource used by the stream API.
     *
     * @var resource|null
     */
    protected $_context;

    /**
     * Array of options/content for the HTTP stream context.
     *
     * @var array<string, mixed>
     */
    protected array $_contextOptions = [];

    /**
     * Array of options/content for the SSL stream context.
     *
     * @var array<string, mixed>
     */
    protected array $_sslContextOptions = [];

    /**
     * The stream resource.
     *
     * @var resource|null
     */
    protected $_stream;

    /**
     * Connection error list.
     *
     * @var array
     */
    protected array $_connectionErrors = [];

    /**
     * @inheritDoc
     */
    public function send(RequestInterface $request, array $options): array
    {
        $this->_stream = null;
        $this->_context = null;
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
     * @return array<\Cake\Http\Client\Response> The list of responses from the request(s)
     */
    public function createResponses(array $headers, string $content): array
    {
        $indexes = $responses = [];
        foreach ($headers as $i => $header) {
            if (strtoupper(substr($header, 0, 5)) === 'HTTP/') {
                $indexes[] = $i;
            }
        }
        $last = count($indexes) - 1;
        foreach ($indexes as $i => $start) {
            /** @psalm-suppress InvalidOperand */
            $end = isset($indexes[$i + 1]) ? $indexes[$i + 1] - $start : null;
            /** @psalm-suppress PossiblyInvalidArgument */
            $headerSlice = array_slice($headers, $start, $end);
            $body = $i === $last ? $content : '';
            $responses[] = $this->_buildResponse($headerSlice, $body);
        }

        return $responses;
    }

    /**
     * Build the stream context out of the request object.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to build context from.
     * @param array<string, mixed> $options Additional request options.
     * @return void
     */
    protected function _buildContext(RequestInterface $request, array $options): void
    {
        $this->_buildContent($request, $options);
        $this->_buildHeaders($request, $options);
        $this->_buildOptions($request, $options);

        $url = $request->getUri();
        $scheme = parse_url((string)$url, PHP_URL_SCHEME);
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
     * @param \Psr\Http\Message\RequestInterface $request The request being sent.
     * @param array<string, mixed> $options Array of options to use.
     * @return void
     */
    protected function _buildHeaders(RequestInterface $request, array $options): void
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[] = sprintf('%s: %s', $name, implode(', ', $values));
        }
        $this->_contextOptions['header'] = implode("\r\n", $headers);
    }

    /**
     * Builds the request content based on the request object.
     *
     * If the $request->body() is a string, it will be used as is.
     * Array data will be processed with {@link \Cake\Http\Client\FormData}
     *
     * @param \Psr\Http\Message\RequestInterface $request The request being sent.
     * @param array<string, mixed> $options Array of options to use.
     * @return void
     */
    protected function _buildContent(RequestInterface $request, array $options): void
    {
        $body = $request->getBody();
        $body->rewind();
        $this->_contextOptions['content'] = $body->getContents();
    }

    /**
     * Build miscellaneous options for the request.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request being sent.
     * @param array<string, mixed> $options Array of options to use.
     * @return void
     */
    protected function _buildOptions(RequestInterface $request, array $options): void
    {
        $this->_contextOptions['method'] = $request->getMethod();
        $this->_contextOptions['protocol_version'] = $request->getProtocolVersion();
        $this->_contextOptions['ignore_errors'] = true;

        if (isset($options['timeout'])) {
            $this->_contextOptions['timeout'] = $options['timeout'];
        }
        // Redirects are handled in the client layer because of cookie handling issues.
        $this->_contextOptions['max_redirects'] = 0;

        if (isset($options['proxy']['proxy'])) {
            $this->_contextOptions['request_fulluri'] = true;
            $this->_contextOptions['proxy'] = $options['proxy']['proxy'];
        }
    }

    /**
     * Build SSL options for the request.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request being sent.
     * @param array<string, mixed> $options Array of options to use.
     * @return void
     */
    protected function _buildSslContext(RequestInterface $request, array $options): void
    {
        $sslOptions = [
            'ssl_verify_peer',
            'ssl_verify_peer_name',
            'ssl_verify_depth',
            'ssl_allow_self_signed',
            'ssl_cafile',
            'ssl_local_cert',
            'ssl_local_pk',
            'ssl_passphrase',
        ];
        if (empty($options['ssl_cafile'])) {
            $options['ssl_cafile'] = CaBundle::getBundledCaBundlePath();
        }
        if (!empty($options['ssl_verify_host'])) {
            $url = $request->getUri();
            $host = parse_url((string)$url, PHP_URL_HOST);
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
     * @param \Psr\Http\Message\RequestInterface $request The request object.
     * @return array Array of populated Response objects
     * @throws \Psr\Http\Client\NetworkExceptionInterface
     */
    protected function _send(RequestInterface $request): array
    {
        $deadline = false;
        if (isset($this->_contextOptions['timeout']) && $this->_contextOptions['timeout'] > 0) {
            /** @var int $deadline */
            $deadline = time() + $this->_contextOptions['timeout'];
        }

        $url = $request->getUri();
        $this->_open((string)$url, $request);
        $content = '';
        $timedOut = false;

        assert($this->_stream !== null, 'HTTP stream failed to open');

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
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        fclose($this->_stream);

        if ($timedOut) {
            throw new NetworkException('Connection timed out ' . $url, $request);
        }

        $headers = $meta['wrapper_data'];
        if (isset($headers['headers']) && is_array($headers['headers'])) {
            $headers = $headers['headers'];
        }

        return $this->createResponses($headers, $content);
    }

    /**
     * Build a response object
     *
     * @param array $headers Unparsed headers.
     * @param string $body The response body.
     * @return \Cake\Http\Client\Response
     */
    protected function _buildResponse(array $headers, string $body): Response
    {
        return new Response($headers, $body);
    }

    /**
     * Open the socket and handle any connection errors.
     *
     * @param string $url The url to connect to.
     * @param \Psr\Http\Message\RequestInterface $request The request object.
     * @return void
     * @throws \Psr\Http\Client\RequestExceptionInterface
     */
    protected function _open(string $url, RequestInterface $request): void
    {
        if (!(bool)ini_get('allow_url_fopen')) {
            throw new ClientException('The PHP directive `allow_url_fopen` must be enabled.');
        }

        set_error_handler(function ($code, $message): bool {
            $this->_connectionErrors[] = $message;

            return true;
        });
        try {
            $stream = fopen($url, 'rb', false, $this->_context);
            if ($stream === false) {
                $stream = null;
            }
            $this->_stream = $stream;
        } finally {
            restore_error_handler();
        }

        if (!$this->_stream || $this->_connectionErrors) {
            throw new RequestException(implode("\n", $this->_connectionErrors), $request);
        }
    }

    /**
     * Get the context options
     *
     * Useful for debugging and testing context creation.
     *
     * @return array<string, mixed>
     */
    public function contextOptions(): array
    {
        return array_merge($this->_contextOptions, $this->_sslContextOptions);
    }
}
