<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestSuite;

use Cake\Core\Plugin;
use Cake\Database\ConnectionManager;
use Cake\TestSuite\Fixture\FixtureManager;
use Cake\TestSuite\TestCase;

/**
 * Fixture manager test case.
 */
class FixtureManagerTest extends TestCase
{

    /**
     * Setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->manager = new FixtureManager();
    }

    /**
     * Test loading core fixtures.
     *
     * @return void
     */
    public function testFixturizeCore()
    {
        $test = $this->getMock('Cake\TestSuite\TestCase');
        $test->fixtures = ['core.articles'];
        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('core.articles', $fixtures);
        $this->assertInstanceOf('Cake\Test\Fixture\ArticlesFixture', $fixtures['core.articles']);
    }

    /**
     * Test loading app fixtures.
     *
     * @return void
     */
    public function testFixturizePlugin()
    {
        Plugin::load('TestPlugin');

        $test = $this->getMock('Cake\TestSuite\TestCase');
        $test->fixtures = ['plugin.test_plugin.articles'];
        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('plugin.test_plugin.articles', $fixtures);
        $this->assertInstanceOf(
            'TestPlugin\Test\Fixture\ArticlesFixture',
            $fixtures['plugin.test_plugin.articles']
        );
    }

    /**
     * Test loading app fixtures.
     *
     * @return void
     */
    public function testFixturizeCustom()
    {
        $test = $this->getMock('Cake\TestSuite\TestCase');
        $test->fixtures = ['plugin.Company/TestPluginThree.articles'];
        $this->manager->fixturize($test);
        $fixtures = $this->manager->loaded();
        $this->assertCount(1, $fixtures);
        $this->assertArrayHasKey('plugin.Company/TestPluginThree.articles', $fixtures);
        $this->assertInstanceOf(
            'Company\TestPluginThree\Test\Fixture\ArticlesFixture',
            $fixtures['plugin.Company/TestPluginThree.articles']
        );
    }

    /**
     * Test that unknown types are handled gracefully.
     *
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Referenced fixture class "Test\Fixture\Derp.derpFixture" not found. Fixture "derp.derp" was referenced
     */
    public function testFixturizeInvalidType()
    {
        $test = $this->getMock('Cake\TestSuite\TestCase');
        $test->fixtures = ['derp.derp'];
        $this->manager->fixturize($test);
    }
}
