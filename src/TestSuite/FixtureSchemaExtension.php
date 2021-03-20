<?php
declare(strict_types=1);

namespace Cake\TestSuite;

use Cake\TestSuite\Fixture\TransactionStrategy;

use PHPUnit\Runner\BeforeTestHook;
use PHPUnit\Runner\AfterTestHook;

/**
 * PHPUnit extension to integrate CakePHP's data-only fixtures.
 */
class FixtureSchemaExtension implements
     AfterTestHook,
     BeforeTestHook
{
    protected $state;

    public function __construct(string $stateStrategy = TransactionStrategy::class)
    {
        $enableLogging = in_array('--debug', $_SERVER['argv'] ?? [], true);
        $this->state = new $stateStrategy($enableLogging);

        // TODO Create the singleton fixture manager that tests will use.
    }

    public function executeBeforeTest(string $test): void
    {
        $this->state->beforeTest();
    }

    public function executeAfterTest(string $test, float $time): void
    {
        $this->state->afterTest();
    }
}
