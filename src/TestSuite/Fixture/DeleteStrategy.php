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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Fixture;

/**
 * Fixture strategy that deletes all rows from the fixture tables at the end of test.
 *
 * A drop-in alternative to {@link \Cake\TestSuite\Fixture\TruncateStrategy}: fixtures are
 * inserted before each test and the tables are emptied afterwards, without a wrapping
 * transaction, so `afterCommit` listeners behave as they do in the application.
 *
 * `TRUNCATE` is a DDL statement, and on most database servers it does more work than
 * removing the rows - InnoDB for example drops and recreates the tablespace of every
 * table it is issued against. Deleting the handful of rows a fixture holds is usually
 * a lot cheaper, so test suites with many fixtures can spend noticeably less time in
 * teardown with this strategy.
 *
 * The trade off is that `DELETE` does not reset auto increment counters. Fixtures
 * whose records omit their primary key, and which other fixtures or assertions then
 * refer to by id, need that counter to start from 1 in every test and have to keep
 * using `TruncateStrategy`.
 */
class DeleteStrategy implements FixtureStrategyInterface
{
    /**
     * @var \Cake\TestSuite\Fixture\FixtureHelper
     */
    protected FixtureHelper $helper;

    /**
     * @var array<\Cake\Datasource\FixtureInterface>
     */
    protected array $fixtures = [];

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
        if (!$fixtureNames) {
            return;
        }

        $this->fixtures = $this->helper->loadFixtures($fixtureNames);
        $this->helper->insert($this->fixtures);
    }

    /**
     * @inheritDoc
     */
    public function teardownTest(): void
    {
        $this->helper->delete($this->fixtures);
    }
}
