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
namespace Cake\Test\TestCase\TestSuite;

use Cake\Datasource\ConnectionManager;
use Cake\Test\Fixture\ArticlesFixture;
use Cake\TestSuite\Fixture\FixtureHelper;
use Cake\TestSuite\Fixture\TestFixture;
use Cake\TestSuite\TestCase;
use Company\TestPluginThree\Test\Fixture\ArticlesFixture as CompanyArticlesFixture;
use TestPlugin\Test\Fixture\ArticlesFixture as PluginArticlesFixture;
use TestPlugin\Test\Fixture\Blog\CommentsFixture as PluginCommentsFixture;
use UnexpectedValueException;

class FixtureHelperTest extends TestCase
{
    protected $fixtures = ['core.Articles'];

    /**
     * Clean up after test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
        ConnectionManager::dropAlias('test1');
        ConnectionManager::dropAlias('test2');
    }

    /**
     * Tests loading fixtures.
     */
    public function testLoadFixtures(): void
    {
        $this->setAppNamespace('TestApp');
        $this->loadPlugins(['TestPlugin']);
        $fixtures = (new FixtureHelper())->loadFixtures([
            'core.Articles',
            'plugin.TestPlugin.Articles',
            'plugin.TestPlugin.Blog/Comments',
            'plugin.Company/TestPluginThree.Articles',
        ]);
        $this->assertNotEmpty($fixtures);
        $this->assertInstanceOf(ArticlesFixture::class, $fixtures[ArticlesFixture::class]);
        $this->assertInstanceOf(PluginArticlesFixture::class, $fixtures[PluginArticlesFixture::class]);
        $this->assertInstanceOf(PluginCommentsFixture::class, $fixtures[PluginCommentsFixture::class]);
        $this->assertInstanceOf(CompanyArticlesFixture::class, $fixtures[CompanyArticlesFixture::class]);
    }

    /**
     * Tests loading missing fixtures.
     */
    public function testLoadMissingFixtures(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Could not find fixture `core.ThisIsMissing`');
        (new FixtureHelper())->loadFixtures(['core.ThisIsMissing']);
    }

    /**
     * Tests loading duplicate fixtures.
     */
    public function testLoadDulicateFixtures(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Found duplicate fixture `core.Articles`');
        (new FixtureHelper())->loadFixtures(['core.Articles','core.Articles']);
    }

    /**
     * Tests running callback per connection
     */
    public function testPerConnection(): void
    {
        $fixture1 = $this->createMock(TestFixture::class);
        $fixture1->expects($this->once())
            ->method('connection')
            ->will($this->returnValue('test1'));

        $fixture2 = $this->createMock(TestFixture::class);
        $fixture2->expects($this->once())
            ->method('connection')
            ->will($this->returnValue('test2'));

        ConnectionManager::alias('test', 'test1');
        ConnectionManager::alias('test', 'test2');

        $numCalls = 0;
        (new FixtureHelper())->runPerConnection(function () use (&$numCalls) {
            ++$numCalls;
        }, [$fixture1, $fixture2]);
        $this->assertSame(2, $numCalls);
    }

    /**
     * Tests inserting fixtures.
     */
    public function testInsertFixtures(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        $connection->newQuery()->delete('articles')->execute()->closeCursor();
        $rows = $connection->newQuery()->select('*')->from('articles')->execute();
        $this->assertEmpty($rows->fetchAll());
        $rows->closeCursor();

        $helper = new FixtureHelper();
        $helper->insert($helper->loadFixtures(['core.Articles']));
        $rows = $connection->newQuery()->select('*')->from('articles')->execute();
        $this->assertNotEmpty($rows->fetchAll());
        $rows->closeCursor();
    }

    /**
     * Tests truncating fixtures.
     */
    public function testTruncateFixtures(): void
    {
        /**
         * @var \Cake\Database\Connection $connection
         */
        $connection = ConnectionManager::get('test');
        $rows = $connection->newQuery()->select('*')->from('articles')->execute();
        $this->assertNotEmpty($rows->fetchAll());
        $rows->closeCursor();

        $helper = new FixtureHelper();
        $helper->truncate($helper->loadFixtures(['core.Articles']));
        $rows = $connection->newQuery()->select('*')->from('articles')->execute();
        $this->assertEmpty($rows->fetchAll());
        $rows->closeCursor();
    }
}
