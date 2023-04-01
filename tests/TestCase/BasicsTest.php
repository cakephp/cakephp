<?php
declare(strict_types=1);

/**
 * BasicsTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase;

use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;

/**
 * BasicsTest class
 */
class BasicsTest extends TestCase
{
    /**
     * Test that works in tandem with testEventManagerReset2 to
     * test the EventManager reset.
     *
     * The return value is passed to testEventManagerReset2 as
     * an arguments.
     */
    public function testEventManagerReset1(): EventManager
    {
        $eventManager = EventManager::instance();
        $this->assertInstanceOf(EventManager::class, $eventManager);

        return $eventManager;
    }

    /**
     * Test if the EventManager is reset between tests.
     *
     * @depends testEventManagerReset1
     */
    public function testEventManagerReset2(EventManager $prevEventManager): void
    {
        $this->assertInstanceOf(EventManager::class, $prevEventManager);
        $this->assertNotSame($prevEventManager, EventManager::instance());
    }
}
