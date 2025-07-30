<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Engine\NullEngine;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * ArrayEngineTest class
 */
class NullEngineTest extends TestCase
{
    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();

        Cache::enable();
        Cache::clearAll();

        Cache::drop('null');
        Cache::setConfig('null', [
            'className' => NullEngine::class,
        ]);
    }

    public function testReadMany(): void
    {
        $keys = [
            'key1',
            'key2',
            'key3',
        ];

        $result1 = Cache::readMany($keys, 'null');

        $this->assertSame([
            'key1' => null,
            'key2' => null,
            'key3' => null,
        ], $result1);

        $e = new Exception('Cache key not found');
        $result2 = Cache::pool('null')->getMultiple($keys, $e);

        $this->assertSame([
            'key1' => $e,
            'key2' => $e,
            'key3' => $e,
        ], $result2);
    }
}
