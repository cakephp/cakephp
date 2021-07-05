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
namespace Cake\Test\TestCase\TestSuite;

use Cake\TestSuite\Fixture\CollectionResetStrategy;
use Cake\TestSuite\Fixture\ResetStrategyInterface;
use Cake\TestSuite\TestCase;

class CollectionResetStrategyTest extends TestCase
{
    /**
     * Test that setupTest calls items.
     */
    public function testSetupTest(): void
    {
        $one = $this->getMockBuilder(ResetStrategyInterface::class)->getMock();
        $two = $this->getMockBuilder(ResetStrategyInterface::class)->getMock();

        $one->expects($this->once())->method('setupTest');
        $two->expects($this->once())->method('setupTest');

        $one->expects($this->never())->method('teardownTest');

        $strategy = new CollectionResetStrategy([$one, $two]);
        $strategy->setupTest();
    }

    /**
     * Test that teardownTest calls items.
     */
    public function testTeardownTest(): void
    {
        $one = $this->getMockBuilder(ResetStrategyInterface::class)->getMock();
        $two = $this->getMockBuilder(ResetStrategyInterface::class)->getMock();

        $one->expects($this->once())->method('teardownTest');
        $two->expects($this->once())->method('teardownTest');

        $one->expects($this->never())->method('setupTest');

        $strategy = new CollectionResetStrategy([$one, $two]);
        $strategy->teardownTest();
    }
}
