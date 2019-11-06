<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\MiddlewareApplication;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * MiddlewareApplication test.
 */
class MiddlewareApplicationTest extends TestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();
    }

    /**
     * Integration test for a simple controller.
     *
     * @return void
     */
    public function testHandle()
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/cakes']);
        $request = $request->withAttribute('params', [
            'controller' => 'Cakes',
            'action' => 'index',
            'plugin' => null,
            'pass' => [],
        ]);

        $app = $this->getMockForAbstractClass(MiddlewareApplication::class);
        $result = $app->handle($request);
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame('Not found', '' . $result->getBody());
        $this->assertSame(404, $result->getStatusCode());
    }
}
