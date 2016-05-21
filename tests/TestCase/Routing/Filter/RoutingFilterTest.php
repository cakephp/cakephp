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
namespace Cake\Test\TestCase\Routing\Filter;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Filter\RoutingFilter;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * Routing filter test.
 */
class RoutingFilterTest extends TestCase
{

    /**
     * test setting parameters in beforeDispatch method
     *
     * @return void
     * @triggers __CLASS__ $this, compact(request)
     */
    public function testBeforeDispatchSkipWhenControllerSet()
    {
        $filter = new RoutingFilter();

        $request = new Request("/testcontroller/testaction/params1/params2/params3");
        $request->addParams(['controller' => 'articles']);
        $event = new Event(__CLASS__, $this, compact('request'));
        $filter->beforeDispatch($event);

        $this->assertSame($request->params['controller'], 'articles');
        $this->assertEmpty($request->params['action']);
    }

    /**
     * test setting parameters in beforeDispatch method
     *
     * @return void
     * @triggers __CLASS__ $this, compact(request)
     */
    public function testBeforeDispatchSetsParameters()
    {
        Router::connect('/:controller/:action/*');
        $filter = new RoutingFilter();

        $request = new Request("/testcontroller/testaction/params1/params2/params3");
        $event = new Event(__CLASS__, $this, compact('request'));
        $filter->beforeDispatch($event);

        $this->assertSame($request->params['controller'], 'Testcontroller');
        $this->assertSame($request->params['action'], 'testaction');
        $this->assertSame($request->params['pass'][0], 'params1');
        $this->assertSame($request->params['pass'][1], 'params2');
        $this->assertSame($request->params['pass'][2], 'params3');
        $this->assertFalse(!empty($request['form']));
    }

    /**
     * test setting parameters in beforeDispatch method
     *
     * @return void
     * @triggers __CLASS__ $this, compact(request)
     */
    public function testBeforeDispatchRedirectRoute()
    {
        Router::redirect('/home', ['controller' => 'articles']);
        Router::connect('/:controller/:action/*');
        $filter = new RoutingFilter();

        $request = new Request("/home");
        $response = new Response();
        $event = new Event(__CLASS__, $this, compact('request', 'response'));
        $response = $filter->beforeDispatch($event);
        $this->assertInstanceOf('Cake\Network\Response', $response);
        $this->assertSame('http://localhost/articles/index', $response->header()['Location']);
        $this->assertSame(301, $response->statusCode());
    }

    /**
     * test setting parameters in beforeDispatch method
     *
     * @return void
     * @triggers __CLASS__ $this, compact(request)
     * @triggers __CLASS__ $this, compact(request)
     */
    public function testQueryStringOnRoot()
    {
        Router::reload();
        Router::connect('/', ['controller' => 'pages', 'action' => 'display', 'home']);
        Router::connect('/pages/*', ['controller' => 'pages', 'action' => 'display']);
        Router::connect('/:controller/:action/*');

        $_GET = ['coffee' => 'life', 'sleep' => 'sissies'];
        $filter = new RoutingFilter();
        $request = new Request('posts/home/?coffee=life&sleep=sissies');

        $event = new Event(__CLASS__, $this, compact('request'));
        $filter->beforeDispatch($event);

        $this->assertRegExp('/Posts/', $request['controller']);
        $this->assertRegExp('/home/', $request['action']);
        $this->assertTrue(isset($request['url']['sleep']));
        $this->assertTrue(isset($request['url']['coffee']));

        $request = new Request('/?coffee=life&sleep=sissy');

        $event = new Event(__CLASS__, $this, compact('request'));
        $filter->beforeDispatch($event);

        $this->assertRegExp('/Pages/', $request['controller']);
        $this->assertRegExp('/display/', $request['action']);
        $this->assertTrue(isset($request['url']['sleep']));
        $this->assertTrue(isset($request['url']['coffee']));
        $this->assertEquals('life', $request['url']['coffee']);
    }
}
