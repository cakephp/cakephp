<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\FixtureManager;
use Cake\TestSuite\TestCase;
use Exception;

/**
 * This class helps in testing the life-cycle of fixtures inside a CakeTestCase
 */
class FixturizedTestCase extends TestCase
{
    /**
     * Fixtures to use in this test
     *
     * @var array
     */
    protected $fixtures = ['core.Categories', 'core.Articles'];

    /**
     * test that the shared fixture is correctly set
     *
     * @return void
     */
    public function testFixturePresent()
    {
        $this->assertInstanceOf(FixtureManager::class, $this->fixtureManager);
    }

    /**
     * test that it is possible to load fixtures on demand
     *
     * @return void
     */
    public function testFixtureLoadOnDemand()
    {
        $this->loadFixtures('Categories');
    }

    /**
     * test that calling loadFixtures without args loads all fixtures
     *
     * @return void
     */
    public function testLoadAllFixtures()
    {
        $this->loadFixtures();
        $article = $this->getTableLocator()->get('Articles')->get(1);
        $this->assertSame(1, $article->id);
        $category = $this->getTableLocator()->get('Categories')->get(1);
        $this->assertSame(1, $category->id);
    }

    /**
     * test that a test is marked as skipped using skipIf and its first parameter evaluates to true
     *
     * @return void
     */
    public function testSkipIfTrue()
    {
        $this->skipIf(true);
    }

    /**
     * test that a test is not marked as skipped using skipIf and its first parameter evaluates to false
     *
     * @return void
     */
    public function testSkipIfFalse()
    {
        $this->skipIf(false);
    }

    /**
     * test that a fixtures are unloaded even if the test throws exceptions
     *
     * @return void
     * @throws \Exception
     */
    public function testThrowException()
    {
        throw new Exception();
    }
}
