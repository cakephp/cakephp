<?php
declare(strict_types=1);

/**
 * HelperTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use TestApp\View\Helper\TestHelper;

/**
 * HelperTest class
 */
class HelperTest extends TestCase
{
    /**
     * @var \Cake\View\View
     */
    protected $View;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        Router::reload();
        $this->View = new View();
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('Asset');

        $this->clearPlugins();
        unset($this->View);
    }

    /**
     * Test settings merging
     */
    public function testSettingsMerging(): void
    {
        $Helper = new TestHelper($this->View, [
            'key3' => 'val3',
            'key2' => ['key2.2' => 'newval'],
        ]);
        $expected = [
            'key1' => 'val1',
            'key2' => ['key2.1' => 'val2.1', 'key2.2' => 'newval'],
            'key3' => 'val3',
        ];
        $this->assertEquals($expected, $Helper->getConfig());
    }

    /**
     * test lazy loading helpers is seamless
     */
    public function testLazyLoadingHelpers(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $Helper = new TestHelper($this->View);
        $this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $Helper->OtherHelper);
        $this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $Helper->Html);
    }

    /**
     * test that a helpers Helper is not 'attached' to the collection
     */
    public function testThatHelperHelpersAreNotAttached(): void
    {
        $events = $this->getMockBuilder('Cake\Event\EventManager')->getMock();
        $this->View->setEventManager($events);

        $events->expects($this->never())
            ->method('on');

        $Helper = new TestHelper($this->View);
        $Helper->OtherHelper;
    }

    /**
     * test that the lazy loader doesn't duplicate objects on each access.
     */
    public function testLazyLoadingUsesReferences(): void
    {
        $Helper = new TestHelper($this->View);
        $resultA = $Helper->Html;
        $resultB = $Helper->Html;

        $resultA->testprop = 1;
        $this->assertSame($resultA->testprop, $resultB->testprop);
    }

    /**
     * test getting view instance
     */
    public function testGetView(): void
    {
        $Helper = new TestHelper($this->View);
        $this->assertSame($this->View, $Helper->getView());
    }

    /**
     * Tests __debugInfo
     */
    public function testDebugInfo(): void
    {
        $Helper = new TestHelper($this->View);

        $expected = [
            'helpers' => [
                'Html',
                'TestPlugin.OtherHelper',
            ],
            'implementedEvents' => [
            ],
            '_config' => [
                'key1' => 'val1',
                'key2' => ['key2.1' => 'val2.1', 'key2.2' => 'val2.2'],
            ],
        ];
        $result = $Helper->__debugInfo();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test addClass() with 'class' => array
     */
    public function testAddClassArray(): void
    {
        $helper = new TestHelper($this->View);
        $input = ['class' => ['element1', 'element2']];
        $expected = ['class' => [
            'element1',
            'element2',
            'element3',
        ]];

        $this->assertEquals($expected, $helper->addClass($input, 'element3'));
    }

    /**
     * Test addClass() with 'class' => string
     */
    public function testAddClassString(): void
    {
        $helper = new TestHelper($this->View);

        $input = ['class' => 'element1 element2'];
        $expected = ['class' => 'element1 element2 element3'];

        $this->assertEquals($expected, $helper->addClass($input, 'element3'));
    }

    /**
     * Test addClass() with no class element
     */
    public function testAddClassEmpty(): void
    {
        $helper = new TestHelper($this->View);

        $input = [];
        $expected = ['class' => 'element3'];

        $this->assertEquals($expected, $helper->addClass($input, 'element3'));
    }
}
