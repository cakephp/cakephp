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
 * @since         3.4.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Network;

use Cake\Http\Response as HttpResponse;
use Cake\Http\ServerRequest as HttpRequest;
use Cake\Network\Request as NetworkRequest;
use Cake\Network\Response as NetworkResponse;
use Cake\TestSuite\TestCase;

/**
 * ensure that backwards compatibility was ensured for old Cake\Network\* classes
 */
class MoveToHttpTest extends TestCase
{
    /**
     * Tests the Cake\Http\Response loaded from Cake\Network\Response correctly
     *
     * @return void
     */
    public function testResponse()
    {
        $response = new NetworkResponse();
        $this->assertInstanceOf('Cake\Http\Response', $response);
        $this->assertInstanceOf('Cake\Network\Response', $response);

        $response = new HttpResponse();
        $this->assertInstanceOf('Cake\Http\Response', $response);
        $this->assertInstanceOf('Cake\Network\Response', $response);
    }
    /**
     * Tests the Cake\Http\ServerRequest loaded from Cake\Network\Request correctly
     *
     * @return void
     */

    public function testRequest()
    {
        $request = new NetworkRequest();
        $this->assertInstanceOf('Cake\Http\ServerRequest', $request);
        $this->assertInstanceOf('Cake\Network\Request', $request);

        $request = new HttpRequest();
        $this->assertInstanceOf('Cake\Http\ServerRequest', $request);
        $this->assertInstanceOf('Cake\Network\Request', $request);
    }
}
