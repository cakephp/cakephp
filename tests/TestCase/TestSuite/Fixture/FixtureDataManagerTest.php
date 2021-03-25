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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite\Fixture;

use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FixtureInterface;
use Cake\TestSuite\Fixture\FixtureDataManager;
use Cake\TestSuite\TestCase;
use UnexpectedValueException;

class FixtureDataManagerTest extends TestCase
{
    /**
     * Schema fixtures to load.
     *
     * @var string[]
     */
    public $fixtures = ['core.Articles', 'core.Comments'];

    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        // Clear fixture tables so we can test insertions.
        $db = ConnectionManager::get('test');
        $query = $db->newQuery()
            ->delete()
            ->from('comments')
            ->where('1=1');
        $query->execute()->closeCursor();

        $query = $db->newQuery()
            ->delete()
            ->from('articles')
            ->where('1=1');
        $query->execute()->closeCursor();
    }

    /**
     * Data provider for valid fixture names.
     *
     * @return array
     */
    public function invalidProvider(): array
    {
        return [
            ['core.Nope'],
            ['app.Nope'],
            ['plugin.NotThere.Nope'],
        ];
    }

    /**
     * Test that fixturize() errors on missing fixture
     *
     * @dataProvider invalidProvider
     * @param string $name Fixture name
     * @return void
     */
    public function testFixturizeErrorOnUnknown($name)
    {
        $manager = new FixtureDataManager();
        $this->fixtures = [$name];

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Referenced fixture class');
        $manager->fixturize($this);
    }

    /**
     * Data provider for valid fixture names.
     *
     * @return array
     */
    public function validProvider(): array
    {
        return [
            ['core.Articles'],
            ['plugin.TestPlugin.Articles'],
            ['plugin.Company/TestPluginThree.Articles'],
        ];
    }

    /**
     * Test that fixturize() loads fixtures.
     *
     * @dataProvider validProvider
     * @param string $name The fixture name
     * @return void
     */
    public function testFixturizeLoads($name)
    {
        $this->setAppNamespace();
        // Also loads TestPlugin
        $this->loadPlugins(['Company/TestPluginThree']);

        $manager = new FixtureDataManager();
        $this->fixtures = [$name];
        $manager->fixturize($this);

        $fixtures = $manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertInstanceOf(FixtureInterface::class, $fixtures[$name]);
    }

    /**
     * Test that fixturize() loads fixtures.
     *
     * @return void
     */
    public function testFixturizeLoadsMultipleFixtures()
    {
        $manager = new FixtureDataManager();
        $manager->fixturize($this);

        $fixtures = $manager->loaded();
        $this->assertCount(2, $fixtures);
        $this->assertInstanceOf(FixtureInterface::class, $fixtures['core.Articles']);
        $this->assertInstanceOf(FixtureInterface::class, $fixtures['core.Comments']);
    }

    /**
     * loadSingle on a known fixture.
     *
     * @return void
     */
    public function testLoadSingleValid()
    {
        $manager = new FixtureDataManager();
        $manager->fixturize($this);

        $manager->loadSingle('Articles');
        $db = ConnectionManager::get('test');
        $stmt = $db->newQuery()->select(['count()'])->from('articles')->execute();
        $result = $stmt->fetch()[0];
        $stmt->closeCursor();

        $this->assertEquals(3, $result);
    }

    /**
     * loadSingle on a unknown fixture.
     *
     * @return void
     */
    public function testLoadSingleInvalid()
    {
        $manager = new FixtureDataManager();
        $manager->fixturize($this);

        $this->expectException(UnexpectedValueException::class);
        $manager->loadSingle('Nope');
    }

    /**
     * Test load()
     *
     * @return void
     */
    public function testLoad()
    {
        $manager = new FixtureDataManager();
        $manager->fixturize($this);

        $manager->load($this);

        $db = ConnectionManager::get('test');
        $stmt = $db->newQuery()->select(['count()'])->from('articles')->execute();
        $result = $stmt->fetch()[0];
        $stmt->closeCursor();
        $this->assertEquals(3, $result);

        $stmt = $db->newQuery()->select(['count()'])->from('comments')->execute();
        $result = $stmt->fetch()[0];
        $stmt->closeCursor();
        $this->assertEquals(6, $result);
    }
}
