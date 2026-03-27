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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Response;

use Cake\Http\Response\JsonStreamResponse;
use Cake\TestSuite\TestCase;

class JsonStreamResponseTest extends TestCase
{
    public function testSimpleArrayStreaming(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $response = new JsonStreamResponse($data);
        $body = (string)$response->getBody();

        $this->assertSame('[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]', $body);
        $this->assertSame('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testWithRootWrapper(): void
    {
        $data = [
            ['id' => 1, 'title' => 'First'],
            ['id' => 2, 'title' => 'Second'],
        ];

        $response = new JsonStreamResponse($data, ['root' => 'articles']);
        $body = (string)$response->getBody();

        $this->assertSame('{"articles":[{"id":1,"title":"First"},{"id":2,"title":"Second"}]}', $body);
    }

    public function testWithEnvelope(): void
    {
        $data = [
            ['id' => 1, 'title' => 'First'],
        ];

        $response = new JsonStreamResponse($data, [
            'envelope' => ['meta' => ['total' => 100, 'page' => 1]],
            'dataKey' => 'articles',
        ]);
        $body = (string)$response->getBody();

        $decoded = json_decode($body, true);
        $this->assertSame(['total' => 100, 'page' => 1], $decoded['meta']);
        $this->assertSame([['id' => 1, 'title' => 'First']], $decoded['articles']);
    }

    public function testNdjsonFormat(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $response = new JsonStreamResponse($data, ['format' => 'ndjson']);
        $body = (string)$response->getBody();

        $expected = "{\"id\":1,\"name\":\"Alice\"}\n{\"id\":2,\"name\":\"Bob\"}\n";
        $this->assertSame($expected, $body);
        $this->assertSame('application/x-ndjson; charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testTransformCallback(): void
    {
        $data = [
            (object)['id' => 1, 'name' => 'Alice', 'secret' => 'hidden'],
            (object)['id' => 2, 'name' => 'Bob', 'secret' => 'hidden'],
        ];

        $response = new JsonStreamResponse($data, [
            'transform' => fn($item) => ['id' => $item->id, 'name' => $item->name],
        ]);
        $body = (string)$response->getBody();

        $this->assertSame('[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]', $body);
        $this->assertStringNotContainsString('secret', $body);
    }

    public function testEmptyIterable(): void
    {
        $response = new JsonStreamResponse([]);
        $body = (string)$response->getBody();

        $this->assertSame('[]', $body);
    }

    public function testEmptyIterableWithRoot(): void
    {
        $response = new JsonStreamResponse([], ['root' => 'data']);
        $body = (string)$response->getBody();

        $this->assertSame('{"data":[]}', $body);
    }

    public function testEmptyIterableNdjson(): void
    {
        $response = new JsonStreamResponse([], ['format' => 'ndjson']);
        $body = (string)$response->getBody();

        $this->assertSame('', $body);
    }

    public function testGeneratorInput(): void
    {
        $generator = function () {
            yield ['id' => 1];
            yield ['id' => 2];
            yield ['id' => 3];
        };

        $response = new JsonStreamResponse($generator());
        $body = (string)$response->getBody();

        $this->assertSame('[{"id":1},{"id":2},{"id":3}]', $body);
    }
}
