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
namespace Cake\TestSuite\Fixture;

/**
 * Fixture state strategy that combines other strategies
 *
 * Useful when your application is using multiple storage
 * systems that require their own state resetting. For example,
 * a relational database and elasticsearch.
 */
class ResetCollection implements ResetStrategyInterface
{
    /**
     * @var \Cake\TestSuite\Fixture\StateResetStrategyInterface[]
     */
    protected $items = [];

    /**
     * Constructor
     *
     * @param \Cake\TestSuite\Fixture\StateResetStrategyInterface[] $items The strategies to aggregate.
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * Call setupTest on each item.
     *
     * @return void
     */
    public function setupTest(): void
    {
        foreach ($this->items as $item) {
            $item->setupTest();
        }
    }

    /**
     * Call teardownTest on each item.
     *
     * @return void
     */
    public function teardownTest(): void
    {
        foreach ($this->items as $item) {
            $item->teardownTest();
        }
    }
}
