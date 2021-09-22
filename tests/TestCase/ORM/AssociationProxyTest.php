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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * Tests the features related to proxying methods from the Association
 * class to the Table class
 */
class AssociationProxyTest extends TestCase
{
    /**
     * Fixtures to be loaded
     *
     * @var array<string>
     */
    protected $fixtures = [
        'core.Articles', 'core.Authors', 'core.Comments',
    ];

    /**
     * Tests that it is possible to get associations as a property
     */
    public function testAssociationAsProperty(): void
    {
        $articles = $this->getTableLocator()->get('articles');
        $articles->hasMany('comments');
        $articles->belongsTo('authors');
        $this->assertTrue(isset($articles->authors));
        $this->assertTrue(isset($articles->comments));
        $this->assertFalse(isset($articles->posts));
        $this->assertSame($articles->getAssociation('authors'), $articles->authors);
        $this->assertSame($articles->getAssociation('comments'), $articles->comments);
    }

    /**
     * Tests that getting a bad property throws exception
     */
    public function testGetBadAssociation(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('You have not defined');
        $articles = $this->getTableLocator()->get('articles');
        $articles->posts;
    }

    /**
     * Test that find() with empty conditions generates valid SQL
     */
    public function testFindEmptyConditions(): void
    {
        $table = $this->getTableLocator()->get('Users');
        $table->hasMany('Articles', [
            'foreignKey' => 'author_id',
            'conditions' => '',
        ]);
        $query = $table->Articles->find('list', ['limit' => 2]);
        $this->assertCount(2, $query->all());
    }

    /**
     * Tests that the proxied updateAll will preserve conditions set for the association
     */
    public function testUpdateAllFromAssociation(): void
    {
        $articles = $this->getTableLocator()->get('articles');
        $comments = $this->getTableLocator()->get('comments');
        $articles->hasMany('comments', ['conditions' => ['published' => 'Y']]);
        $articles->comments->updateAll(['comment' => 'changed'], ['article_id' => 1]);
        $changed = $comments->find()->where(['comment' => 'changed'])->count();
        $this->assertSame(3, $changed);
    }

    /**
     * Tests that the proxied updateAll uses the association finder
     */
    public function testUpdateAllFromAssociationFinder(): void
    {
        $this->setAppNamespace('TestApp');

        $articles = $this->getTableLocator()->get('articles');
        $authors = $this->getTableLocator()->get('authors');
        // Exclude a record from the published finder.
        $articles->updateAll(['published' => 'N'], ['id' => 1]);

        $authors->hasMany('Articles', [
            'finder' => 'published',
        ]);
        $authors->Articles->updateAll(['published' => '?'], '1=1');
        $missed = $articles->find()->where(['published' => 'Y'])->count();
        $this->assertSame(0, $missed);

        $remaining = $articles->find()->where(['published' => 'N'])->count();
        $this->assertSame(1, $remaining);
    }

    /**
     * Tests that the proxied deleteAll preserves conditions set for the association
     */
    public function testDeleteAllFromAssociationConditions(): void
    {
        $articles = $this->getTableLocator()->get('articles');
        $comments = $this->getTableLocator()->get('comments');
        $articles->hasMany('comments', ['conditions' => ['published' => 'Y']]);
        $articles->comments->deleteAll(['article_id' => 1]);
        $remaining = $comments->find()->where(['article_id' => 1])->count();
        $this->assertSame(1, $remaining);
    }

    /**
     * Tests that the proxied deleteAll uses the association finder
     */
    public function testDeleteAllFromAssociationFinder(): void
    {
        $this->setAppNamespace('TestApp');

        $articles = $this->getTableLocator()->get('articles');
        $authors = $this->getTableLocator()->get('authors');
        // Exclude a record from the published finder.
        $articles->updateAll(['published' => 'N'], ['id' => 1]);

        $authors->hasMany('Articles', [
            'finder' => 'published',
        ]);
        $authors->Articles->deleteAll('1=1');
        $remaining = $articles->find()->all();
        $this->assertCount(1, $remaining);
        $this->assertSame(['N'], $remaining->extract('published')->toList());
    }

    /**
     * Tests that it is possible to get associations as a property
     */
    public function testAssociationAsPropertyProxy(): void
    {
        $articles = $this->getTableLocator()->get('articles');
        $authors = $this->getTableLocator()->get('authors');
        $articles->belongsTo('authors');
        $authors->hasMany('comments');
        $this->assertTrue(isset($articles->authors->comments));
        $this->assertSame($authors->getAssociation('comments'), $articles->authors->comments);
    }

    /**
     * Tests that methods are proxied from the Association to the target table
     */
    public function testAssociationMethodProxy(): void
    {
        $articles = $this->getTableLocator()->get('articles');
        $mock = $this->getMockBuilder('Cake\ORM\Table')
            ->addMethods(['crazy'])
            ->getMock();
        $articles->belongsTo('authors', [
            'targetTable' => $mock,
        ]);

        $mock->expects($this->once())->method('crazy')
            ->with('a', 'b')
            ->will($this->returnValue('thing'));
        $this->assertSame('thing', $articles->authors->crazy('a', 'b'));
    }
}
