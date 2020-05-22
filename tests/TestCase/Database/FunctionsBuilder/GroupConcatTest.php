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
namespace Cake\Test\TestCase\Database\FunctionsBuilder;

use Cake\Database\Expression\AggregateExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\FunctionsBuilder;
use Cake\Database\ValueBinder;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;

/**
 * Tests FunctionsBuilder class
 */
class GroupConcatTest extends TestCase
{
    /**
     * @var string[]
     */
    protected $fixtures = [
        'core.Articles',
        'core.Tags',
        'core.ArticlesTags',
    ];

    /**
     * @var \Cake\Database\FunctionsBuilder
     */
    protected $functions;

    /**
     * @var \Cake\Database\Connection
     */
    protected $connection;

    /**
     * Setups a mock for FunctionsBuilder
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');
        $this->functions = new FunctionsBuilder();
        $this->loadFixtures('Articles', 'Tags', 'ArticlesTags');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->connection);
    }

    /**
     * Tests generating a GROUP_CONCAT() function.
     *
     * @return void
     */
    public function testGroupConcatSyntax()
    {
        $function = $this->functions->groupConcat([new IdentifierExpression('field')]);
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('GROUP_CONCAT(field SEPARATOR :se0)', $function->sql(new ValueBinder()));

        $function = $this->functions->groupConcat(
            [new IdentifierExpression('field1'), new IdentifierExpression('field2')],
            ['sort1' => 'DESC', 'sort2' => 'ASC'],
            ', ',
            true
        );
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame(
            'GROUP_CONCAT(DISTINCT field1, field2 ORDER BY sort1 DESC, sort2 ASC SEPARATOR :se0)',
            $function->sql(new ValueBinder())
        );

        $function = $this->functions->groupConcat(
            [[new FrozenTime('2020-02-24'), 'field2'], ['date']],
            ['sort1'],
            ':'
        );
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame(
            'GROUP_CONCAT(:se0, :se1 ORDER BY sort1 SEPARATOR :se2)',
            $function->sql(new ValueBinder())
        );
    }

    /**
     * Tests querying using a GROUP_CONCAT() function with some properties.
     *
     * @return void
     */
    public function testGroupConcat()
    {
        $articles = $this->getTableLocator()->get('Articles')->setConnection($this->connection);
        $articles->belongsToMany('Tags', ['through' => 'ArticlesTags']);
        $articleQuery = $articles->find('all');
        $articleQuery->contain(['Tags'])->group(['Articles.id'])
            ->select(
                [
                    'article_id' => 'Articles.id',
                    'tag_ids' => $articleQuery->func()->groupConcat(
                        ['Tags.name' => 'identifier']
                    ),
                ]
            )->matching('Tags')->order(['Articles.id' => 'ASC']);
        $this->assertSame(
            [['article_id' => 1, 'tag_ids' => 'tag1tag2'],['article_id' => 2, 'tag_ids' => 'tag1tag3']],
            $articleQuery->execute()->fetchAll('assoc')
        );
    }

    /**
     * Tests querying using a GROUP_CONCAT() function with all properties.
     *
     * @return void
     */
    public function testGroupConcatFull()
    {
        $articles = $this->getTableLocator()->get('Articles')->setConnection($this->connection);
        $articles->belongsToMany(
            'Tags',
            [
                'through' => 'ArticlesTags',
                'strategy' => 'select',
            ]
        );
        $articles->Tags->setStrategy('select');
        $articleQuery = $articles->find('all');
        $articleQuery->contain(['Tags' => ['strategy' => 'select']])->group(['Articles.id'])
            ->select(
                [
                    'article_id' => 'Articles.id',
                    'tag_ids' => $articleQuery->func()->groupConcat(
                        [['Tags.name' => 'identifier'], []],
                        ['Tags.name' => 'DESC'],
                        ':',
                        true
                    ),
                ]
            )->matching('Tags')->order(['Articles.id' => 'ASC']);
        $this->assertSame(
            [['article_id' => 1, 'tag_ids' => 'tag2:tag1'], ['article_id' => 2, 'tag_ids' => 'tag3:tag1']],
            $articleQuery->execute()->fetchAll('assoc')
        );
    }
}
