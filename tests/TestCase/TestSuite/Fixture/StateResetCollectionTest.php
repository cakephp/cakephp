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

use Cake\TestSuite\Fixture\StateResetCollection;
use Cake\TestSuite\Fixture\StateResetStrategyInterface;
use Cake\TestSuite\TestCase;

class StateResetCollectionTest extends TestCase
{
    /**
     * Test that setupTest calls items.
     *
     * @return void
     */
    public function testSetupTest()
    {
        $one = $this->getMockBuilder(StateResetStrategyInterface::class)->getMock();
        $two = $this->getMockBuilder(StateResetStrategyInterface::class)->getMock();

        $one->expects($this->once())->method('setupTest');
        $two->expects($this->once())->method('setupTest');

        $one->expects($this->never())->method('teardownTest');

        $strategy = new StateResetCollection([$one, $two]);
        $strategy->setupTest();
    }

    /**
     * Test that teardownTest calls items.
     *
     * @return void
     */
    public function testTeardownTest()
    {
        $one = $this->getMockBuilder(StateResetStrategyInterface::class)->getMock();
        $two = $this->getMockBuilder(StateResetStrategyInterface::class)->getMock();

        $one->expects($this->once())->method('teardownTest');
        $two->expects($this->once())->method('teardownTest');

        $one->expects($this->never())->method('setupTest');

        $strategy = new StateResetCollection([$one, $two]);
        $strategy->teardownTest();
    }
}
