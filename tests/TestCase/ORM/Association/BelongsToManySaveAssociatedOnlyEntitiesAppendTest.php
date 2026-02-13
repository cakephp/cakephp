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
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Mockery;

/**
 * Tests BelongsToManySaveAssociatedOnlyEntitiesAppendTest class
 */
class BelongsToManySaveAssociatedOnlyEntitiesAppendTest extends TestCase
{
    /**
     * @var \Cake\ORM\Table
     */
    protected $tag;

    /**
     * @var \Cake\ORM\Table
     */
    protected $article;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tag = new Table(['alias' => 'Tags', 'table' => 'tags']);
        $this->tag->setSchema([
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ]);
        $this->article = new Table(['alias' => 'Articles', 'table' => 'articles']);
        $this->article->setSchema([
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ]);
    }

    /**
     * Test that saveAssociated() ignores non entity values.
     */
    public function testSaveAssociatedOnlyEntitiesAppend(): void
    {
        $connection = ConnectionManager::get('test');
        /** @var \Cake\Test\TestCase\ORM\Association\MockedTable&\Mockery\MockInterface $table */
        $table = Mockery::mock(new MockedTable(['table' => 'tags', 'connection' => $connection]))
            ->makePartial();
        $table->setPrimaryKey('id');

        $config = [
            'sourceTable' => $this->article,
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

        $table->shouldReceive('saveAssociated')->never();

        $association = new BelongsToMany('Tags', $config);
        $association->saveAssociated($entity);
    }
}

// phpcs:disable
class MockedTable extends Table
{
    public function saveAssociated() {}
    public function schema() {}
}
// phpcs:enable
