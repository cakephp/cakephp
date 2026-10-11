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

use Cake\Core\Configure;
use Cake\Http\UriFactory;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test case for the uri factory.
 */
class UriFactoryTest extends TestCase
{
    public function testCreateUri(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('https://cakephp.org');

        $this->assertInstanceOf(Uri::class, $uri);
    }

    public function testMarshalUriAndBaseFromSapi(): void
    {
        Configure::write('App.baseUrl', '/index.php');
        $result = UriFactory::marshalUriAndBaseFromSapi([]);

        $this->assertInstanceOf(Uri::class, $result['uri']);
        $this->assertSame('/index.php', $result['base']);
        $this->assertSame('/webroot/', $result['webroot']);

        Configure::delete('App.baseUrl');
    }

    public function testMarshalUsesIISUnencodedUrlValueIfPresentAndUrlWasRewritten(): void
    {
        $server = [
            'IIS_WasUrlRewritten' => '1',
            'UNENCODED_URL' => '/foo/bar',
            'REQUEST_URI' => '/other/path',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('/foo/bar', $uri->getPath());
    }

    public function testMarshalIgnoresIISUnencodedUrlIfUrlWasNotRewritten(): void
    {
        $server = [
            'UNENCODED_URL' => '/foo/bar',
            'REQUEST_URI' => '/other/path',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('/other/path', $uri->getPath());
    }

    public function testMarshalStripsSchemeHostAndPortInformationFromRequestUri(): void
    {
        $server = [
            'REQUEST_URI' => 'http://example.com:8000/foo/bar',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('/foo/bar', $uri->getPath());
    }

    public function testMarshalUsesOrigPathInfoIfPresent(): void
    {
        $server = [
            'ORIG_PATH_INFO' => '/foo/bar',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('/foo/bar', $uri->getPath());
    }

    public function testMarshalFallsBackToRootPath(): void
    {
        $result = UriFactory::marshalUriAndBaseFromSapi([]);
        $uri = $result['uri'];

        $this->assertSame('/', $uri->getPath());
        $this->assertSame('', $result['base']);
        $this->assertSame('/', $result['webroot']);
    }

    public function testMarshalDefaultsToLocalhostWithoutHostInformation(): void
    {
        $uri = UriFactory::marshalUriAndBaseFromSapi([])['uri'];

        $this->assertSame('localhost', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('http://localhost/', (string)$uri);
    }

    public function testMarshalHostAndPortUsesHostHeaderWhenPresent(): void
    {
        $uri = UriFactory::marshalUriAndBaseFromSapi(['HTTP_HOST' => 'example.com'])['uri'];

        $this->assertSame('example.com', $uri->getHost());
        $this->assertNull($uri->getPort());
    }

    public function testMarshalHostAndPortWillDetectPortInHostHeaderWhenPresent(): void
    {
        $uri = UriFactory::marshalUriAndBaseFromSapi(['HTTP_HOST' => 'example.com:8000'])['uri'];

        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8000, $uri->getPort());
    }

    public function testMarshalHostAndPortDetectsPortInIpv6HostHeader(): void
    {
        $uri = UriFactory::marshalUriAndBaseFromSapi(['HTTP_HOST' => '[::1]:8080'])['uri'];

        $this->assertSame('[::1]', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
    }

    public function testMarshalHostAndPortPrefersHostHeaderOverServerName(): void
    {
        $server = [
            'HTTP_HOST' => 'example.com',
            'SERVER_NAME' => 'example.org',
            'SERVER_PORT' => 8000,
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('example.com', $uri->getHost());
        $this->assertNull($uri->getPort());
    }

    public function testMarshalHostAndPortFallsBackToRedirectHostHeader(): void
    {
        $server = [
            'REDIRECT_HTTP_HOST' => 'example.com:8000',
            'SERVER_NAME' => 'example.org',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8000, $uri->getPort());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedHostHeaderProvider(): array
    {
        return [
            'empty' => [''],
            'space' => ['example.com evil.com'],
            'tab' => ["example.com\tevil.com"],
            'multiple' => ['example.com,evil.com'],
        ];
    }

    #[DataProvider('malformedHostHeaderProvider')]
    public function testMarshalHostAndPortIgnoresMalformedHostHeader(string $host): void
    {
        $server = [
            'HTTP_HOST' => $host,
            'SERVER_NAME' => 'example.org',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('example.org', $uri->getHost());
    }

    public function testMarshalHostAndPortReturnsServerNameForHostWhenPresent(): void
    {
        $uri = UriFactory::marshalUriAndBaseFromSapi(['SERVER_NAME' => 'example.com'])['uri'];

        $this->assertSame('example.com', $uri->getHost());
        $this->assertNull($uri->getPort());
    }

    public function testMarshalHostAndPortReturnsServerPortForPortWhenPresentWithServerName(): void
    {
        $server = [
            'SERVER_NAME' => 'example.com',
            'SERVER_PORT' => 8000,
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8000, $uri->getPort());
    }

    public function testMarshalHostAndPortOmitsDefaultServerPort(): void
    {
        $server = [
            'HTTPS' => 'on',
            'SERVER_NAME' => 'example.com',
            'SERVER_PORT' => '443',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('https://example.com/', (string)$uri);
        $this->assertNull($uri->getPort());
    }

    public function testMarshalHostAndPortReturnsServerNameForHostIfServerAddrPresentButHostIsNotIpv6Address(): void
    {
        $server = [
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'example.com',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('example.com', $uri->getHost());
    }

    public function testMarshalHostAndPortReturnsServerAddrForHostIfPresentAndHostIsIpv6Address(): void
    {
        $server = [
            'SERVER_ADDR' => 'FE80::0202:B3FF:FE1E:8329',
            'SERVER_NAME' => '[FE80::0202:B3FF:FE1E:8329]',
            'SERVER_PORT' => 8000,
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('[fe80::0202:b3ff:fe1e:8329]', $uri->getHost());
        $this->assertSame(8000, $uri->getPort());
    }

    public function testMarshalHostAndPortWillDetectPortInIpv6StyleHost(): void
    {
        $server = [
            'SERVER_ADDR' => 'FE80::0202:B3FF:FE1E:8329',
            'SERVER_NAME' => '[FE80::0202:B3FF:FE1E:8329:80]',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('[fe80::0202:b3ff:fe1e:8329]', $uri->getHost());
        $this->assertNull($uri->getPort());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function httpsParamProvider(): array
    {
        return [
            'lowercase' => ['https'],
            'uppercase' => ['HTTPS'],
        ];
    }

    #[DataProvider('httpsParamProvider')]
    public function testMarshalUriDetectsHttpsSchemeFromServerValue(string $param): void
    {
        $server = [
            $param => 'on',
            'HTTP_HOST' => 'example.com',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertSame('https', $uri->getScheme());
    }

    public function testMarshalUriDetectsHttpsSchemeFromUppercaseOnValue(): void
    {
        $server = [
            'HTTPS' => 'ON',
            'HTTP_HOST' => 'example.com',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('https', $uri->getScheme());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function httpsDisableParamProvider(): iterable
    {
        foreach (array_keys(static::httpsParamProvider()) as $key) {
            $param = static::httpsParamProvider()[$key][0];
            yield $key . '-lowercase-off' => [$param, 'off'];
            yield $key . '-uppercase-off' => [$param, 'OFF'];
            yield $key . '-empty' => [$param, ''];
        }
    }

    #[DataProvider('httpsDisableParamProvider')]
    public function testMarshalUriUsesHttpSchemeIfHttpsServerValueEqualsOff(string $param, string $value): void
    {
        $server = [
            $param => $value,
            'HTTP_HOST' => 'example.com',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertSame('http', $uri->getScheme());
    }

    public function testMarshalUriAcceptsBooleanHttpsValue(): void
    {
        $uri = UriFactory::marshalUriAndBaseFromSapi(['HTTPS' => true, 'HTTP_HOST' => 'example.com'])['uri'];
        $this->assertSame('https', $uri->getScheme());

        $uri = UriFactory::marshalUriAndBaseFromSapi(['HTTPS' => false, 'HTTP_HOST' => 'example.com'])['uri'];
        $this->assertSame('http', $uri->getScheme());
    }

    public function testMarshalUriRaisesExceptionForInvalidHttpsValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SAPI HTTPS value must be a string or boolean');

        UriFactory::marshalUriAndBaseFromSapi(['HTTPS' => 1]);
    }

    public function testMarshalUriStripsQueryStringFromRequestUri(): void
    {
        $server = [
            'REQUEST_URI' => '/foo/bar?foo=bar',
            'HTTP_HOST' => 'example.com',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertSame('/foo/bar', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
    }

    public function testMarshalUriInjectsQueryStringFromServer(): void
    {
        $server = [
            'REQUEST_URI' => '/foo/bar?foo=bar',
            'QUERY_STRING' => 'bar=baz',
            'HTTP_HOST' => 'example.com',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertSame('bar=baz', $uri->getQuery());
        $this->assertSame('http://example.com/foo/bar?bar=baz', (string)$uri);
    }

    public function testMarshalUriStripsLeadingQuestionMarkFromQueryString(): void
    {
        $server = [
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => '?bar=baz',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('bar=baz', $uri->getQuery());
    }

    public function testMarshalUriInjectsFragmentFromServer(): void
    {
        $server = [
            'REQUEST_URI' => '/foo/bar#foo',
            'HTTP_HOST' => 'example.com',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertSame('/foo/bar', $uri->getPath());
        $this->assertSame('foo', $uri->getFragment());
    }

    public function testMarshalRequestUriIgnoresXOriginalUrl(): void
    {
        $server = [
            'HTTP_X_ORIGINAL_URL' => '/hijack-attempt',
            'HTTP_X_REWRITE_URL' => '/hijack-attempt',
            'REQUEST_URI' => 'https://example.com/requested/path',
        ];

        $uri = UriFactory::marshalUriAndBaseFromSapi($server)['uri'];

        $this->assertSame('/requested/path', $uri->getPath());
    }

    public function testMarshalStripsBasePathFromRequestUri(): void
    {
        $server = [
            'PHP_SELF' => '/subdir/webroot/index.php',
            'REQUEST_URI' => '/subdir/articles/view/1',
            'HTTP_HOST' => 'example.com',
        ];

        $result = UriFactory::marshalUriAndBaseFromSapi($server);

        $this->assertSame('/subdir', $result['base']);
        $this->assertSame('/subdir/', $result['webroot']);
        $this->assertSame('/articles/view/1', $result['uri']->getPath());
        $this->assertSame('http://example.com/articles/view/1', (string)$result['uri']);
    }

    public function testMarshalStripsConfiguredBasePath(): void
    {
        Configure::write('App.base', '/app');
        $server = [
            'REQUEST_URI' => '/app/articles?page=2',
            'QUERY_STRING' => 'page=2',
            'HTTP_HOST' => 'example.com',
        ];

        $result = UriFactory::marshalUriAndBaseFromSapi($server);

        $this->assertSame('/app', $result['base']);
        $this->assertSame('/app/', $result['webroot']);
        $this->assertSame('/articles', $result['uri']->getPath());
        $this->assertSame('page=2', $result['uri']->getQuery());
    }
}
