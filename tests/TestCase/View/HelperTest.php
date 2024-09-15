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
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;
use Exception;
use TestApp\View\Helper\TestHelper;
use TestPlugin\View\Helper\OtherHelperHelper;

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
        $this->assertInstanceOf(OtherHelperHelper::class, $Helper->OtherHelper);
        $this->assertInstanceOf(HtmlHelper::class, $Helper->Html);
    }

    /**
     * test that a helpers Helper is not 'attached' to the collection
     */
    public function testThatHelperHelpersAreNotAttached(): void
    {
        $eventsManager = new class extends EventManager
        {
            public function on(string|EventListenerInterface $eventKey, callable|array $options = [], ?callable $callable = null)
            {
                throw new Exception('Should not be called');
            }
        };
        $this->View->setEventManager($eventsManager);

        $helper = new TestHelper($this->View);
        $this->assertInstanceOf(Helper::class, $helper->OtherHelper);
    }

    /**
     * test that the lazy loader doesn't duplicate objects on each access.
     */
    public function testLazyLoadingUsesReferences(): void
    {
        $Helper = new TestHelper($this->View);
        $resultA = $Helper->Html;
        $resultB = $Helper->Html;

        $this->assertSame($resultA, $resultB);
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
                'Html' => [],
                'OtherHelper' => ['className' => 'TestPlugin.OtherHelper'],
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
