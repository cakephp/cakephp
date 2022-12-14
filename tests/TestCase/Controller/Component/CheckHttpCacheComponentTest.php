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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\Component\CheckHttpCacheComponent;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * CheckHttpCacheComponentTest class
 */
class CheckHttpCacheComponentTest extends TestCase
{
    /**
     * @var \Cake\Controller\Component\CheckHTtpCacheComponent
     */
    protected $Component;

    /**
     * @var \Cake\Controller\Controller
     */
    protected $Controller;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
        $request = (new ServerRequest())
            ->withHeader('If-Modified-Since', '2012-01-01 00:00:00')
            ->withHeader('If-None-Match', '*');
        $this->Controller = new Controller($request);
        $this->Component = new CheckHttpCacheComponent($this->Controller->components());
    }

    public function testBeforeRenderSuccess()
    {
        $response = $this->Controller->getResponse()
            ->withEtag('something', true);
        $this->Controller->setResponse($response);

        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->Component->beforeRender($event);

        $this->assertTrue($event->isStopped());
        $response = $this->Controller->getResponse();
        $this->assertSame(304, $response->getStatusCode());
    }

    public function testBeforeRenderNoOp()
    {
        $event = new Event('Controller.beforeRender', $this->Controller);
        $this->Component->beforeRender($event);

        $this->assertFalse($event->isStopped());
        $response = $this->Controller->getResponse();
        $this->assertSame(200, $response->getStatusCode());
    }
}
