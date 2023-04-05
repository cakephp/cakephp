<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Http;

use Cake\Http\HeaderUtility;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class HeaderUtilityTest extends TestCase
{
    /**
     * Tests getting a parsed representation of a Link header
     */
    public function testParseLinks(): void
    {
        $response = new Response();
        $this->assertFalse($response->hasHeader('Link'));

        $new = $response->withAddedLink('http://example.com');
        $this->assertSame('<http://example.com>', $new->getHeaderLine('Link'));
        $expected = [
            ['link' => 'http://example.com'],
        ];
        $this->assertSame($expected, HeaderUtility::parseLinks($new->getHeader('Link')));

        $new = $response->withAddedLink('http://example.com/苗条');
        $this->assertSame('<http://example.com/苗条>', $new->getHeaderLine('Link'));
        $expected = [
            ['link' => 'http://example.com/苗条'],
        ];
        $this->assertSame($expected, HeaderUtility::parseLinks($new->getHeader('Link')));

        $new = $response->withAddedLink('http://example.com', ['rel' => 'prev']);
        $this->assertSame('<http://example.com>; rel="prev"', $new->getHeaderLine('Link'));
        $expected = [
            [
                'link' => 'http://example.com',
                'rel' => 'prev',
            ],
        ];
        $this->assertSame($expected, HeaderUtility::parseLinks($new->getHeader('Link')));

        $new = $response->withAddedLink('http://example.com', ['rel' => 'prev', 'results' => 'true']);
        $this->assertSame('<http://example.com>; rel="prev"; results="true"', $new->getHeaderLine('Link'));
        $expected = [
            [
                'link' => 'http://example.com',
                'rel' => 'prev',
                'results' => 'true',
            ],
        ];
        $this->assertSame($expected, HeaderUtility::parseLinks($new->getHeader('Link')));

        $new = $response
            ->withAddedLink('http://example.com/1', ['rel' => 'prev'])
            ->withAddedLink('http://example.com/3', ['rel' => 'next']);
        $this->assertSame('<http://example.com/1>; rel="prev",<http://example.com/3>; rel="next"', $new->getHeaderLine('Link'));
        $expected = [
            [
                'link' => 'http://example.com/1',
                'rel' => 'prev',
            ],
            [
                'link' => 'http://example.com/3',
                'rel' => 'next',
            ],
        ];
        $this->assertSame($expected, HeaderUtility::parseLinks($new->getHeader('Link')));

        $encodedLinkHeader = '</extended-attr-example>; rel=start; title*=UTF-8\'en\'%E2%91%A0%E2%93%AB%E2%85%93%E3%8F%A8%E2%99%B3%F0%9D%84%9E%CE%BB';
        $new = $response
            ->withHeader('Link', $encodedLinkHeader);
        $this->assertSame($encodedLinkHeader, $new->getHeaderLine('Link'));
        $expected = [
            [
                'link' => '/extended-attr-example',
                'rel' => 'start',
                'title*' => [
                    'language' => 'en',
                    'encoding' => 'UTF-8',
                    'value' => '①⓫⅓㏨♳𝄞λ',
                ],
            ],
        ];
        $this->assertSame($expected, HeaderUtility::parseLinks($new->getHeader('Link')));
    }

    public function testParseAccept(): void
    {
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/json;q=0.5,application/xml;q=0.6,application/pdf;q=0.3',
            ],
        ]);
        $result = HeaderUtility::parseAccept($request->getHeaderLine('Accept'));
        $expected = [
            '0.6' => ['application/xml'],
            '0.5' => ['application/json'],
            '0.3' => ['application/pdf'],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testParseWwwAuthenticate(): void
    {
        $result = HeaderUtility::parseWwwAuthenticate('Digest realm="The batcave",nonce="4cded326c6c51"');
        $expected = [
            'realm' => 'The batcave',
            'nonce' => '4cded326c6c51',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testWwwAuthenticateWithAlgo(): void
    {
        $result = HeaderUtility::parseWwwAuthenticate('Digest qop="auth", realm="shellyplus1pm-44179393e8a8", nonce="63f8c86f", algorithm=SHA-256');
        $expected = [
            'qop' => 'auth',
            'realm' => 'shellyplus1pm-44179393e8a8',
            'nonce' => '63f8c86f',
            'algorithm' => 'SHA-256',
        ];
        $this->assertEquals($expected, $result);
    }
}
