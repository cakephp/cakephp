<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Http;

use Cake\Http\HeaderParser;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class HeaderParserTest extends TestCase
{
    /**
     * Tests getting a parsed representation of a Link header
     */
    public function testLink(): void
    {
        $response = new Response();
        $this->assertFalse($response->hasHeader('Link'));

        $new = $response->withAddedLink('http://example.com');
        $this->assertSame('<http://example.com>', $new->getHeaderLine('Link'));
        $expected = [
            [['link' => 'http://example.com']],
        ];
        $this->assertSame($expected, HeaderParser::link($new->getHeader('Link')));

        $new = $response->withAddedLink('http://example.com/苗条');
        $this->assertSame('<http://example.com/苗条>', $new->getHeaderLine('Link'));
        $expected = [
            [['link' => 'http://example.com/苗条']],
        ];
        $this->assertSame($expected, HeaderParser::link($new->getHeader('Link')));

        $new = $response->withAddedLink('http://example.com', ['rel' => 'prev']);
        $this->assertSame('<http://example.com>; rel="prev"', $new->getHeaderLine('Link'));
        $expected = [
            [
                [
                    'link' => 'http://example.com',
                    'rel' => 'prev',
                ],
            ],
        ];
        $this->assertSame($expected, HeaderParser::link($new->getHeader('Link')));

        $new = $response->withAddedLink('http://example.com', ['rel' => 'prev', 'results' => 'true']);
        $this->assertSame('<http://example.com>; rel="prev"; results="true"', $new->getHeaderLine('Link'));
        $expected = [
            [
                [
                    'link' => 'http://example.com',
                    'rel' => 'prev',
                    'results' => 'true',
                ],
            ],
        ];
        $this->assertSame($expected, HeaderParser::link($new->getHeader('Link')));

        $new = $response
            ->withAddedLink('http://example.com', ['rel' => 'prev'])
            ->withAddedLink('http://example.com', ['rel' => 'next']);
        $this->assertSame('<http://example.com>; rel="prev",<http://example.com>; rel="next"', $new->getHeaderLine('Link'));
        $expected = [
            [
                [
                    'link' => 'http://example.com',
                    'rel' => 'prev',
                ],
            ],
            [
                [
                    'link' => 'http://example.com',
                    'rel' => 'next',
                ],
            ],
        ];
        $this->assertSame($expected, HeaderParser::link($new->getHeader('Link')));

        $encodedLinkHeader = '</extended-attr-example>; rel=start; title*=UTF-8\'en\'%E2%91%A0%E2%93%AB%E2%85%93%E3%8F%A8%E2%99%B3%F0%9D%84%9E%CE%BB';
        $new = $response
            ->withHeader('Link', $encodedLinkHeader);
        $this->assertSame($encodedLinkHeader, $new->getHeaderLine('Link'));
        $expected = [
            [
                [
                    'link' => '/extended-attr-example',
                    'rel' => 'start',
                    'title*' => [
                        'language' => 'en',
                        'encoding' => 'UTF-8',
                        'value' => '①⓫⅓㏨♳𝄞λ',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, HeaderParser::link($new->getHeader('Link')));
    }

    public function testQualifiers(): void
    {
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/json;q=0.5,application/xml;q=0.6,application/pdf;q=0.3',
            ],
        ]);
        $result = HeaderParser::qualifiers($request->getHeaderLine('Accept'));
        $expected = [
            '0.6' => ['application/xml'],
            '0.5' => ['application/json'],
            '0.3' => ['application/pdf'],
        ];
        $this->assertEquals($expected, $result);
    }
}
