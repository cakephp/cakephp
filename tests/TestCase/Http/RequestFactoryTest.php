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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\RequestFactory;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Uri;

/**
 * Test case for the request factory.
 */
class RequestFactoryTest extends TestCase
{
    public function testCreateRequest(): void
    {
        $factory = new RequestFactory();

        $request = $factory->createRequest('POST', 'http://example.com');

        $this->assertSame('http://example.com', (string)$request->getUri());
        $this->assertStringContainsString($request->getMethod(), 'POST');

        $uri = new Uri('http://example.com');
        $request = $factory->createRequest('GET', $uri);

        $this->assertSame($uri, $request->getUri());
        $this->assertStringContainsString($request->getMethod(), 'GET');
    }
}
