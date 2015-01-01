<?php
/**
 * SessionHelperTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\SessionHelper;
use Cake\View\View;

/**
 * SessionHelperTest class
 *
 */
class SessionHelperTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->View = new View();
        $session = new Session();
        $this->View->request = new Request(['session' => $session]);
        $this->Session = new SessionHelper($this->View);

        $session->write([
            'test' => 'info',
            'Flash' => [
                'flash' => [
                    'type' => 'info',
                    'params' => [],
                    'message' => 'This is a calling'
                ],
                'notification' => [
                    'type' => 'info',
                    'params' => [
                        'title' => 'Notice!',
                        'name' => 'Alert!',
                        'element' => 'session_helper'
                    ],
                    'message' => 'This is a test of the emergency broadcasting system',
                ],
                'classy' => [
                    'type' => 'success',
                    'params' => ['class' => 'positive'],
                    'message' => 'Recorded'
                ],
                'incomplete' => [
                    'message' => 'A thing happened',
                ]
            ],
            'Deeply' => ['nested' => ['key' => 'value']],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        $_SESSION = [];
        unset($this->View, $this->Session);
        Plugin::unload();
        parent::tearDown();
    }

    /**
     * testRead method
     *
     * @return void
     */
    public function testRead()
    {
        $result = $this->Session->read('Deeply.nested.key');
        $this->assertEquals('value', $result);

        $result = $this->Session->read('test');
        $this->assertEquals('info', $result);
    }

    /**
     * testCheck method
     *
     * @return void
     */
    public function testCheck()
    {
        $this->assertTrue($this->Session->check('test'));
        $this->assertTrue($this->Session->check('Flash.flash'));
        $this->assertFalse($this->Session->check('Does.not.exist'));
        $this->assertFalse($this->Session->check('Nope'));
    }
}
