<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Expression;

use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Expression\IntervalExpression;
use Cake\Database\Query;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Type;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;

/**
 * Tests IntervalExpression class
 */
class IntervalExpressionTest extends TestCase
{
    /**
     * @var \Cake\Database\Driver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $driver;

    /**
     * @var \Cake\Datasource\ConnectionInterface
     */
    protected $connection;

    /**
     * @var array
     */
    protected $data;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->data['tz'] = $utc = new \DateTimeZone('UTC');
        $this->data['query'] = new Query($this->connection);
        $this->data['interval'] = \DateInterval::createFromDateString('+1 year + 2 seconds + 111 milliseconds');
        $this->data['date'] = '2021-04-17 02:03:04.321000';
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->connection, $this->data);
        parent::tearDown();
    }

    /**
     * Tests interval values.
     *
     * @return void
     */
    public function testInterval()
    {
        // Query using direct date value
        $iExp = new IntervalExpression(
            new FrozenTime($this->data['date'], $this->data['tz']),
            $this->data['interval']
        );
        $this->data['query']->select([ $iExp ]);
        $stm = $this->data['query']->execute();
        $result = $stm->fetchColumn(0);
        $resultDt = Type::build('datetimefractional')->toPHP($result, $this->connection->getDriver());
        $this->assertContainsEquals(
            $resultDt,
            [new FrozenTime('2022-04-17 02:03:06.432000', $this->data['tz'])]
        );
    }

    /**
     * Tests interval values using an expression.
     *
     * @return void
     */
    public function testIntervalWithExpression()
    {
        // Create temporary table and populate with date
        $tmpSchema = new TableSchema('interval_test');
        $tmpSchema->addColumn('interval_date', ['type' => 'datetimefractional', 'precision' => 6])
            ->setTemporary(true);
        foreach ($tmpSchema->createSql($this->connection) as $query) {
            $this->connection->query($query);
        }
        $this->connection->execute('INSERT INTO interval_test VALUES (\'' . $this->data['date'] . '\')');
        // Query using subquery
        $iExp = new IntervalExpression(
            (new Query($this->connection))->select(['interval_date'])->from('interval_test')->limit(1),
            $this->data['interval']
        );
        $this->data['query']->select([ $iExp ]);
        $stm = $this->data['query']->execute();
        $result = $stm->fetchColumn(0);
        $resultDt = Type::build('datetimefractional')->toPHP($result, $this->connection->getDriver());
        $this->assertContainsEquals(
            $resultDt,
            [new FrozenTime('2022-04-17 02:03:06.432000', $this->data['tz'])]
        );
    }
}
