<?php
declare(strict_types=1);

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
namespace Cake\Test\TestCase\View\Helper;

use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\FlashHelper;
use Cake\View\View;

/**
 * FlashHelperTest class
 *
 * @property \Cake\View\Helper\FlashHelper $Flash
 */
class FlashHelperTest extends TestCase
{
    /**
     * @var \Cake\View\View
     */
    protected $View;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $session = new Session();
        $this->View = new View(new ServerRequest(['session' => $session]));
        $this->Flash = new FlashHelper($this->View);

        $session->write([
            'Flash' => [
                'flash' => [
                    [
                        'key' => 'flash',
                        'message' => 'This is a calling',
                        'element' => 'flash/default',
                        'params' => [],
                    ],
                ],
                'notification' => [
                    [
                        'key' => 'notification',
                        'message' => 'This is a test of the emergency broadcasting system',
                        'element' => 'flash_helper',
                        'params' => [
                            'title' => 'Notice!',
                            'name' => 'Alert!',
                        ],
                    ],
                ],
                'classy' => [
                    [
                        'key' => 'classy',
                        'message' => 'Recorded',
                        'element' => 'flash_classy',
                        'params' => [],
                    ],
                ],
                'stack' => [
                    [
                        'key' => 'flash',
                        'message' => 'This is a calling',
                        'element' => 'flash/default',
                        'params' => [],
                    ],
                    [
                        'key' => 'notification',
                        'message' => 'This is a test of the emergency broadcasting system',
                        'element' => 'flash_helper',
                        'params' => [
                            'title' => 'Notice!',
                            'name' => 'Alert!',
                        ],
                    ],
                    [
                        'key' => 'classy',
                        'message' => 'Recorded',
                        'element' => 'flash_classy',
                        'params' => [],
                    ],
                ],
            ],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->View, $this->Flash);
        $this->clearPlugins();
    }

    /**
     * testFlash method
     *
     * @return void
     */
    public function testFlash()
    {
        $result = $this->Flash->render();
        $expected = '<div class="message">This is a calling</div>';
        $this->assertStringContainsString($expected, $result);

        $expected = '<div id="classy-message">Recorded</div>';
        $result = $this->Flash->render('classy');
        $this->assertSame($expected, $result);

        $result = $this->Flash->render('notification');
        $expected = [
            'div' => ['id' => 'notificationLayout'],
            '<h1', 'Alert!', '/h1',
            '<h3', 'Notice!', '/h3',
            '<p', 'This is a test of the emergency broadcasting system', '/p',
            '/div',
        ];
        $this->assertHtml($expected, $result);
        $this->assertNull($this->Flash->render('nonexistent'));
    }

    /**
     * test setting the element from the attrs.
     *
     * @return void
     */
    public function testFlashElementInAttrs()
    {
        $result = $this->Flash->render('notification', [
            'element' => 'flash_helper',
            'params' => ['title' => 'Notice!', 'name' => 'Alert!'],
        ]);

        $expected = [
            'div' => ['id' => 'notificationLayout'],
            '<h1', 'Alert!', '/h1',
            '<h3', 'Notice!', '/h3',
            '<p', 'This is a test of the emergency broadcasting system', '/p',
            '/div',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test using elements in plugins.
     *
     * @return void
     */
    public function testFlashWithPluginElement()
    {
        $this->loadPlugins(['TestPlugin']);

        $result = $this->Flash->render('flash', ['element' => 'TestPlugin.flash/plugin_element']);
        $expected = 'this is the plugin element';
        $this->assertSame($expected, $result);
    }

    /**
     * test that when View theme is set, flash element from that theme (plugin) is used.
     *
     * @return void
     */
    public function testFlashWithTheme()
    {
        $this->loadPlugins(['TestTheme']);

        $this->View->setTheme('TestTheme');
        $result = $this->Flash->render('flash');
        $expected = 'flash element from TestTheme';
        $this->assertStringContainsString($expected, $result);
    }

    /**
     * Test that when rendering a stack, messages are displayed in their
     * respective element, in the order they were added in the stack
     *
     * @return void
     */
    public function testFlashWithStack()
    {
        $result = $this->Flash->render('stack');
        $expected = [
            ['div' => ['class' => 'message']], 'This is a calling', '/div',
            ['div' => ['id' => 'notificationLayout']],
            '<h1', 'Alert!', '/h1',
            '<h3', 'Notice!', '/h3',
            '<p', 'This is a test of the emergency broadcasting system', '/p',
            '/div',
            ['div' => ['id' => 'classy-message']], 'Recorded', '/div',
        ];
        $this->assertHtml($expected, $result);
        $this->assertNull($this->View->getRequest()->getSession()->read('Flash.stack'));
    }

    /**
     * test that when View prefix is set, flash element from that prefix
     * is used if available.
     *
     * @return void
     */
    public function testFlashWithPrefix()
    {
        $this->View->setRequest($this->View->getRequest()->withParam('prefix', 'Admin'));
        $result = $this->Flash->render('flash');
        $expected = 'flash element from Admin prefix folder';
        $this->assertStringContainsString($expected, $result);
    }
}
