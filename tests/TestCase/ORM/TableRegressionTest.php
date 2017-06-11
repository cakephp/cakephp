<?php
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
 * @since         3.2.13
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Contains regression test for the Table class
 */
class TableRegressionTest extends TestCase
{

    /**
     * Fixture to be used
     *
     * @var array
     */
    public $fixtures = [
        'core.authors',
    ];

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        TableRegistry::clear();
    }

    /**
     * Tests that an exception is thrown if the transaction is aborted
     * in the afterSave callback
     *
     * @see https://github.com/cakephp/cakephp/issues/9079
     * @expectedException \Cake\ORM\Exception\RolledbackTransactionException
     * @return void
     */
    public function testAfterSaveRollbackTransaction()
    {
        $table = TableRegistry::get('Authors');
        $table->eventManager()->on(
            'Model.afterSave',
            function () use ($table) {
                $table->connection()->rollback();
            }
        );
        $entity = $table->newEntity(['name' => 'Jon']);
        $table->save($entity);
    }
}
