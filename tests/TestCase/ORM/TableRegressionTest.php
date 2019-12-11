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
 * @since         3.2.13
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\TestSuite\TestCase;
use InvalidArgumentException;

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
    protected $fixtures = [
        'core.Authors',
    ];

    /**
     * Tests that an exception is thrown if the transaction is aborted
     * in the afterSave callback
     *
     * @see https://github.com/cakephp/cakephp/issues/9079
     * @return void
     */
    public function testAfterSaveRollbackTransaction()
    {
        $this->expectException(\Cake\ORM\Exception\RolledbackTransactionException::class);
        $table = $this->getTableLocator()->get('Authors');
        $table->getEventManager()->on(
            'Model.afterSave',
            function () use ($table) {
                $table->getConnection()->rollback();
            }
        );
        $entity = $table->newEntity(['name' => 'Jon']);
        $table->save($entity);
    }

    /**
     * Ensure that saving to a table with no primary key fails.
     *
     * @return void
     */
    public function testSaveNoPrimaryKeyException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('primary key');
        $table = $this->getTableLocator()->get('Authors');
        $table->getSchema()->dropConstraint('primary');

        $entity = $table->find()->first();
        $entity->name = 'new name';
        $table->save($entity);
    }
}
