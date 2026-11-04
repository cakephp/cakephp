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
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * Tests ORM queries with association aliases that generate identifiers longer
 * than the database limit, with identifier shortening enabled.
 */
class LongAliasesTest extends TestCase
{
    protected array $fixtures = [
        'core.Articles',
        'core.Authors',
        'core.Comments',
    ];

    /**
     * Association names that generate `Alias__column` aliases over the Postgres limit
     * of 63 bytes, and have the same first 63 bytes.
     */
    protected const AUTHOR = 'OrganisationsBillingAddressesCountriesRegionsCitiesAuthorsPrimary';
    protected const COAUTHOR = 'OrganisationsBillingAddressesCountriesRegionsCitiesAuthorsPrimaryCo';
    protected const COMMENTS = 'OrganisationsBillingAddressesCountriesRegionsCitiesCommentsOfArticle';

    protected Table $articles;

    protected bool $shortening;

    protected function setUp(): void
    {
        parent::setUp();
        $driver = ConnectionManager::get('test')->getDriver();
        $this->shortening = $driver->isIdentifierShorteningEnabled();
        $driver->enableIdentifierShortening();

        $this->articles = $this->getTableLocator()->get('Articles');
        $this->articles->belongsTo(static::AUTHOR, [
            'className' => 'Authors',
            'foreignKey' => 'author_id',
            'propertyName' => 'author',
        ]);
        $this->articles->belongsTo(static::COAUTHOR, [
            'className' => 'Authors',
            'foreignKey' => 'author_id',
            'propertyName' => 'coauthor',
        ]);
        $this->articles->hasMany(static::COMMENTS, [
            'className' => 'Comments',
            'foreignKey' => 'article_id',
            'propertyName' => 'comments',
            'sort' => ['id' => 'ASC'],
        ]);
    }

    protected function tearDown(): void
    {
        ConnectionManager::get('test')->getDriver()->enableIdentifierShortening($this->shortening);
        parent::tearDown();
    }

    public function testContain(): void
    {
        $article = $this->articles->find()
            ->contain([static::AUTHOR, static::COAUTHOR, static::COMMENTS])
            ->where(['Articles.id' => 2])
            ->firstOrFail();

        $this->assertSame('Second Article', $article->title);
        $this->assertSame(3, $article->author->id);
        $this->assertSame('larry', $article->author->name);
        $this->assertSame(3, $article->coauthor->id);
        $this->assertSame('larry', $article->coauthor->name);
        $this->assertSame(
            ['First Comment for Second Article', 'Second Comment for Second Article'],
            array_column(array_map(fn($c) => $c->toArray(), $article->comments), 'comment'),
        );
    }

    public function testConditionsAndOrderOnLongAlias(): void
    {
        $titles = $this->articles->find()
            ->contain([static::AUTHOR])
            ->where([static::AUTHOR . '.name' => 'mariano'])
            ->orderBy([static::AUTHOR . '.id' => 'ASC', 'Articles.id' => 'DESC'])
            ->all()
            ->extract('title')
            ->toList();

        $this->assertSame(['Third Article', 'First Article'], $titles);
    }

    public function testMatching(): void
    {
        $results = $this->articles->find()
            ->matching(static::COMMENTS, fn($q) => $q->where([static::COMMENTS . '.user_id' => 2]))
            ->orderBy(['Articles.id' => 'ASC'])
            ->all()
            ->toList();

        $this->assertCount(2, $results);
        $this->assertSame(1, $results[0]->_matchingData[static::COMMENTS]->article_id);
        $this->assertSame('First Comment for First Article', $results[0]->_matchingData[static::COMMENTS]->comment);
        $this->assertSame(2, $results[1]->_matchingData[static::COMMENTS]->article_id);
    }

    public function testCount(): void
    {
        $query = $this->articles->find()
            ->contain([static::AUTHOR])
            ->where([static::AUTHOR . '.name' => 'mariano']);

        $this->assertSame(2, $query->count());
    }
}
