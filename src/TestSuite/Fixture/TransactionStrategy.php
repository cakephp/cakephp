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

use Cake\Database\Connection;
use RuntimeException;

/**
 * Fixture strategy that wraps fixtures in a transaction that is rolled back
 * after each test.
 *
 * Any test that calls Connection::rollback(true) will break this strategy.
 */
class TransactionStrategy implements FixtureStrategyInterface
{
    /**
     * @var \Cake\TestSuite\Fixture\FixtureHelper
     */
    protected $helper;

    /**
     * @var array<\Cake\Datasource\FixtureInterface>
     */
    protected $fixtures = [];

    /**
     * Initialize strategy.
     */
    public function __construct()
    {
        $this->helper = new FixtureHelper();
    }

    /**
     * @inheritDoc
     */
    public function setupTest(array $fixtureNames): void
    {
        if (empty($fixtureNames)) {
            return;
        }

        $this->fixtures = $this->helper->loadFixtures($fixtureNames);

        $this->helper->runPerConnection(function ($connection) {
            if ($connection instanceof Connection) {
                assert(
                    $connection->inTransaction() === false,
                    'Cannot start transaction strategy inside a transaction. ' .
                    'Ensure you have closed all open transactions.'
                );
                $connection->enableSavePoints();
                if (!$connection->isSavePointsEnabled()) {
                    throw new RuntimeException(sprintf(
                        'Could not enable save points for the `%s` connection. ' .
                            'Your database needs to support savepoints in order to use ' .
                            'TransactionStrategy.',
                        $connection->configName()
                    ));
                }

                $connection->begin();
                $connection->createSavePoint('__fixtures__');
            }
        }, $this->fixtures);

        $this->helper->insert($this->fixtures);
    }

    /**
     * @inheritDoc
     */
    public function teardownTest(): void
    {
        $this->helper->runPerConnection(function ($connection) {
            if ($connection->inTransaction()) {
                $connection->rollback(true);
            }
        }, $this->fixtures);
    }
}
