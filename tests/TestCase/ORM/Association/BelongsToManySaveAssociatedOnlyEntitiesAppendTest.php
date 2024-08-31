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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Association;

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * Tests BelongsToManySaveAssociatedOnlyEntitiesAppendTest class
 */
class BelongsToManySaveAssociatedOnlyEntitiesAppendTest extends TestCase
{
    /**
     * Test that saveAssociated() ignores non entity values.
     */
    public function testSaveAssociatedOnlyEntitiesAppend(): void
    {
        $connection = ConnectionManager::get('test');
        $table = new class (['alias' => 'Tags', 'table' => 'tags', 'connection' => $connection]) extends Table {
            public function saveAssociated()
            {
                throw new Exception('Should not be called');
            }

            public function schema()
            {
            }
        };
        $table->setPrimaryKey('id');

        $articleTable = new class (['alias' => 'Articles', 'table' => 'articles']) extends Table {
        };
        $articleTable->setSchema([
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ]);

        $config = [
            'sourceTable' => $articleTable,
            'targetTable' => $table,
            'saveStrategy' => BelongsToMany::SAVE_APPEND,
        ];

        $entity = new Entity([
            'id' => 1,
            'title' => 'First Post',
            'tags' => [
                ['tag' => 'nope'],
                new Entity(['tag' => 'cakephp']),
            ],
        ]);

        $association = new BelongsToMany('Tags', $config);
        $this->assertInstanceOf(EntityInterface::class, $association->saveAssociated($entity));
    }
}
