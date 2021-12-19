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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\Uri;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Uri as LaminasUri;
use UnexpectedValueException;

/**
 * Test case for the Uri Shim
 */
class UriTest extends TestCase
{
    public function testExtraMethods()
    {
        $inner = new LaminasUri('/articles/view/1');
        $uri = new Uri($inner, '/base', '/base/');

        $this->assertSame('/base', $uri->getBase());
        $this->assertSame('/base/', $uri->getWebroot());
    }

    public function testMagicAttributes()
    {
        $inner = new LaminasUri('/articles/view/1');
        $uri = new Uri($inner, '/base', '/base/');

        $this->assertSame('/base', $uri->base);
        $this->assertSame('/base/', $uri->webroot);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Undefined property via __get');
        $uri->uri;
    }

    public function testWrappedGetMethods()
    {
        $inner = (new LaminasUri('/articles/view/1'))
            ->withPort(80)
            ->withUserInfo('user', 'pass')
            ->withQuery('a=b&c=d')
            ->withFragment('frag');

        $uri = new Uri($inner, '/base', '/base/');
        $methods = [
            'getScheme', 'getAuthority', 'getUserInfo',
            'getHost', 'getPort', 'getPath', 'getQuery', 'getFragment',
        ];
        foreach ($methods as $method) {
            $this->assertSame($inner->{$method}(), $uri->{$method}());
        }
    }

    public function testWrappedWithMethods()
    {
        $inner = new LaminasUri('/articles/view/1');
        $uri = new Uri($inner, '/base', '/base/');

        $new = $uri->withPort(80);
        $this->assertNotSame($new, $uri);
        $this->assertNotSame($new, $inner);
        $this->assertSame(80, $new->getPort());

        $new = $uri->withScheme('http');
        $this->assertNotSame($new, $uri);
        $this->assertNotSame($new, $inner);
        $this->assertSame('http', $new->getScheme());

        $new = $uri->withUserInfo('user', 'pass');
        $this->assertNotSame($new, $uri);
        $this->assertNotSame($new, $inner);
        $this->assertSame('user:pass', $new->getUserInfo());

        $methods = [
            'withHost', 'withPath', 'withQuery', 'withFragment',
        ];
        foreach ($methods as $method) {
            $new = $uri->{$method}('value');
            $this->assertNotSame($new, $uri);
            $this->assertNotSame($new, $inner);
            $this->assertInstanceOf(Uri::class, $new);
        }
    }
}
