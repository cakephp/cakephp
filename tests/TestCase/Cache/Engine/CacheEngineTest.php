<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Exception\InvalidArgumentException;
use Cake\TestSuite\TestCase;
use TestApp\Cache\Engine\TestAppCacheEngine;

class CacheEngineTest extends TestCase
{
    public function durationProvider(): array
    {
        return [
            [null, 10],
            [2, 2],
            [new \DateInterval('PT1S'), 1],
            [new \DateInterval('P1D'), 86400],
        ];
    }

    /**
     * Test duration with null, int and DateInterval multiple format.
     *
     * @dataProvider durationProvider
     */
    public function testDuration($ttl, $expected): void
    {
        $engine = new TestAppCacheEngine();
        $engine->setConfig(['duration' => 10]);

        $result = $engine->getDuration($ttl);

        $this->assertSame($result, $expected);
    }

    /**
     * Test duration value should be \DateInterval, int or null.
     */
    public function testDurationException(): void
    {
        $engine = new TestAppCacheEngine();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TTL values must be one of null, int, \DateInterval');
        $engine->getDuration('ttl');
    }
}
