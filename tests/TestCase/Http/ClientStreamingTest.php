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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Event\EventManager;
use Cake\Http\Client;
use Cake\Http\Client\Adapter\Stream;
use Cake\Http\Client\ClientEvent;
use Cake\Http\Client\Exception\NetworkException;
use Cake\Http\Response;
use Cake\Http\ResponseEmitter;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/server_mocks.php';

/**
 * Integration tests using a local HTTP server whose body completion is controlled by the test.
 */
class ClientStreamingTest extends TestCase
{
    /**
     * @return array<string, array<string>>
     */
    public static function paths(): array
    {
        return ['plain' => ['/'], 'gzip' => ['/gzip']];
    }

    /**
     * Headers, monitoring events and body chunks arrive while the server is still waiting.
     */
    #[DataProvider('paths')]
    public function testLiveResponse(string $path): void
    {
        [$process, $pipes, $url] = $this->startServer();
        $afterSend = false;
        $client = new Client(['adapter' => Stream::class, 'timeout' => 2]);
        $manager = new EventManager();
        $manager->on('HttpClient.afterSend', function (ClientEvent $event) use (&$afterSend): void {
            $afterSend = true;
            $this->assertSame(200, $event->getResult()->getStatusCode());
            $this->assertFalse($event->getResult()->getBody()->eof());
        });
        $client->setEventManager($manager);
        $body = null;
        try {
            $response = $client->post($url . $path, '{"stream":true}', [
                'stream' => true,
                'readTimeout' => 2,
                'headers' => ['Content-Type' => 'application/json'],
            ]);
            $this->assertTrue($afterSend);
            $this->assertSame('text/event-stream', $response->getHeaderLine('Content-Type'));
            $this->assertFalse($response->hasHeader('Content-Encoding'));
            $body = $response->getBody();
            $this->assertFalse($body->isSeekable());
            $this->assertNull($body->getSize());
            $this->assertSame("data: first\n\n", $body->read(13));
            $this->assertFalse($body->eof());

            // The upstream cannot complete before this point.
            fwrite($pipes[0], "finish\n");
            $this->assertSame("data: second\n\n", $body->getContents());
            $this->assertTrue($body->eof());
        } finally {
            $body?->close();
            $this->stopServer($process, $pipes);
        }
    }

    /**
     * The browser receives the first bytes before the upstream finishes.
     */
    public function testRelayToBrowser(): void
    {
        [$process, $pipes, $url] = $this->startServer();
        $GLOBALS['mockedHeadersSent'] = false;
        $GLOBALS['mockedHeaders'] = [];
        $GLOBALS['mockedCookies'] = [];
        $body = null;
        try {
            $client = new Client(['adapter' => Stream::class, 'timeout' => 2]);
            $upstream = $client->get($url, [], ['stream' => true, 'readTimeout' => 0]);
            $body = $upstream->getBody();
            $response = new Response()
                ->withType('text/event-stream')
                ->withHeader('Cache-Control', 'no-cache')
                ->withHeader('X-Accel-Buffering', 'no')
                ->withBody($body);
            $output = '';
            $released = false;
            ob_start(function (string $chunk) use (&$output, &$released, $pipes): string {
                $output .= $chunk;
                if (!$released && str_contains($output, "data: first\n\n")) {
                    $released = true;
                    fwrite($pipes[0], "finish\n");
                }

                return '';
            }, 1);
            try {
                new ResponseEmitter()->emit($response);
            } finally {
                ob_end_clean();
            }
            $this->assertTrue($released);
            $this->assertSame("data: first\n\ndata: second\n\n", $output);
            $this->assertFalse($body->isReadable());
        } finally {
            $body?->close();
            $this->stopServer($process, $pipes);
            unset($GLOBALS['mockedHeadersSent'], $GLOBALS['mockedHeaders'], $GLOBALS['mockedCookies']);
        }
    }

    /**
     * Redirects close unfinished response bodies before opening the next connection.
     */
    public function testStreamingRedirect(): void
    {
        [$process, $pipes, $url] = $this->startServer();
        $client = new Client(['adapter' => Stream::class, 'timeout' => 2]);
        $redirectBody = null;
        $manager = new EventManager();
        $manager->on('HttpClient.afterSend', function (ClientEvent $event) use (&$redirectBody): void {
            if ($event->getResult()->isRedirect()) {
                $redirectBody = $event->getResult()->getBody();
            }
        });
        $client->setEventManager($manager);
        $body = null;
        try {
            $response = $client->get($url . '/redirect', [], ['stream' => true, 'redirect' => 1]);
            $this->assertNotNull($redirectBody);
            $this->assertFalse($redirectBody->isReadable());
            $this->assertSame(200, $response->getStatusCode());
            $body = $response->getBody();
            $this->assertSame("data: first\n\n", $body->read(13));
            fwrite($pipes[0], "finish\n");
            $this->assertSame("data: second\n\n", $body->getContents());
        } finally {
            $body?->close();
            $this->stopServer($process, $pipes);
        }
    }

    /**
     * Read timeouts are raised during consumption and close the connection.
     */
    public function testReadTimeout(): void
    {
        [$process, $pipes, $url] = $this->startServer();
        $body = null;
        try {
            $client = new Client(['adapter' => Stream::class, 'timeout' => 2]);
            $response = $client->get($url . '/timeout', [], ['stream' => true, 'readTimeout' => 0.05]);
            $body = $response->getBody();
            try {
                $body->read(8192);
                $this->fail('Expected a read timeout.');
            } catch (NetworkException $error) {
                $this->assertStringContainsString('timed out', $error->getMessage());
                $this->assertSame($url . '/timeout', (string)$error->getRequest()->getUri());
                $this->assertFalse($body->isReadable());
            }
        } finally {
            $body?->close();
            $this->stopServer($process, $pipes);
        }
    }

    /**
     * @return array{resource, array<resource>, string}
     */
    protected function startServer(): array
    {
        if (!function_exists('proc_open')) {
            $this->markTestSkipped('proc_open is required for the local HTTP server.');
        }
        $process = proc_open(
            [PHP_BINARY, TEST_APP . 'TestApp/Http/Client/streaming_server.php'],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
        );
        $this->assertIsResource($process);
        stream_set_timeout($pipes[1], 5);
        $address = fgets($pipes[1]);
        if ($address === false) {
            $this->stopServer($process, $pipes);
            $this->fail('Local HTTP server did not start.');
        }

        return [$process, $pipes, 'http://' . trim($address)];
    }

    /**
     * @param resource $process The server process.
     * @param array<resource> $pipes Server pipes.
     * @return void
     */
    protected function stopServer($process, array $pipes): void
    {
        proc_terminate($process);
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        proc_close($process);
    }
}
