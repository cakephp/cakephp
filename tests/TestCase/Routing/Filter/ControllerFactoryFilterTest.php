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
 * @since         3.2.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Filter;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Filter\ControllerFactoryFilter;
use Cake\TestSuite\TestCase;

/**
 * Controller factory filter test.
 */
class ControllerFactoryFilterTest extends TestCase
{

    /**
     * testBeforeDispatch
     *
     * @return void
     */
    public function testBeforeDispatch()
    {
        Configure::write('App.namespace', 'TestApp');

        $filter = new ControllerFactoryFilter();

        $request = new Request();
        $response = new Response();
        $request->addParams(['prefix' => 'admin', 'controller' => 'Posts', 'action' => 'index']);
        $event = new Event(__CLASS__, $this, compact('request', 'response'));
        $filter->beforeDispatch($event);

        $this->assertEquals(
            'TestApp\Controller\Admin\PostsController',
            get_class($event->data('controller'))
        );

        $request->addParams(['prefix' => 'admin/sub', 'controller' => 'Posts', 'action' => 'index']);
        $event = new Event(__CLASS__, $this, compact('request', 'response'));
        $filter->beforeDispatch($event);

        $this->assertEquals(
            'TestApp\Controller\Admin\Sub\PostsController',
            get_class($event->data('controller'))
        );
    }
}
