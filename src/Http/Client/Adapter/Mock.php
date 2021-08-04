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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client\Adapter;

use Cake\Http\Client\AdapterInterface;
use Cake\Http\Client\Response;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

/**
 * Implements sending requests to an array of stubbed responses
 *
 * This adapter is not intended for production use. Instead
 * it is the backend used by `Client::addMockResponse()`
 */
class Mock implements AdapterInterface
{
    /**
     * List of mocked responses.
     *
     * @var array
     */
    protected $responses = [];

    /**
     * Add a mocked response.
     *
     * ### Options
     *
     * - `match` An additional closure to match requests with.
     *
     * @param \Psr\Http\Message\RequestInterface $request A partial request to use for matching.
     * @param \Cake\Http\Client\Response $response The response that matches the request.
     * @param array $options See above.
     * @return void
     */
    public function addResponse(RequestInterface $request, Response $response, array $options): void
    {
        if (isset($mock['options']['match']) && !is_callable($mock['options']['match'])) {
            throw new InvalidArgumentException('The `match` option must be a callable');
        }
        $this->responses[] = [
            'request' => $request,
            'response' => $response,
            'options' => $options,
        ];
    }

    /**
     * Find a response if one exists.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to match
     * @param array $options Unused.
     * @return \Cake\Http\Client\Response[] The matched response or an empty array for no matches.
     */
    public function send(RequestInterface $request, array $options): array
    {
        $found = null;
        foreach ($this->responses as $mock) {
            if ($request->getMethod() !== $mock['request']->getMethod()) {
                continue;
            }
            if (!$this->urlMatches($request, $mock['request'])) {
                continue;
            }
            if (isset($mock['options']['match'])) {
                $match = $mock['options']['match']($request);
                if (!is_bool($match)) {
                    throw new InvalidArgumentException('Match callback return a non-boolean value.');
                }
                if (!$match) {
                    continue;
                }
            }
            $found = $mock['response'];
            break;
        }

        return $found ? [$found] : [];
    }

    /**
     * Check if the request URI matches the mock URI.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request being sent.
     * @param \Psr\Http\Message\RequestInterface $mock The request being mocked.
     * @return bool
     */
    protected function urlMatches(RequestInterface $request, RequestInterface $mock): bool
    {
        $requestUri = (string)$request->getUri();
        $mockUri = (string)$mock->getUri();
        if ($requestUri === $mockUri) {
            return true;
        }
        $starPosition = strrpos($mockUri, '/%2A');
        if ($starPosition === strlen($mockUri) - 4) {
            $mockUri = substr($mockUri, 0, $starPosition);

            return strpos($requestUri, $mockUri) === 0;
        }

        return false;
    }
}
