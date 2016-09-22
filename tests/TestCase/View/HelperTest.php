<?php
/**
 * HelperTest file
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper;
use Cake\View\View;

class TestHelper extends Helper
{

    /**
     * Settings for this helper.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'key1' => 'val1',
        'key2' => ['key2.1' => 'val2.1', 'key2.2' => 'val2.2']
    ];

    /**
     * Helpers for this helper.
     *
     * @var array
     */
    public $helpers = ['Html', 'TestPlugin.OtherHelper'];

    /**
     * expose a method as public
     *
     * @param string $options
     * @param string $exclude
     * @param string $insertBefore
     * @param string $insertAfter
     * @return void
     */
    public function parseAttributes($options, $exclude = null, $insertBefore = ' ', $insertAfter = null)
    {
        return $this->_parseAttributes($options, $exclude, $insertBefore, $insertAfter);
    }
}

/**
 * HelperTest class
 */
class HelperTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Router::reload();
        $this->View = new View();
        $this->Helper = new Helper($this->View);
        $this->Helper->request = new Request();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Configure::delete('Asset');

        Plugin::unload();
        unset($this->Helper, $this->View);
    }

    /**
     * Test settings merging
     *
     * @return void
     */
    public function testSettingsMerging()
    {
        $Helper = new TestHelper($this->View, [
            'key3' => 'val3',
            'key2' => ['key2.2' => 'newval']
        ]);
        $expected = [
            'key1' => 'val1',
            'key2' => ['key2.1' => 'val2.1', 'key2.2' => 'newval'],
            'key3' => 'val3'
        ];
        $this->assertEquals($expected, $Helper->config());
    }

    /**
     * test lazy loading helpers is seamless
     *
     * @return void
     */
    public function testLazyLoadingHelpers()
    {
        Plugin::load(['TestPlugin']);

        $Helper = new TestHelper($this->View);
        $this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $Helper->OtherHelper);
        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $Helper->Html);
    }

    /**
     * test that a helpers Helper is not 'attached' to the collection
     *
     * @return void
     */
    public function testThatHelperHelpersAreNotAttached()
    {
        Plugin::loadAll();

        $events = $this->getMockBuilder('\Cake\Event\EventManager')->getMock();
        $this->View->eventManager($events);

        $events->expects($this->never())
            ->method('attach');

        $Helper = new TestHelper($this->View);
        $Helper->OtherHelper;
    }

    /**
     * test that the lazy loader doesn't duplicate objects on each access.
     *
     * @return void
     */
    public function testLazyLoadingUsesReferences()
    {
        $Helper = new TestHelper($this->View);
        $resultA = $Helper->Html;
        $resultB = $Helper->Html;

        $resultA->testprop = 1;
        $this->assertEquals($resultA->testprop, $resultB->testprop);
    }

    /**
     * Tests __debugInfo
     *
     * @return void
     */
    public function testDebugInfo()
    {
        $Helper = new TestHelper($this->View);

        $expected = [
            'helpers' => [
                'Html',
                'TestPlugin.OtherHelper'
            ],
            'theme' => null,
            'plugin' => null,
            'fieldset' => [],
            'tags' => [],
            'implementedEvents' => [
            ],
            '_config' => [
                'key1' => 'val1',
                'key2' => ['key2.1' => 'val2.1', 'key2.2' => 'val2.2']
            ]
        ];
        $result = $Helper->__debugInfo();
        $this->assertEquals($expected, $result);
    }
}
