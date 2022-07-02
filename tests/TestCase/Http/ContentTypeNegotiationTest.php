<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Http;

use Cake\Http\ContentTypeNegotiation;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class ContentTypeNegotiationTest extends TestCase
{
    public function testPreferredTypeNoAccept()
    {
        $request = new ServerRequest([
            'url' => '/dashboard',
        ]);
        $content = new ContentTypeNegotiation();
        $this->assertNull($content->preferredType($request));

        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => '',
            ],
        ]);
        $this->assertNull($content->preferredType($request));
    }

    public function testPreferredTypeFirefoxHtml()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            ],
        ]);
        $this->assertEquals('text/html', $content->preferredType($request));
        $this->assertEquals('text/html', $content->preferredType($request, ['text/html', 'application/xml']));
        $this->assertEquals('application/xml', $content->preferredType($request, ['application/xml']));
        $this->assertNull($content->preferredType($request, ['application/json']));
    }

    public function testPreferredTypeFirstMatch()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/json',
            ],
        ]);
        $this->assertEquals('application/json', $content->preferredType($request));

        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/json,application/xml',
            ],
        ]);
        $this->assertEquals('application/json', $content->preferredType($request));
    }

    public function testPreferredTypeQualValue()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'text/xml,application/xml,application/xhtml+xml,' .
                    'text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
            ],
        ]);
        $this->assertEquals('text/xml', $content->preferredType($request));

        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'text/plain;q=0.8,application/json;q=0.9',
            ],
        ]);
        $this->assertEquals('application/json', $content->preferredType($request));
    }

    public function testPreferredTypeSimple()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/json',
            ],
        ]);
        $this->assertNull($content->preferredType($request, ['text/html']));

        $request = $request->withEnv('HTTP_ACCEPT', 'application/json');
        $this->assertEquals(
            'application/json',
            $content->preferredType($request, ['text/html', 'application/json'])
        );
    }

    public function testParseAccept()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/json;q=0.5,application/xml;q=0.6,application/pdf;q=0.3',
            ],
        ]);
        $result = $content->parseAccept($request);
        $expected = [
            '0.6' => ['application/xml'],
            '0.5' => ['application/json'],
            '0.3' => ['application/pdf'],
        ];
        $this->assertEquals($expected, $result);

        $request = $request->withEnv(
            'HTTP_ACCEPT',
            'application/pdf;q=0.3,application/json;q=0.5,application/xml;q=0.5'
        );
        $result = $content->parseAccept($request);
        $expected = [
            '0.5' => ['application/json', 'application/xml'],
            '0.3' => ['application/pdf'],
        ];
        $this->assertEquals($expected, $result, 'Sorting is incorrect.');
    }

    public function testParseAcceptLanguage()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT_LANGUAGE' => '',
            ],
        ]);
        $this->assertEmpty($content->parseAcceptLanguage($request));

        $request = $request->withEnv('HTTP_ACCEPT_LANGUAGE', 'es_mx,en_ca');
        $expected = [
            '1.0' => ['es_mx', 'en_ca'],
        ];
        $this->assertEquals($expected, $content->parseAcceptLanguage($request));

        $request = $request->withEnv('HTTP_ACCEPT_LANGUAGE', 'en-US,en;q=0.8,pt-BR;q=0.6,pt;q=0.4');
        $expected = [
            '1.0' => ['en-US'],
            '0.8' => ['en'],
            '0.6' => ['pt-BR'],
            '0.4' => ['pt'],
        ];
        $this->assertEquals($expected, $content->parseAcceptLanguage($request));
    }

    public function testAcceptLanguage()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT_LANGUAGE' => 'en_US,en_CA',
            ],
        ]);
        $this->assertFalse($content->acceptLanguage($request, 'es-mx'));
        $this->assertTrue($content->acceptLanguage($request, 'en-ca'));
        $this->assertTrue($content->acceptLanguage($request, 'en-CA'), 'Input code is lowercased');
        $this->assertFalse($content->acceptLanguage($request, 'en_CA'), 'Input code not normalized');
    }

    public function testAcceptedLanguage()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT_LANGUAGE' => 'pt-BR;q=0.6,en_US,en_CA;q=0.8',
            ],
        ]);
        $expected = ['en-us', 'en-ca', 'pt-br'];
        $this->assertEquals($expected, $content->acceptedLanguages($request, 'es-mx'));
    }
}
