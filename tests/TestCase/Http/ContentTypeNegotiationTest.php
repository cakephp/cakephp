<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\Http;

use Cake\Http\ContentTypeNegotiation;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class ContentTypeNegotiationTest extends TestCase
{
    public function testPrefersNoAccept()
    {
        $request = new ServerRequest([
            'url' => '/dashboard',
        ]);
        $content = new ContentTypeNegotiation();
        $this->assertNull($content->prefers($request));

        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => '',
            ],
        ]);
        $this->assertNull($content->prefers($request));
    }

    public function testPrefersFirstMatch()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/json',
            ],
        ]);
        $this->assertEquals('application/json', $content->prefers($request));

        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/json,application/xml',
            ],
        ]);
        $this->assertEquals('application/json', $content->prefers($request));
    }

    public function testPrefersQualValue()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
            ],
        ]);
        $this->assertEquals('text/xml', $content->prefers($request));

        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'text/plain;q=0.8,application/json;q=0.9',
            ],
        ]);
        $this->assertEquals('application/json', $content->prefers($request));
    }

    public function testPrefersChoiceNoMatch()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/json',
            ],
        ]);
        $this->assertNull($content->prefersChoice($request, ['text/html']));
    }

    public function testPrefersChoiceSimple()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/json',
            ],
        ]);
        $this->assertEquals(
            'application/json',
            $content->prefersChoice($request, ['text/html', 'application/json'])
        );
    }

    public function testPrefersChoiceQualValue()
    {
        $content = new ContentTypeNegotiation();
        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/json;q=0.5,application/xml;q=0.6,application/pdf;q=0.3',
            ],
        ]);
        $this->assertEquals(
            'application/json',
            $content->prefersChoice($request, ['text/html', 'application/json'])
        );
        $this->assertEquals(
            'application/pdf',
            $content->prefersChoice($request, ['text/html', 'application/pdf'])
        );
        $this->assertEquals(
            'application/json',
            $content->prefersChoice($request, ['application/json', 'application/pdf'])
        );
        $this->assertNull(
            $content->prefersChoice($request, ['image/png', 'text/html'])
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

        $request = new ServerRequest([
            'url' => '/dashboard',
            'environment' => [
                'HTTP_ACCEPT' => 'application/pdf;q=0.3,application/json;q=0.5,application/xml;q=0.5',
            ],
        ]);
        $result = $content->parseAccept($request);
        $expected = [
            '0.5' => ['application/json', 'application/xml'],
            '0.3' => ['application/pdf'],
        ];
        $this->assertEquals($expected, $result, 'Sorting is incorrect.');
    }
}
