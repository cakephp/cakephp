<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Engine\RedisClusterEngine;
use Cake\TestSuite\TestCase;
use RedisCluster;
use ReflectionClass;

/**
 * RedisClusterEngineTest class
 */
class RedisClusterEngineTest extends TestCase
{
    /**
     * @var \Cake\Cache\Engine\RedisClusterEngine&\PHPUnit\Framework\MockObject\MockObject $engine
     */
    private RedisClusterEngine $engine;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = $this->getMockBuilder(RedisClusterEngine::class)
            ->onlyMethods(['connect'])
            ->getMock();
    }

    protected function tearDown(): void
    {
        unset($this->engine);
    }

    protected function mockCluster(array $data): RedisCluster
    {
        $class = new ReflectionClass(RedisCluster::class);

        /* Old version of the extension without return types */
        if ($class->getMethod('flushdb')?->getReturnType() === null) {
            return $this->mockClusterOld($data);
        }

        return new class ($data) extends RedisCluster {
            public function __construct(private array $data)
            {
            }

            /**
             * @phpcs:disable CakePHP.NamingConventions.ValidFunctionName.PublicWithUnderscore
             */
            public function _masters(): array
            {
                // @phpcs:enable CakePHP.NamingConventions.ValidFunctionName.PublicWithUnderscore

                return array_keys($this->data);
            }

            public function get(string $key): mixed
            {
                foreach ($this->data as $nodeData) {
                    if (array_key_exists($key, $nodeData)) {
                        return serialize($nodeData[$key]);
                    }
                }

                return false;
            }

            /**
             * Emulates scan and returns one key at a time, to ensure we properly handle
             * the iteration.
             */
            public function scan(&$iterator, $str_node, $pattern = null, $count = 0): bool|array
            {
                if (!isset($this->data[$str_node])) {
                    return false;
                }

                $keys = array_keys($this->data[$str_node]);

                if ($iterator === null || $iterator['node'] !== $str_node) {
                    $iterator = [
                        'node' => $str_node,
                        'count' => 0,
                    ];
                } else {
                    $iterator['count'] = $iterator['count'] + 1;
                }

                $index = $iterator['count'];

                if (!isset($keys[$index])) {
                    return false;
                }

                return [ $keys[$index] ];
            }

            public function unlink(array|string $key, string ...$other_keys): RedisCluster|int|false
            {
                $count = 0;
                foreach ($this->data as &$nodeData) {
                    if (array_key_exists($key, $nodeData)) {
                        $count++;
                        unset($nodeData[$key]);
                    }
                }

                return $count;
            }

            public function del(array|string $key, string ...$other_keys): RedisCluster|int|false
            {
                return $this->unlink($key, ...$other_keys);
            }

            public function flushdb(array|string $key_or_address, bool $async = false): RedisCluster|bool
            {
                $this->data = [];

                return true;
            }
        };
    }

    protected function mockClusterOld(array $data): RedisCluster
    {
        return new class ($data) extends RedisCluster {
            public function __construct(private array $data)
            {
            }

            /**
             * @phpcs:disable CakePHP.NamingConventions.ValidFunctionName.PublicWithUnderscore
             */
            public function _masters(): array
            {
                // @phpcs:enable CakePHP.NamingConventions.ValidFunctionName.PublicWithUnderscore

                return array_keys($this->data);
            }

            public function get($key)
            {
                foreach ($this->data as $nodeData) {
                    if (array_key_exists($key, $nodeData)) {
                        return serialize($nodeData[$key]);
                    }
                }

                return false;
            }

            /**
             * Emulates scan and returns one key at a time, to ensure we properly handle
             * the iteration.
             */
            public function scan(&$iterator, $str_node, $pattern = null, $count = 0): bool|array
            {
                if (!isset($this->data[$str_node])) {
                    return false;
                }

                $keys = array_keys($this->data[$str_node]);

                if ($iterator === null || $iterator['node'] !== $str_node) {
                    $iterator = [
                        'node' => $str_node,
                        'count' => 0,
                    ];
                } else {
                    $iterator['count'] = $iterator['count'] + 1;
                }

                $index = $iterator['count'];

                if (!isset($keys[$index])) {
                    return false;
                }

                return [ $keys[$index] ];
            }

            public function unlink($key, ...$other_keys): int
            {
                $count = 0;
                foreach ($this->data as &$nodeData) {
                    if (array_key_exists($key, $nodeData)) {
                        $count++;
                        unset($nodeData[$key]);
                    }
                }

                return $count;
            }

            public function del($key, ...$other_keys): int
            {
                return $this->unlink($key, ...$other_keys);
            }

            public function flushdb($key_or_address, $async = false)
            {
                $this->data = [];

                return true;
            }
        };
    }

    public function testClearEmpty(): void
    {
        $cluster = $this->mockCluster([
            'node-1' => [
                'cake_key_1' => 'value_1',
            ],
            'node-2' => [
                'cake_key_2' => 'value_2',
            ],
            'node-3' => [
                'cake_key_3' => 'value_3',
            ],
        ]);

        $this->engine->expects($this->atLeastOnce())
            ->method('connect')
            ->willReturn($cluster);

        $this->assertTrue($this->engine->init());
        $this->assertSame('value_1', $this->engine->get('key_1'));

        $this->assertTrue($this->engine->clear());
        $this->assertNull($this->engine->get('key_1'));
        $this->assertNull($this->engine->get('key_2'));
        $this->assertNull($this->engine->get('key_3'));
    }

    public function testClearEmptyFlushDb(): void
    {
        $cluster = $this->mockCluster([
            'node-1' => [
                'cake_key_1' => 'value_1',
            ],
            'node-2' => [
                'cake_key_2' => 'value_2',
            ],
            'node-3' => [
                'cake_key_3' => 'value_3',
            ],
        ]);

        $this->engine->expects($this->atLeastOnce())
            ->method('connect')
            ->willReturn($cluster);

        $this->assertTrue($this->engine->init());
        $this->assertSame('value_1', $this->engine->get('key_1'));

        // Test flushDB
        $this->engine->setConfig('clearUsesFlushDb', true);
        $this->assertTrue($this->engine->clear());
        $this->assertNull($this->engine->get('key_1'));
        $this->assertNull($this->engine->get('key_2'));
        $this->assertNull($this->engine->get('key_3'));
    }
}
