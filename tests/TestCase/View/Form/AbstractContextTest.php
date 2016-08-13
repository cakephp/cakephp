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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Form;

use Cake\Network\Request;
use Cake\TestSuite\TestCase;

/**
 * Test case for AbstractContext
 */
class AbstractContextTest extends TestCase
{
    /**
     * @dataProvider requestTypeInitializationDataProvider
     */
    public function testRequestTypeInitialization($requestType, $create, $expectedRequestType)
    {
        $context = new CustomContext(new Request(), $requestType, $create);
        $this->assertEquals($expectedRequestType, $context->getRequestType());
    }

    public function requestTypeInitializationDataProvider()
    {
        return [
            [null, false, 'put'],
            [null, true, 'post'],
            ['file', false, 'put'],
            ['file', true, 'post'],
            ['post', false, 'post'],
            ['post', true, 'post'],
            ['get', false, 'get'],
            ['get', true, 'get']
        ];
    }

    /**
     * @dataProvider valDataProvider
     */
    public function testVal(array $data, array $query, $requestType, $expectedValue)
    {
        $request = new Request();
        $request->data = $data;
        $request->query = $query;
        $context = new CustomContext($request, $requestType, false);
        $this->assertEquals($expectedValue, $context->val('field'));
    }

    public function valDataProvider()
    {
        return [
            [[], [], null, ''],
            [[], [], 'post', ''],
            [[], [], 'get', ''],
            [[], ['field' => 'get'], null, ''],
            [[], ['field' => 'get'], 'post', ''],
            [[], ['field' => 'get'], 'get', 'get'],
            [['field' => 'post'], [], null, 'post'],
            [['field' => 'post'], [], 'post', 'post'],
            [['field' => 'post'], [], 'get', ''],
            [['field' => 'post'], ['field' => 'get'], null, 'post'],
            [['field' => 'post'], ['field' => 'get'], 'post', 'post'],
            [['field' => 'post'], ['field' => 'get'], 'get', 'get']
        ];
    }
}
