<?php
declare(strict_types=1);

/**
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         4.0.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestSession;

class TestSessionTest extends TestCase
{
    /**
     * @var array
     */
    protected $sessionData;

    /**
     * @var \Cake\TestSuite\TestSession
     */
    protected $session;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->sessionData = [
            'root' => [
                'sub' => [
                    'subsub' => 'foo',
                ],
            ],
        ];
        $this->session = new TestSession($this->sessionData);
    }

    /**
     * Tests read()
     *
     * @return void
     */
    public function testRead(): void
    {
        $result = $this->session->read();
        $this->assertSame($this->sessionData, $result);

        $result = $this->session->read('root.sub');
        $this->assertSame(['subsub' => 'foo'], $result);
    }

    /**
     * Tests check()
     *
     * @return void
     */
    public function testCheck(): void
    {
        $result = $this->session->check();
        $this->assertTrue($result);

        $result = $this->session->check('root.sub');
        $this->assertTrue($result);

        $result = $this->session->check('root.nonexistent');
        $this->assertFalse($result);
    }
}
