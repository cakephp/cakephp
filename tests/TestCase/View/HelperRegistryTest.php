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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Core\Exception\CakeException;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\MissingHelperException;
use Cake\View\Helper\FormHelper;
use Cake\View\Helper\HtmlHelper;
use Cake\View\HelperRegistry;
use Cake\View\View;
use TestApp\View\Helper\HtmlAliasHelper;
use TestPlugin\View\Helper\OtherHelperHelper;

/**
 * HelperRegistryTest
 */
class HelperRegistryTest extends TestCase
{
    /**
     * @var \Cake\View\HelperRegistry
     */
    protected $Helpers;

    /**
     * @var \Cake\Event\EventManager
     */
    protected $Events;

    /**
     * @var \Cake\View\View
     */
    protected $View;

    /**
     * setUp
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->View = new View();
        $this->Events = $this->View->getEventManager();
        $this->Helpers = new HelperRegistry($this->View);
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        $this->clearPlugins();
        unset($this->Helpers, $this->View);
        parent::tearDown();
    }

    /**
     * test loading helpers.
     */
    public function testLoad(): void
    {
        $result = $this->Helpers->load('Html');
        $this->assertInstanceOf(HtmlHelper::class, $result);
        $this->assertInstanceOf(HtmlHelper::class, $this->Helpers->Html);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html'], $result, 'loaded() results are wrong.');
    }

    /**
     * test lazy loading of helpers
     */
    public function testLazyLoad(): void
    {
        $result = $this->Helpers->Html;
        $this->assertInstanceOf(HtmlHelper::class, $result);

        $result = $this->Helpers->Form;
        $this->assertInstanceOf(FormHelper::class, $result);

        $this->View->setPlugin('TestPlugin');
        $this->loadPlugins(['TestPlugin']);
        $result = $this->Helpers->OtherHelper;
        $this->assertInstanceOf(OtherHelperHelper::class, $result);
    }

    /**
     * test lazy loading of helpers
     */
    public function testLazyLoadException(): void
    {
        $this->expectException(MissingHelperException::class);
        $this->Helpers->NotAHelper;
    }

    /**
     * Test that loading helpers subscribes to events.
     */
    public function testLoadSubscribeEvents(): void
    {
        $this->Helpers->load('Html', ['className' => HtmlAliasHelper::class]);
        $result = $this->Events->listeners('View.afterRender');
        $this->assertCount(1, $result);
    }

    /**
     * Tests loading as an alias
     */
    public function testLoadWithAlias(): void
    {
        $result = $this->Helpers->load('Html', ['className' => HtmlAliasHelper::class]);
        $this->assertInstanceOf(HtmlAliasHelper::class, $result);
        $this->assertInstanceOf(HtmlAliasHelper::class, $this->Helpers->Html);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html'], $result, 'loaded() results are wrong.');

        $result = $this->Helpers->load('Html');
        $this->assertInstanceOf(HtmlAliasHelper::class, $result);
    }

    /**
     * Test loading helpers with aliases and plugins.
     */
    public function testLoadWithAliasAndPlugin(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $result = $this->Helpers->load('SomeOther', ['className' => 'TestPlugin.OtherHelper']);
        $this->assertInstanceOf(OtherHelperHelper::class, $result);
        $this->assertInstanceOf(OtherHelperHelper::class, $this->Helpers->SomeOther);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['SomeOther'], $result, 'loaded() results are wrong.');
    }

    /**
     * test that the enabled setting disables the helper.
     */
    public function testLoadWithEnabledFalse(): void
    {
        $result = $this->Helpers->load('Html', ['enabled' => false]);
        $this->assertInstanceOf(HtmlHelper::class, $result);
        $this->assertInstanceOf(HtmlHelper::class, $this->Helpers->Html);

        $this->assertEmpty($this->Events->listeners('View.beforeRender'));
    }

    /**
     * test missinghelper exception
     */
    public function testLoadMissingHelper(): void
    {
        $this->expectException(MissingHelperException::class);
        $this->Helpers->load('ThisHelperShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin helper.
     */
    public function testLoadPluginHelper(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $result = $this->Helpers->load('TestPlugin.OtherHelper');
        $this->assertInstanceOf(OtherHelperHelper::class, $result, 'Helper class is wrong.');
        $this->assertInstanceOf(OtherHelperHelper::class, $this->Helpers->OtherHelper, 'Class is wrong');
    }

    /**
     * test loading helpers with dotted aliases
     */
    public function testLoadPluginHelperDottedAlias(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $result = $this->Helpers->load('thing.helper', [
            'className' => 'TestPlugin.OtherHelper',
        ]);
        $this->assertInstanceOf(OtherHelperHelper::class, $result, 'Helper class is wrong.');
        $this->assertInstanceOf(
            OtherHelperHelper::class,
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
     */
    public function testReset(): void
    {
        static::setAppNamespace();

        $instance = $this->Helpers->load('EventListenerTest');
        $this->assertSame(
            $instance,
            $this->Helpers->EventListenerTest,
            'Instance in registry should be the same as previously loaded'
        );
        $this->assertCount(1, $this->Events->listeners('View.beforeRender'));

        $this->Helpers->reset();
        $this->assertCount(0, $this->Events->listeners('View.beforeRender'));

        $this->assertNotSame($instance, $this->Helpers->load('EventListenerTest'));
    }

    /**
     * Test unloading.
     */
    public function testUnload(): void
    {
        static::setAppNamespace();

        $instance = $this->Helpers->load('EventListenerTest');
        $this->assertSame(
            $instance,
            $this->Helpers->EventListenerTest,
            'Instance in registry should be the same as previously loaded'
        );
        $this->assertCount(1, $this->Events->listeners('View.beforeRender'));

        $this->assertSame($this->Helpers, $this->Helpers->unload('EventListenerTest'));
        $this->assertCount(0, $this->Events->listeners('View.beforeRender'));
    }

    /**
     * Test that unloading a none existing helper triggers an error.
     */
    public function testUnloadUnknown(): void
    {
        $this->expectException(MissingHelperException::class);
        $this->expectExceptionMessage('Helper class `FooHelper` could not be found.');
        $this->Helpers->unload('Foo');
    }

    /**
     * Loading a helper with no config should "just work"
     *
     * The addToAssertionCount call is to record that no exception was thrown
     */
    public function testLoadMultipleTimesNoConfig(): void
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
     */
    public function testLoadMultipleTimesAlreadyConfigured(): void
    {
        $this->Helpers->load('Html', ['same' => 'stuff']);
        $this->Helpers->load('Html');
        $this->addToAssertionCount(1);
    }

    /**
     * Loading a helper overriding defaults to default value
     * should "just work"
     */
    public function testLoadMultipleTimesDefaultConfigValuesWorks(): void
    {
        $this->Helpers->load('Number', ['engine' => 'Cake\I18n\Number']);
        $this->Helpers->load('Number');
        $this->addToAssertionCount(1);
    }

    /**
     * Loading a helper with different config, should throw an exception
     */
    public function testLoadMultipleTimesDifferentConfigured(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('The `Html` alias has already been loaded');
        $this->Helpers->load('Html');
        $this->Helpers->load('Html', ['same' => 'stuff']);
    }

    /**
     * Loading a helper with different config, should throw an exception
     */
    public function testLoadMultipleTimesDifferentConfigValues(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('The `Html` alias has already been loaded');
        $this->Helpers->load('Html', ['key' => 'value']);
        $this->Helpers->load('Html', ['key' => 'new value']);
    }

    /**
     * Test ObjectRegistry normalizeArray
     */
    public function testArrayIsNormalized(): void
    {
        $config = [
            'SomeHelper',
            'SomeHelper' => [
                'value' => 1,
                'value2' => 2,
            ],
            'Plugin.SomeOtherHelper' => [
                'value' => 3,
                'value2' => 4,
            ],
        ];
        $result = $this->Helpers->normalizeArray($config);
        $expected = [
            'SomeHelper' => [
                'value' => 1,
                'value2' => 2,
            ],
            'SomeOtherHelper' => [
                'className' => 'Plugin.SomeOtherHelper',
                'value' => 3,
                'value2' => 4,
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that calling normalizeArray multiple times does
     * not nest the configuration.
     */
    public function testArrayIsNormalizedAfterMultipleCalls(): void
    {
        $config = [
            'SomeHelper' => [
                'value' => 1,
                'value2' => 2,
            ],
            'Plugin.SomeOtherHelper' => [
                'value' => 1,
                'value2' => 2,
            ],
            'SomeAliasesHelper' => [
                'className' => 'Plugin.SomeHelper',
            ],
        ];

        $result1 = $this->Helpers->normalizeArray($config);
        $result2 = $this->Helpers->normalizeArray($result1);
        $expected = [
            'SomeHelper' => [
                'value' => 1,
                'value2' => 2,
            ],
            'SomeOtherHelper' => [
                'className' => 'Plugin.SomeOtherHelper',
                'value' => 1,
                'value2' => 2,
            ],
            'SomeAliasesHelper' => [
                'className' => 'Plugin.SomeHelper',
            ],
        ];
        $this->assertEquals($expected, $result2);
    }
}
