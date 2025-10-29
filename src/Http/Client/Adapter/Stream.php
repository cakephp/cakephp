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
    protected $context;

    /**
     * Array of options/content for the HTTP stream context.
     *
     * @var array<string, mixed>
     */
    protected array $contextOptions = [];

    /**
     * Array of options/content for the SSL stream context.
     *
     * @var array<string, mixed>
     */
    protected array $sslContextOptions = [];

    /**
     * The stream resource.
     *
     * @var resource|null
     */
    protected $stream;

    /**
     * Connection error list.
     *
     * @var array
     */
    protected array $connectionErrors = [];

    /**
     * @inheritDoc
     */
    public function send(RequestInterface $request, array $options): array
    {
        $this->stream = null;
        $this->context = null;
        $this->contextOptions = [];
        $this->sslContextOptions = [];
        $this->connectionErrors = [];

        $this->buildContext($request, $options);

        return $this->processRequest($request);
    }

    /**
     * Create the response list based on the headers & content
     *
     * Creates one or many response objects based on the number
     * of redirects that occurred.
     *
     * @param list<string> $headers The list of headers from the request(s)
     * @param string $content The response content.
     * @return array<\Cake\Http\Client\Response> The list of responses from the request(s)
     */
    public function createResponses(array $headers, string $content): array
    {
        $indexes = [];
        $responses = [];
        foreach ($headers as $i => $header) {
            if (strtoupper(substr($header, 0, 5)) === 'HTTP/') {
                $indexes[] = $i;
            }
        }
        $last = count($indexes) - 1;
        foreach ($indexes as $i => $start) {
            $end = isset($indexes[$i + 1]) ? $indexes[$i + 1] - $start : null;
            $headerSlice = array_slice($headers, $start, $end);
            $body = $i === $last ? $content : '';
            $responses[] = $this->buildResponse($headerSlice, $body);
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
    protected function buildContext(RequestInterface $request, array $options): void
    {
        $this->buildContent($request, $options);
        $this->buildHeaders($request, $options);
        $this->buildOptions($request, $options);

        $url = $request->getUri();
        $scheme = parse_url((string)$url, PHP_URL_SCHEME);
        if ($scheme === 'https') {
            $this->buildSslContext($request, $options);
        }
        $this->context = stream_context_create([
            'http' => $this->contextOptions,
            'ssl' => $this->sslContextOptions,
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
    protected function buildHeaders(RequestInterface $request, array $options): void
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[] = sprintf('%s: %s', $name, implode(', ', $values));
        }
        $this->contextOptions['header'] = implode("\r\n", $headers);
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
    protected function buildContent(RequestInterface $request, array $options): void
    {
        $body = $request->getBody();
        $body->rewind();
        $this->contextOptions['content'] = $body->getContents();
    }

    /**
     * Build miscellaneous options for the request.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request being sent.
     * @param array<string, mixed> $options Array of options to use.
     * @return void
     */
    protected function buildOptions(RequestInterface $request, array $options): void
    {
        $this->contextOptions['method'] = $request->getMethod();
        $this->contextOptions['protocol_version'] = $request->getProtocolVersion();
        $this->contextOptions['ignore_errors'] = true;

        if (isset($options['timeout'])) {
            $this->contextOptions['timeout'] = $options['timeout'];
        }
        // Redirects are handled in the client layer because of cookie handling issues.
        $this->contextOptions['max_redirects'] = 0;

        if (isset($options['proxy']['proxy'])) {
            $this->contextOptions['request_fulluri'] = true;
            $this->contextOptions['proxy'] = $options['proxy']['proxy'];
        }
    }

    /**
     * Build SSL options for the request.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request being sent.
     * @param array<string, mixed> $options Array of options to use.
     * @return void
     */
    protected function buildSslContext(RequestInterface $request, array $options): void
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
            $this->sslContextOptions['peer_name'] = $host;
        }
        foreach ($sslOptions as $key) {
            if (isset($options[$key])) {
                $name = substr($key, 4);
                $this->sslContextOptions[$name] = $options[$key];
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
    protected function processRequest(RequestInterface $request): array
    {
        $deadline = false;
        if (isset($this->contextOptions['timeout']) && $this->contextOptions['timeout'] > 0) {
            /** @var int $deadline */
            $deadline = time() + $this->contextOptions['timeout'];
        }

        $url = $request->getUri();
        $this->open((string)$url, $request);
        $content = '';
        $timedOut = false;

        assert($this->stream !== null, 'HTTP stream failed to open');

        while (!feof($this->stream)) {
            if ($deadline !== false) {
                stream_set_timeout($this->stream, max($deadline - time(), 1));
            }

            $content .= fread($this->stream, 8192);

            $meta = stream_get_meta_data($this->stream);
            if ($meta['timed_out'] || ($deadline !== false && time() > $deadline)) {
                $timedOut = true;
                break;
            }
        }

        $meta = stream_get_meta_data($this->stream);
        fclose($this->stream);

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
     * @param array<string> $headers Unparsed headers.
     * @param string $body The response body.
     * @return \Cake\Http\Client\Response
     */
    protected function buildResponse(array $headers, string $body): Response
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
    protected function open(string $url, RequestInterface $request): void
    {
        if (!(bool)ini_get('allow_url_fopen')) {
            throw new ClientException('The PHP directive `allow_url_fopen` must be enabled.');
        }

        set_error_handler(function ($code, $message): bool {
            $this->connectionErrors[] = $message;

            return true;
        });
        try {
            $stream = fopen($url, 'rb', false, $this->context);
            if ($stream === false) {
                $stream = null;
            }
            $this->stream = $stream;
        } finally {
            restore_error_handler();
        }

        if (!$this->stream || $this->connectionErrors) {
            throw new RequestException(implode("\n", $this->connectionErrors), $request);
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
        return array_merge($this->contextOptions, $this->sslContextOptions);
    }
}
