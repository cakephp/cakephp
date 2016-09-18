<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Driver;

use Cake\Database\Query;
use Cake\TestSuite\TestCase;

/**
 * @group StandardSQL
 */
class StandardSQLTest extends TestCase
{
    use MockDriverTrait;

    /**
     * @var string
     */
    protected $_driver_class = 'Cake\Database\Driver\Sqlite';

    /**
     * Defines what connection class to mock.
     *
     * @var string
     */
    protected $_connection_class = '\Cake\Database\Connection';

    /**
     * Defines all the drivers
     *
     * @return array
     */
    public static function drivers()
    {
        return [
            'Cake\Database\Driver\Mysql',
            'Cake\Database\Driver\Postgres',
            'Cake\Database\Driver\Sqlite',
            'Cake\Database\Driver\Sqlserver'
        ];
    }

    /**
     * Test update with limit
     *
     * @dataProvider drivers
     */
    public function testUpdateLimit($driver)
    {
        $this->newQuery(function (Query $query) {
            $query->update('articles')
                ->set(['title' => 'FooBar'])
                ->where(['published' => true])
                ->limit(5);

            $this->assertEquals('UPDATE articles SET title = :c0 WHERE published = 1 LIMIT 5', $query->sql());
        });
    }

}
