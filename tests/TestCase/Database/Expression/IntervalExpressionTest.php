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

use Cake\Database\Expression\IntervalExpression;
use Cake\Database\Query;
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

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
    }

    /**
     * Tests interval values.
     *
     * @return void
     */
    public function testInterval()
    {
        $utc = new \DateTimeZone('UTC');
        $query = new Query($this->connection);
        $interval = \DateInterval::createFromDateString('+1 year + 2 seconds + 111 milliseconds');
        $iExp = new IntervalExpression(new FrozenTime('2020-04-17 02:03:02.21', $utc), $interval);
        $query->select([ $iExp ]);
        $stm = $query->execute();
        $result = $stm->fetchColumn(0);
        $resultDt = Type::build('datetimefractional')->toPHP($result, $this->connection->getDriver());
        $this->assertContainsEquals(
            $resultDt,
            [new FrozenTime('2021-04-17 02:03:04.321000', $utc)]
        );
    }
}
