<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Cache\Engine;

use Cake\TestSuite\TestCase;
use DateInterval;
use TestApp\Cache\Engine\TestAppCacheEngine;

class CacheEngineTest extends TestCase
{
    public static function durationProvider(): array
    {
        return [
            [null, 10],
            [2, 2],
            [new DateInterval('PT1S'), 1],
            [new DateInterval('P1D'), 86400],
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
}
