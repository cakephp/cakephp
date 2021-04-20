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

use Cake\TestSuite\Fixture\FixtureDataManager;
use Cake\TestSuite\Fixture\FixtureLoader;
use Cake\TestSuite\Fixture\StateResetStrategyInterface;
use Cake\TestSuite\Fixture\TransactionStrategy;
use InvalidArgumentException;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeTestHook;
use ReflectionClass;

/**
 * PHPUnit extension to integrate CakePHP's data-only fixtures.
 */
class FixtureSchemaExtension implements
    AfterTestHook,
    BeforeTestHook
{
    /**
     * @var \Cake\TestSuite\Fixture\StateResetStrategyInterface
     */
    protected $state;

    /**
     * Constructor.
     *
     * @param string $stateStrategy The state management strategy to use.
     * @psalm-param class-string<\Cake\TestSuite\Fixture\StateResetStrategyInterface> $stateStrategy
     */
    public function __construct(string $stateStrategy = TransactionStrategy::class)
    {
        $enableLogging = in_array('--debug', $_SERVER['argv'] ?? [], true);
        $class = new ReflectionClass($stateStrategy);
        if (!$class->implementsInterface(StateResetStrategyInterface::class)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid stateStrategy provided. State strategies must implement `%s`. Got `%s`.',
                StateResetStrategyInterface::class,
                $stateStrategy
            ));
        }
        $this->state = new $stateStrategy($enableLogging);

        FixtureLoader::setInstance(new FixtureDataManager());
    }

    /**
     * BeforeTestHook implementation
     *
     * @param string $test The test class::method being run.
     * @return void
     */
    public function executeBeforeTest(string $test): void
    {
        $this->state->beforeTest($test);
    }

    /**
     * AfterTestHook implementation
     *
     * @param string $test The test class::method being run.
     * @param float $time The duration the test took.
     * @return void
     */
    public function executeAfterTest(string $test, float $time): void
    {
        $this->state->afterTest($test);
    }
}
