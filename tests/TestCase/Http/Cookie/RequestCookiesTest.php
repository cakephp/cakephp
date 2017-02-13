<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Cookie;

use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\RequestCookies;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * HTTP cookies test.
 */
class RequestCookiesTest extends TestCase {

    /**
     * Server Request
     *
     * @var \Cake\Http\ServerRequest
     */
    public $request;

    /**
     * setup
     *
     * @return null
     */
    public function setUp()
    {
        $this->request = new ServerRequest([
            'cookies' => [
                'remember_me' => 'test',
                'something' => 'test2'
            ]
        ]);
    }

    /**
     * Test testCreateFromRequest
     *
     * @return null
     */
    public function testCreateFromRequest()
    {
        $result = RequestCookies::createFromRequest($this->request);
        $this->assertInstanceOf(RequestCookies::class, $result);
        $this->assertInstanceOf(Cookie::class, $result->get('remember_me'));
        $this->assertInstanceOf(Cookie::class, $result->get('something'));

        $this->assertTrue($result->has('remember_me'));
        $this->assertTrue($result->has('something'));
        $this->assertFalse($result->has('does-not-exist'));
    }
}
