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
namespace Cake\Test\TestCase\Database;

use Cake\Database\Query;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Tests WindowExpression class
 */
class WindowQueryTests extends TestCase
{
    protected $fixtures = [
        'core.Comments',
    ];

    public $autoFixtures = false;

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection = null;

    protected $skipTests = false;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');

        $enable = env('ENABLE_WINDOW_TESTS');
        $this->skipTests = !$enable || $enable === 'false';
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testPartitions()
    {
        $this->skipIf($this->skipTests);
        $this->loadFixtures('Comments');

        $query = new Query($this->connection);
        $result = $query
            ->select(['num_rows' => $query->func()->count('*')->over()])
            ->from('comments')
            ->execute()
            ->fetchAll();
        $this->assertCount(6, $result);

        $query = new Query($this->connection);
        $result = $query
            ->select(['num_rows' => $query->func()->count('*')->partition('article_id')])
            ->from('comments')
            ->order(['article_id'])
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals(4, $result[0]['num_rows']);

        $query = new Query($this->connection);
        $result = $query
            ->select(['num_rows' => $query->func()->count('*')->partition('article_id')->order('updated')])
            ->from('comments')
            ->order(['updated'])
            ->execute()
            ->fetchAll('assoc');
        $this->assertEquals(1, $result[0]['num_rows']);
        $this->assertEquals(4, $result[3]['num_rows']);
        $this->assertEquals(1, $result[4]['num_rows']);
    }
}
