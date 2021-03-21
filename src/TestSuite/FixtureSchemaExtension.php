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
namespace Cake\TestSuite;

use Cake\TestSuite\Fixture\TransactionStrategy;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeTestHook;

/**
 * PHPUnit extension to integrate CakePHP's data-only fixtures.
 */
class FixtureSchemaExtension implements
    AfterTestHook,
    BeforeTestHook
{
    /**
     * @var object
     */
    protected $state;

    /**
     * Constructor.
     *
     * @param string $stateStrategy The state management strategy to use.
     */
    public function __construct(string $stateStrategy = TransactionStrategy::class)
    {
        $enableLogging = in_array('--debug', $_SERVER['argv'] ?? [], true);
        $this->state = new $stateStrategy($enableLogging);

        // TODO Create the singleton fixture manager that tests will use.
    }

    /**
     * BeforeTestHook implementation
     *
     * @param string $test The test being run.
     * @return void
     */
    public function executeBeforeTest(string $test): void
    {
        $this->state->beforeTest();
    }

    /**
     * AfterTestHook implementation
     *
     * @param string $test The test being run.
     * @param float $time The duration the test took.
     * @return void
     */
    public function executeAfterTest(string $test, float $time): void
    {
        $this->state->afterTest();
    }
}
