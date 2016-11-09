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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\View\Helper;
use Cake\View\HelperRegistry;
use Cake\View\View;

/**
 * Extended HtmlHelper
 */
class HtmlAliasHelper extends Helper
{

    public function afterRender($viewFile)
    {
    }
}

/**
 * HelperRegistryTest
 */
class HelperRegistryTest extends TestCase
{

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->View = new View();
        $this->Events = $this->View->eventManager();
        $this->Helpers = new HelperRegistry($this->View);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        Plugin::unload();
        unset($this->Helpers, $this->View);
        parent::tearDown();
    }

    /**
     * test loading helpers.
     *
     * @return void
     */
    public function testLoad()
    {
        $result = $this->Helpers->load('Html');
        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $result);
        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $this->Helpers->Html);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html'], $result, 'loaded() results are wrong.');
    }

    /**
     * test lazy loading of helpers
     *
     * @return void
     */
    public function testLazyLoad()
    {
        $result = $this->Helpers->Html;
        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $result);

        $result = $this->Helpers->Form;
        $this->assertInstanceOf('Cake\View\Helper\FormHelper', $result);

        $this->View->plugin = 'TestPlugin';
        Plugin::load(['TestPlugin']);
        $result = $this->Helpers->OtherHelper;
        $this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $result);
    }

    /**
     * test lazy loading of helpers
     *
     * @expectedException \Cake\View\Exception\MissingHelperException
     * @return void
     */
    public function testLazyLoadException()
    {
        $this->Helpers->NotAHelper;
    }

    /**
     * Test that loading helpers subscribes to events.
     *
     * @return void
     */
    public function testLoadSubscribeEvents()
    {
        $this->Helpers->load('Html', ['className' => __NAMESPACE__ . '\HtmlAliasHelper']);
        $result = $this->Events->listeners('View.afterRender');
        $this->assertCount(1, $result);
    }

    /**
     * Tests loading as an alias
     *
     * @return void
     */
    public function testLoadWithAlias()
    {
        $result = $this->Helpers->load('Html', ['className' => __NAMESPACE__ . '\HtmlAliasHelper']);
        $this->assertInstanceOf(__NAMESPACE__ . '\HtmlAliasHelper', $result);
        $this->assertInstanceOf(__NAMESPACE__ . '\HtmlAliasHelper', $this->Helpers->Html);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html'], $result, 'loaded() results are wrong.');

        $result = $this->Helpers->load('Html');
        $this->assertInstanceOf(__NAMESPACE__ . '\HtmlAliasHelper', $result);
    }

    /**
     * Test loading helpers with aliases and plugins.
     *
     * @return void
     */
    public function testLoadWithAliasAndPlugin()
    {
        Plugin::load('TestPlugin');
        $result = $this->Helpers->load('SomeOther', ['className' => 'TestPlugin.OtherHelper']);
        $this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $result);
        $this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $this->Helpers->SomeOther);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['SomeOther'], $result, 'loaded() results are wrong.');
    }

    /**
     * test that the enabled setting disables the helper.
     *
     * @return void
     */
    public function testLoadWithEnabledFalse()
    {
        $result = $this->Helpers->load('Html', ['enabled' => false]);
        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $result);
        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $this->Helpers->Html);

        $this->assertEmpty($this->Events->listeners('View.beforeRender'));
    }

    /**
     * test missinghelper exception
     *
     * @expectedException \Cake\View\Exception\MissingHelperException
     * @return void
     */
    public function testLoadMissingHelper()
    {
        $this->Helpers->load('ThisHelperShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin helper.
     *
     * @return void
     */
    public function testLoadPluginHelper()
    {
        Plugin::load(['TestPlugin']);

        $result = $this->Helpers->load('TestPlugin.OtherHelper');
        $this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $result, 'Helper class is wrong.');
        $this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $this->Helpers->OtherHelper, 'Class is wrong');
    }

    /**
     * test loading helpers with dotted aliases
     *
     * @return void
     */
    public function testLoadPluginHelperDottedAlias()
    {
        Plugin::load(['TestPlugin']);

        $result = $this->Helpers->load('thing.helper', [
            'className' => 'TestPlugin.OtherHelper',
        ]);
        $this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $result, 'Helper class is wrong.');
        $this->assertInstanceOf(
            'TestPlugin\View\Helper\OtherHelperHelper',
            $this->Helpers->get('thing.helper'),
            'Class is wrong'
        );
        $this->assertTrue($this->Helpers->has('thing.helper'));
        $this->assertFalse($this->Helpers->has('thing'));
        $this->assertFalse($this->Helpers->has('helper'));

        $this->Helpers->unload('thing.helper');
        $this->assertFalse($this->Helpers->has('thing.helper'), 'Should be gone now.');
    }

    /**
     * Test reset.
     *
     * @return void
     */
    public function testReset()
    {
        Configure::write('App.namespace', 'TestApp');

        $instance = $this->Helpers->load('EventListenerTest');
        $this->assertSame(
            $instance,
            $this->Helpers->EventListenerTest,
            'Instance in registry should be the same as previously loaded'
        );
        $this->assertCount(1, $this->Events->listeners('View.beforeRender'));

        $this->assertNull($this->Helpers->reset(), 'No return expected');
        $this->assertCount(0, $this->Events->listeners('View.beforeRender'));

        $this->assertNotSame($instance, $this->Helpers->load('EventListenerTest'));
    }

    /**
     * Test unloading.
     *
     * @return void
     */
    public function testUnload()
    {
        Configure::write('App.namespace', 'TestApp');

        $instance = $this->Helpers->load('EventListenerTest');
        $this->assertSame(
            $instance,
            $this->Helpers->EventListenerTest,
            'Instance in registry should be the same as previously loaded'
        );
        $this->assertCount(1, $this->Events->listeners('View.beforeRender'));

        $this->assertNull($this->Helpers->unload('EventListenerTest'), 'No return expected');
        $this->assertCount(0, $this->Events->listeners('View.beforeRender'));
    }

    /**
     * Loading a helper with no config should "just work"
     *
     * The addToAssertionCount call is to record that no exception was thrown
     *
     * @return void
     */
    public function testLoadMultipleTimesNoConfig()
    {
        $this->Helpers->load('Html');
        $this->Helpers->load('Html');
        $this->addToAssertionCount(1);
    }

    /**
     * Loading a helper with bespoke config, where the subsequent load specifies no
     * config should "just work"
     *
     * The addToAssertionCount call is to record that no exception was thrown
     *
     * @return void
     */
    public function testLoadMultipleTimesAlreadyConfigured()
    {
        $this->Helpers->load('Html', ['same' => 'stuff']);
        $this->Helpers->load('Html');
        $this->addToAssertionCount(1);
    }

    /**
     * Loading a helper overriding defaults to default value
     * should "just work"
     *
     * @return void
     */
    public function testLoadMultipleTimesDefaultConfigValuesWorks()
    {
        $this->Helpers->load('Number', ['engine' => 'Cake\I18n\Number']);
        $this->Helpers->load('Number');
        $this->addToAssertionCount(1);
    }

    /**
     * Loading a helper with different config, should throw an exception
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage The "Html" alias has already been loaded with the following
     * @return void
     */
    public function testLoadMultipleTimesDifferentConfigured()
    {
        $this->Helpers->load('Html');
        $this->Helpers->load('Html', ['same' => 'stuff']);
    }

    /**
     * Loading a helper with different config, should throw an exception
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage The "Html" alias has already been loaded with the following
     * @return void
     */
    public function testLoadMultipleTimesDifferentConfigValues()
    {
        $this->Helpers->load('Html', ['key' => 'value']);
        $this->Helpers->load('Html', ['key' => 'new value']);
    }
}
