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

    public function testClearEmpty(): void
    {
        $cluster = new class() extends RedisCluster {
            private array $data = [
                'node-1' => [
                    'key_1',
                ],
                'node-2' => [
                    'key_2',
                ],
                'node-3' => [
                    'key_3',
                ],
            ];

            public function __construct()
            {
            }

            public function _masters(): array
            {
                return array_keys($this->data);
            }

            public function scan(&$iterator, $str_node, $pattern = null, $count = 0): bool|array
            {
                if (!isset($this->data[$str_node])) {
                    return false;
                }

                $keys = $this->data[$str_node];

                if ($iterator === null) {
                    $iterator = 0;
                } else {
                    $iterator++;
                }

                if (!isset($keys[$iterator])) {
                    return false;
                }

                return [ $keys[$iterator] ];
            }

            public function unlink($key, ...$other_keys): int
            {
                $count = 0;
                foreach ($this->data as &$nodeData) {
                    $index = array_search($key, $nodeData, true);

                    if (is_int($index)) {
                        $count++;
                        unset($nodeData[$index]);
                    }
                }

                return $count;
            }
        };

        $this->engine->expects($this->atLeastOnce())
            ->method('connect')
            ->willReturn($cluster);

        $this->assertTrue($this->engine->init());
        $this->assertTrue($this->engine->clear());
    }
}
