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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Response;

use Cake\Http\Response\RedirectResponse;
use Cake\Http\Uri;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * RedirectResponse test.
 */
class RedirectResponseTest extends TestCase
{
    public function testStringLocation(): void
    {
        $response = new RedirectResponse('/articles');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/articles', $response->getHeaderLine('Location'));
        $this->assertSame('', (string)$response->getBody());
    }

    public function testUriLocation(): void
    {
        $uri = new Uri('https://example.com/articles?page=2');
        $response = new RedirectResponse($uri);

        $this->assertSame('https://example.com/articles?page=2', $response->getHeaderLine('Location'));
    }

    public function testCustomStatus(): void
    {
        $response = new RedirectResponse('/articles', 301);

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('Moved Permanently', $response->getReasonPhrase());
    }

    public function testExtraHeaders(): void
    {
        $response = new RedirectResponse('/articles', 303, [
            'X-Reason' => 'moved',
            'X-Multi' => ['one', 'two'],
        ]);

        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('moved', $response->getHeaderLine('X-Reason'));
        $this->assertSame(['one', 'two'], $response->getHeader('x-multi'));
        $this->assertSame('/articles', $response->getHeaderLine('Location'));
    }

    public function testLocationOverridesHeaderOption(): void
    {
        $response = new RedirectResponse('/articles', 302, ['location' => '/other']);

        $this->assertSame(['/articles'], $response->getHeader('Location'));
        $this->assertArrayNotHasKey('location', $response->getHeaders());
        $this->assertSame(['/articles'], $response->getHeaders()['Location']);
    }

    public function testNoContentType(): void
    {
        $response = new RedirectResponse('/articles');

        $this->assertFalse($response->hasHeader('Content-Type'));
        $this->assertArrayNotHasKey('Content-Type', $response->getHeaders());
    }

    public function testSetCookieHeadersAddedToCookieCollection(): void
    {
        $response = new RedirectResponse('/articles', 302, [
            'Set-Cookie' => [
                'first=one; Path=/; HttpOnly',
                'second=two; Path=/admin',
            ],
            'set-cookie' => 'third=three',
        ]);

        $this->assertSame('one', $response->getCookie('first')['value']);
        $this->assertTrue($response->getCookie('first')['httponly']);
        $this->assertSame('two', $response->getCookie('second')['value']);
        $this->assertSame('/admin', $response->getCookie('second')['path']);
        $this->assertSame('three', $response->getCookie('third')['value']);
        $this->assertFalse($response->hasHeader('Set-Cookie'));
    }

    public static function invalidHeaderNames(): array
    {
        return [
            'space' => ['X Foo'],
            'colon' => ['X-Foo:'],
            'crlf' => ["X-Foo\r\nInjected"],
            'empty' => [''],
        ];
    }

    #[DataProvider('invalidHeaderNames')]
    public function testInvalidHeaderNameThrows(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid header name');

        new RedirectResponse('/articles', 302, [$name => 'value']);
    }

    public function testInvalidHeaderValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RedirectResponse('/articles', 302, ['X-Foo' => "value\r\nInjected: header"]);
    }

    public function testInvalidLocationThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RedirectResponse("/articles\r\nInjected: header");
    }
}
