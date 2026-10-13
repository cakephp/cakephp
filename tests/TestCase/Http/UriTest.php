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
 * Parts of this file are derived from laminas-diactoros
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @copyright     Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC. (https://getlaminas.org/)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @license       https://github.com/laminas/laminas-diactoros/blob/3.9.x/LICENSE.md New BSD License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\Uri;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test case for the Uri class.
 */
class UriTest extends TestCase
{
    protected const string URI = 'https://user:pass@local.example.com:3001/foo?bar=baz#quz';

    public function testConstructorSetsAllProperties(): void
    {
        $uri = new Uri(static::URI);
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('local.example.com', $uri->getHost());
        $this->assertSame(3001, $uri->getPort());
        $this->assertSame('user:pass@local.example.com:3001', $uri->getAuthority());
        $this->assertSame('/foo', $uri->getPath());
        $this->assertSame('bar=baz', $uri->getQuery());
        $this->assertSame('quz', $uri->getFragment());
    }

    public function testEmptyConstructor(): void
    {
        $uri = new Uri();
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertSame('', $uri->getAuthority());
        $this->assertSame('', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
        $this->assertSame('', (string)$uri);
    }

    public function testCanSerializeToString(): void
    {
        $uri = new Uri(static::URI);
        $this->assertSame(static::URI, (string)$uri);
    }

    public function testWithSchemeReturnsNewInstanceWithNewScheme(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withScheme('http');
        $this->assertNotSame($uri, $new);
        $this->assertSame('http', $new->getScheme());
        $this->assertSame('http://user:pass@local.example.com:3001/foo?bar=baz#quz', (string)$new);
    }

    public function testWithSchemeReturnsSameInstanceWithSameScheme(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withScheme('https');
        $this->assertSame($uri, $new);
        $this->assertSame('https', $new->getScheme());
        $this->assertSame(static::URI, (string)$new);
    }

    public function testWithSchemeIsLowercased(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withScheme('HTTPS');
        $this->assertSame('https', $new->getScheme());
    }

    public function testWithUserInfoReturnsNewInstanceWithProvidedUser(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withUserInfo('matthew');
        $this->assertNotSame($uri, $new);
        $this->assertSame('matthew', $new->getUserInfo());
        $this->assertSame('https://matthew@local.example.com:3001/foo?bar=baz#quz', (string)$new);
    }

    public function testWithUserInfoReturnsNewInstanceWithProvidedUserAndPassword(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withUserInfo('matthew', 'laminas');
        $this->assertNotSame($uri, $new);
        $this->assertSame('matthew:laminas', $new->getUserInfo());
        $this->assertSame('https://matthew:laminas@local.example.com:3001/foo?bar=baz#quz', (string)$new);
    }

    public function testWithUserInfoReturnsSameInstanceIfUserAndPasswordAreSameAsBefore(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withUserInfo('user', 'pass');
        $this->assertSame($uri, $new);
        $this->assertSame('user:pass', $new->getUserInfo());
        $this->assertSame(static::URI, (string)$new);
    }

    public function testWithUserInfoDropsEmptyPassword(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withUserInfo('user', '');
        $this->assertSame('user', $new->getUserInfo());
        $this->assertSame('https://user@local.example.com:3001/foo?bar=baz#quz', (string)$new);
    }

    public function testWithUserInfoEmptyUserRemovesUserInfo(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withUserInfo('', 'pass');
        $this->assertSame('', $new->getUserInfo());
        $this->assertSame('https://local.example.com:3001/foo?bar=baz#quz', (string)$new);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function userInfoProvider(): array
    {
        return [
            'valid-chars' => ['foo', 'bar', 'foo:bar'],
            'colon' => ['foo:bar', 'baz:bat', 'foo%3Abar:baz%3Abat'],
            'at' => ['user@example.com', 'cred@foo', 'user%40example.com:cred%40foo'],
            'percent' => ['%25', '%25', '%25:%25'],
            'invalid-enc' => ['%ZZ', '%GG', '%25ZZ:%25GG'],
            'invalid-utf' => ["\x21\x92", '!?', '!%92:!%3F'],
        ];
    }

    #[DataProvider('userInfoProvider')]
    public function testWithUserInfoEncodesUsernameAndPassword(string $user, string $credential, string $expected): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withUserInfo($user, $credential);

        $this->assertSame($expected, $new->getUserInfo());
    }

    public function testWithHostReturnsNewInstanceWithProvidedHost(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withHost('getlaminas.org');
        $this->assertNotSame($uri, $new);
        $this->assertSame('getlaminas.org', $new->getHost());
        $this->assertSame('https://user:pass@getlaminas.org:3001/foo?bar=baz#quz', (string)$new);
    }

    public function testWithHostReturnsSameInstanceWithProvidedHostIsSameAsBefore(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withHost('local.example.com');
        $this->assertSame($uri, $new);
        $this->assertSame('local.example.com', $new->getHost());
        $this->assertSame(static::URI, (string)$new);
    }

    /**
     * @return array<string, array{int|null}>
     */
    public static function validPorts(): array
    {
        return [
            'null' => [null],
            'int' => [3000],
            'min' => [1],
            'max' => [65535],
        ];
    }

    #[DataProvider('validPorts')]
    public function testWithPortReturnsNewInstanceWithProvidedPort(?int $port): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withPort($port);
        $this->assertNotSame($uri, $new);
        $this->assertSame($port, $new->getPort());
        $this->assertSame(
            sprintf('https://user:pass@local.example.com%s/foo?bar=baz#quz', $port === null ? '' : ':' . $port),
            (string)$new,
        );
    }

    public function testWithPortReturnsSameInstanceWithProvidedPortIsSameAsBefore(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withPort(3001);
        $this->assertSame($uri, $new);
        $this->assertSame(3001, $new->getPort());
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidPorts(): array
    {
        return [
            'zero' => [0],
            'too-small' => [-1],
            'too-big' => [65536],
        ];
    }

    #[DataProvider('invalidPorts')]
    public function testWithPortRaisesExceptionForInvalidPorts(int $port): void
    {
        $uri = new Uri(static::URI);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid port');

        $uri->withPort($port);
    }

    public function testWithPathReturnsNewInstanceWithProvidedPath(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withPath('/bar/baz');
        $this->assertNotSame($uri, $new);
        $this->assertSame('/bar/baz', $new->getPath());
        $this->assertSame('https://user:pass@local.example.com:3001/bar/baz?bar=baz#quz', (string)$new);
    }

    public function testWithPathReturnsSameInstanceWithProvidedPathSameAsBefore(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withPath('/foo');
        $this->assertSame($uri, $new);
        $this->assertSame('/foo', $new->getPath());
        $this->assertSame(static::URI, (string)$new);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPaths(): array
    {
        return [
            'query' => ['/bar/baz?bat=quz'],
            'fragment' => ['/bar/baz#bat'],
        ];
    }

    #[DataProvider('invalidPaths')]
    public function testWithPathRaisesExceptionForInvalidPaths(string $path): void
    {
        $uri = new Uri(static::URI);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid path');

        $uri->withPath($path);
    }

    public function testWithQueryReturnsNewInstanceWithProvidedQuery(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withQuery('baz=bat');
        $this->assertNotSame($uri, $new);
        $this->assertSame('baz=bat', $new->getQuery());
        $this->assertSame('https://user:pass@local.example.com:3001/foo?baz=bat#quz', (string)$new);
    }

    public function testWithQueryReturnsSameInstanceWithProvidedQuerySameAsBefore(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withQuery('bar=baz');
        $this->assertSame($uri, $new);
    }

    public function testWithQueryRaisesExceptionForQueryStringWithFragment(): void
    {
        $uri = new Uri(static::URI);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Query string');

        $uri->withQuery('baz=bat#quz');
    }

    public function testWithFragmentReturnsNewInstanceWithProvidedFragment(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withFragment('qat');
        $this->assertNotSame($uri, $new);
        $this->assertSame('qat', $new->getFragment());
        $this->assertSame('https://user:pass@local.example.com:3001/foo?bar=baz#qat', (string)$new);
    }

    public function testWithFragmentReturnsSameInstanceWithProvidedFragmentSameAsBefore(): void
    {
        $uri = new Uri(static::URI);
        $new = $uri->withFragment('quz');
        $this->assertSame($uri, $new);
        $this->assertSame('quz', $new->getFragment());
        $this->assertSame(static::URI, (string)$new);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function authorityInfo(): array
    {
        return [
            'host-only' => ['http://foo.com/bar', 'foo.com'],
            'host-port' => ['http://foo.com:3000/bar', 'foo.com:3000'],
            'user-host' => ['http://me@foo.com/bar', 'me@foo.com'],
            'user-host-port' => ['http://me@foo.com:3000/bar', 'me@foo.com:3000'],
        ];
    }

    #[DataProvider('authorityInfo')]
    public function testRetrievingAuthorityReturnsExpectedValues(string $url, string $expected): void
    {
        $uri = new Uri($url);
        $this->assertSame($expected, $uri->getAuthority());
    }

    public function testCanEmitOriginFormUrl(): void
    {
        $url = '/foo/bar?baz=bat';
        $uri = new Uri($url);
        $this->assertSame($url, (string)$uri);
    }

    public function testSettingEmptyPathOnAbsoluteUriReturnsAnEmptyPath(): void
    {
        $uri = new Uri('http://example.com/foo');
        $new = $uri->withPath('');
        $this->assertSame('', $new->getPath());
    }

    public function testStringRepresentationOfAbsoluteUriWithNoPathSetsAnEmptyPath(): void
    {
        $uri = new Uri('http://example.com');
        $this->assertSame('http://example.com', (string)$uri);
    }

    public function testEmptyPathOnOriginFormRemainsAnEmptyPath(): void
    {
        $uri = new Uri('?foo=bar');
        $this->assertSame('', $uri->getPath());
    }

    public function testStringRepresentationOfOriginFormWithNoPathRetainsEmptyPath(): void
    {
        $uri = new Uri('?foo=bar');
        $this->assertSame('?foo=bar', (string)$uri);
    }

    public function testConstructorRaisesExceptionForSeriouslyMalformedURI(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Uri('http:///www.php-fig.org/');
    }

    public function testMutatingSchemeStripsOffDelimiter(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withScheme('https://');
        $this->assertSame('https', $new->getScheme());
    }

    public function testEmptySchemeStripsOffDelimiter(): void
    {
        $uri = new Uri('https://example.com');
        $new = $uri->withScheme('://');
        $this->assertSame('', $new->getScheme());
        $this->assertSame('//example.com', (string)$new);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validSchemes(): array
    {
        return [
            'mailto' => ['mailto'],
            'ftp' => ['ftp'],
            'ws' => ['ws'],
            'wss' => ['wss'],
            'ssh' => ['ssh'],
            'git' => ['git'],
            'plus-dot-dash' => ['svn+ssh.x-y'],
        ];
    }

    #[DataProvider('validSchemes')]
    public function testConstructWithNonHttpSchemeIsAccepted(string $scheme): void
    {
        $uri = new Uri($scheme . '://example.com/path');
        $this->assertSame($scheme, $uri->getScheme());
        $this->assertSame($scheme . '://example.com/path', (string)$uri);
    }

    #[DataProvider('validSchemes')]
    public function testMutatingWithNonHttpSchemeIsAccepted(string $scheme): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withScheme($scheme);
        $this->assertSame($scheme, $new->getScheme());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidSchemes(): array
    {
        return [
            'leading-digit' => ['1http'],
            'leading-plus' => ['+http'],
            'underscore' => ['ht_tp'],
            'space' => ['ht tp'],
            'slash' => ['http/s'],
            'unicode' => ['httpä'],
        ];
    }

    #[DataProvider('invalidSchemes')]
    public function testMutatingWithInvalidSchemeRaisesAnException(string $scheme): void
    {
        $uri = new Uri('http://example.com');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid scheme');

        $uri->withScheme($scheme);
    }

    public function testPathIsNotPrefixedWithSlashIfSetWithoutOne(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withPath('foo/bar');
        $this->assertSame('foo/bar', $new->getPath());
    }

    public function testPathNotSlashPrefixedIsEmittedWithSlashDelimiterWhenUriIsCastToString(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withPath('foo/bar');
        $this->assertSame('http://example.com/foo/bar', $new->__toString());
    }

    public function testMultipleLeadingSlashesInPathAreCollapsed(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withPath('//evil.com/foo');
        $this->assertSame('/evil.com/foo', $new->getPath());
        $this->assertSame('http://example.com/evil.com/foo', (string)$new);
    }

    public function testStripsQueryPrefixIfPresent(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withQuery('?foo=bar');
        $this->assertSame('foo=bar', $new->getQuery());
    }

    public function testEncodeFragmentPrefixIfPresent(): void
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withFragment('#/foo/bar');
        $this->assertSame('%23/foo/bar', $new->getFragment());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function standardSchemePortCombinations(): array
    {
        return [
            'http' => ['http', 80],
            'https' => ['https', 443],
        ];
    }

    #[DataProvider('standardSchemePortCombinations')]
    public function testAuthorityOmitsPortForStandardSchemePortCombinations(string $scheme, int $port): void
    {
        $uri = new Uri()
            ->withHost('example.com')
            ->withScheme($scheme)
            ->withPort($port);
        $this->assertSame('example.com', $uri->getAuthority());
        $this->assertNull($uri->getPort());
        $this->assertSame($scheme . '://example.com', (string)$uri);
    }

    public function testNonStandardPortIsKeptForStandardScheme(): void
    {
        $uri = new Uri('http://example.com:443');
        $this->assertSame(443, $uri->getPort());
        $this->assertSame('example.com:443', $uri->getAuthority());
    }

    public function testPortIsKeptForSchemesWithoutKnownDefaultPort(): void
    {
        $uri = new Uri('ftp://example.com:21');
        $this->assertSame(21, $uri->getPort());
        $this->assertSame('ftp://example.com:21', (string)$uri);
    }

    /**
     * @return array<string, array{string, string|int}>
     */
    public static function mutations(): array
    {
        return [
            'scheme' => ['withScheme', 'https'],
            'user-info' => ['withUserInfo', 'foo'],
            'host' => ['withHost', 'www.example.com'],
            'port' => ['withPort', 8080],
            'path' => ['withPath', '/changed'],
            'query' => ['withQuery', 'changed=value'],
            'fragment' => ['withFragment', 'changed'],
        ];
    }

    #[DataProvider('mutations')]
    public function testMutationDoesNotChangeOriginalInstance(string $method, string|int $value): void
    {
        $uri = new Uri('http://example.com/path?query=string#fragment');
        $string = (string)$uri;

        $new = $uri->{$method}($value);

        $this->assertNotSame($uri, $new);
        $this->assertNotSame($string, (string)$new);
        $this->assertSame($string, (string)$uri);
    }

    public function testPathIsProperlyEncoded(): void
    {
        $uri = new Uri()->withPath('/foo^bar');
        $this->assertSame('/foo%5Ebar', $uri->getPath());
    }

    public function testPathDoesNotBecomeDoubleEncoded(): void
    {
        $uri = new Uri()->withPath('/foo%5Ebar');
        $this->assertSame('/foo%5Ebar', $uri->getPath());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function queryStringsForEncoding(): array
    {
        return [
            'key-only' => ['k^ey', 'k%5Eey'],
            'key-value' => ['k^ey=valu`', 'k%5Eey=valu%60'],
            'array-key-only' => ['key[]', 'key%5B%5D'],
            'array-key-value' => ['key[]=valu`', 'key%5B%5D=valu%60'],
            'complex' => ['k^ey&key[]=valu`&f<>=`bar', 'k%5Eey&key%5B%5D=valu%60&f%3C%3E=%60bar'],
            'empty-pairs' => ['&key=&=value&&key=a=b&', '&key=&=value&&key=a=b&'],
            'encoded-delimiters' => ['key=a%26b%3Dc&other=%25', 'key=a%26b%3Dc&other=%25'],
            'invalid-escapes' => ['key=%&other=%2&last=%GG', 'key=%25&other=%252&last=%25GG'],
            'mixed-utf8' => ["key=ουτοπία&other=\xFF=value", 'key=ουτοπία&other=%FF=value'],
        ];
    }

    #[DataProvider('queryStringsForEncoding')]
    public function testQueryIsProperlyEncoded(string $query, string $expected): void
    {
        $uri = new Uri()->withQuery($query);
        $this->assertSame($expected, $uri->getQuery());
    }

    #[DataProvider('queryStringsForEncoding')]
    public function testQueryIsNotDoubleEncoded(string $query, string $expected): void
    {
        $uri = new Uri()->withQuery($expected);
        $this->assertSame($expected, $uri->getQuery());
    }

    public function testFragmentIsProperlyEncoded(): void
    {
        $uri = new Uri()->withFragment('/p^th?key^=`bar#b@z');
        $this->assertSame('/p%5Eth?key%5E=%60bar%23b@z', $uri->getFragment());
    }

    public function testFragmentIsNotDoubleEncoded(): void
    {
        $expected = '/p%5Eth?key%5E=%60bar%23b@z';
        $uri = new Uri()->withFragment($expected);
        $this->assertSame($expected, $uri->getFragment());
    }

    public function testUtf8Uri(): void
    {
        $uri = new Uri('http://ουτοπία.δπθ.gr/');

        $this->assertSame('ουτοπία.δπθ.gr', $uri->getHost());
    }

    /**
     * @return array<array{string, string}>
     */
    public static function utf8PathsDataProvider(): array
    {
        return [
            ['http://example.com/тестовый_путь/', '/тестовый_путь/'],
            ['http://example.com/ουτοπία/', '/ουτοπία/'],
            ["http://example.com/\x21\x92", '/%21%92'],
            ['http://example.com/!?', '/%21'],
        ];
    }

    #[DataProvider('utf8PathsDataProvider')]
    public function testUtf8Path(string $url, string $result): void
    {
        $uri = new Uri($url);

        $this->assertSame($result, $uri->getPath());
    }

    /**
     * @return array<array{string, string}>
     */
    public static function utf8QueryStringsDataProvider(): array
    {
        return [
            ['http://example.com/?q=тестовый_путь', 'q=тестовый_путь'],
            ['http://example.com/?q=ουτοπία', 'q=ουτοπία'],
            ["http://example.com/?q=\x21\x92", 'q=!%92'],
        ];
    }

    #[DataProvider('utf8QueryStringsDataProvider')]
    public function testUtf8Query(string $url, string $result): void
    {
        $uri = new Uri($url);

        $this->assertSame($result, $uri->getQuery());
    }

    public function testUriDoesNotAppendColonToHostIfPortIsEmpty(): void
    {
        $uri = new Uri()->withHost('google.com');
        $this->assertSame('//google.com', (string)$uri);
    }

    public function testAuthorityIsPrefixedByDoubleSlashIfPresent(): void
    {
        $uri = new Uri()->withHost('example.com');
        $this->assertSame('//example.com', (string)$uri);
    }

    public function testReservedCharsInPathUnencoded(): void
    {
        $uri = new Uri()
            ->withScheme('https')
            ->withHost('api.linkedin.com')
            ->withPath('/v1/people/~:(first-name,last-name,email-address,picture-url)');

        $this->assertStringContainsString(
            '/v1/people/~:(first-name,last-name,email-address,picture-url)',
            (string)$uri,
        );
    }

    public function testHostIsLowercase(): void
    {
        $uri = new Uri('http://HOST.LOC/path?q=1');
        $this->assertSame('host.loc', $uri->getHost());
    }

    public function testHostIsLowercaseWhenIsSetViaWithHost(): void
    {
        $uri = new Uri()->withHost('NEW-HOST.COM');
        $this->assertSame('new-host.com', $uri->getHost());
    }

    public function testUriDistinguishZeroFromEmptyString(): void
    {
        $expected = 'https://0:0@0:1/0?0#0';
        $uri = new Uri($expected);
        $this->assertSame($expected, (string)$uri);
    }
}
