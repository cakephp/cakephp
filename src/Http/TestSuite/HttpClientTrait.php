<?php
declare(strict_types=1);

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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\TestSuite;

use Cake\Http\Client;
use Cake\Http\Client\Response;

/**
 * Define mock responses and have mocks automatically cleared.
 */
trait HttpClientTrait
{
    /**
     * Resets mocked responses
     *
     * @after
     * @return void
     */
    public function cleanupMockResponses(): void
    {
        Client::clearMockResponses();
    }

    /**
     * Create a new response.
     *
     * @param int $code The response code to use. Defaults to 200
     * @param list<string> $headers A list of headers for the response. Example `Content-Type: application/json`
     * @param string $body The body for the response.
     * @return \Cake\Http\Client\Response
     */
    public function newClientResponse(int $code = 200, array $headers = [], string $body = ''): Response
    {
        $headers = array_merge(["HTTP/1.1 {$code}"], $headers);

        return new Response($headers, $body);
    }

    /**
     * Add a mock response for a POST request.
     *
     * @param string $url The URL to mock
     * @param \Cake\Http\Client\Response $response The response for the mock.
     * @param array<string, mixed> $options Additional options. See Client::addMockResponse()
     * @return void
     */
    public function mockClientPost(string $url, Response $response, array $options = []): void
    {
        Client::addMockResponse('POST', $url, $response, $options);
    }

    /**
     * Add a mock response for a GET request.
     *
     * @param string $url The URL to mock
     * @param \Cake\Http\Client\Response $response The response for the mock.
     * @param array<string, mixed> $options Additional options. See Client::addMockResponse()
     * @return void
     */
    public function mockClientGet(string $url, Response $response, array $options = []): void
    {
        Client::addMockResponse('GET', $url, $response, $options);
    }

    /**
     * Add a mock response for a PATCH request.
     *
     * @param string $url The URL to mock
     * @param \Cake\Http\Client\Response $response The response for the mock.
     * @param array<string, mixed> $options Additional options. See Client::addMockResponse()
     * @return void
     */
    public function mockClientPatch(string $url, Response $response, array $options = []): void
    {
        Client::addMockResponse('PATCH', $url, $response, $options);
    }

    /**
     * Add a mock response for a PUT request.
     *
     * @param string $url The URL to mock
     * @param \Cake\Http\Client\Response $response The response for the mock.
     * @param array<string, mixed> $options Additional options. See Client::addMockResponse()
     * @return void
     */
    public function mockClientPut(string $url, Response $response, array $options = []): void
    {
        Client::addMockResponse('PUT', $url, $response, $options);
    }

    /**
     * Add a mock response for a DELETE request.
     *
     * @param string $url The URL to mock
     * @param \Cake\Http\Client\Response $response The response for the mock.
     * @param array<string, mixed> $options Additional options. See Client::addMockResponse()
     * @return void
     */
    public function mockClientDelete(string $url, Response $response, array $options = []): void
    {
        Client::addMockResponse('DELETE', $url, $response, $options);
    }
}

// phpcs:disable
class_alias(
    'Cake\Http\TestSuite\HttpClientTrait',
    'Cake\TestSuite\HttpClientTrait'
);
// phpcs:enable
