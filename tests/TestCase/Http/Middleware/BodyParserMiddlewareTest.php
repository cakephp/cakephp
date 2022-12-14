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
 * @since         3.5.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Middleware;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TestApp\Http\TestRequestHandler;

/**
 * Test for BodyParser
 */
class BodyParserMiddlewareTest extends TestCase
{
    /**
     * Data provider for HTTP method tests.
     *
     * HEAD and GET do not populate $_POST or request->data.
     *
     * @return array
     */
    public static function safeHttpMethodProvider(): array
    {
        return [
            ['GET'],
            ['HEAD'],
        ];
    }

    /**
     * Data provider for HTTP methods that can contain request bodies.
     *
     * @return array
     */
    public static function httpMethodProvider(): array
    {
        return [
            ['PATCH'], ['PUT'], ['POST'], ['DELETE'],
        ];
    }

    /**
     * Data provider for JSON scalar and how it should be parsed
     *
     * @return array
     */
    public static function jsonScalarValues(): array
    {
        return [
            ['', []], // Requests without body
            ['true', [true]],
            ['false', [false]],
            ['0', [0]],
            ['0.1', [0.1]],
            ['"cake"', ['cake']],
            ['null', []],
        ];
    }

    /**
     * test constructor options
     */
    public function testConstructorMethodsOption(): void
    {
        $parser = new BodyParserMiddleware(['methods' => ['PUT']]);
        $this->assertEquals(['PUT'], $parser->getMethods());
    }

    /**
     * test constructor options
     */
    public function testConstructorXmlOption(): void
    {
        $parser = new BodyParserMiddleware(['json' => false]);
        $this->assertEquals([], $parser->getParsers(), 'Xml off by default');

        $parser = new BodyParserMiddleware(['json' => false, 'xml' => false]);
        $this->assertEquals([], $parser->getParsers(), 'No Xml types set.');

        $parser = new BodyParserMiddleware(['json' => false, 'xml' => true]);
        $this->assertEquals(
            ['application/xml', 'text/xml'],
            array_keys($parser->getParsers()),
            'Default XML parsers are not set.'
        );
    }

    /**
     * test constructor options
     */
    public function testConstructorJsonOption(): void
    {
        $parser = new BodyParserMiddleware(['json' => false]);
        $this->assertEquals([], $parser->getParsers(), 'No JSON types set.');

        $parser = new BodyParserMiddleware([]);
        $this->assertEquals(
            ['application/json', 'text/json'],
            array_keys($parser->getParsers()),
            'Default JSON parsers are not set.'
        );
    }

    /**
     * test setMethods()
     */
    public function testSetMethodsReturn(): void
    {
        $parser = new BodyParserMiddleware();
        $this->assertSame($parser, $parser->setMethods(['PUT']));
        $this->assertEquals(['PUT'], $parser->getMethods());
    }

    /**
     * test addParser()
     */
    public function testAddParserReturn(): void
    {
        $parser = new BodyParserMiddleware(['json' => false]);
        $f1 = function (string $body) {
            return json_decode($body, true);
        };
        $this->assertSame($parser, $parser->addParser(['application/json'], $f1));
    }

    /**
     * test last parser defined wins
     */
    public function testAddParserOverwrite(): void
    {
        $parser = new BodyParserMiddleware(['json' => false]);

        $f1 = function (string $body) {
            return json_decode($body, true);
        };
        $f2 = function (string $body) {
            return ['overridden'];
        };
        $parser->addParser(['application/json'], $f1);
        $parser->addParser(['application/json'], $f2);

        $this->assertSame(['application/json' => $f2], $parser->getParsers());
    }

    /**
     * test skipping parsing on unknown type
     *
     * @dataProvider httpMethodProvider
     */
    public function testInvokeMismatchedType(string $method): void
    {
        $parser = new BodyParserMiddleware();

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'CONTENT_TYPE' => 'text/csv',
            ],
            'input' => 'a,b,c',
        ]);
        $handler = new TestRequestHandler(function ($req) {
            $this->assertEquals([], $req->getParsedBody());

            return new Response();
        });
        $parser->process($request, $handler);
    }

    /**
     * test parsing on valid http method
     *
     * @dataProvider httpMethodProvider
     */
    public function testInvokeCaseInsensitiveContentType(string $method): void
    {
        $parser = new BodyParserMiddleware();

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'CONTENT_TYPE' => 'ApPlIcAtIoN/JSoN',
            ],
            'input' => '{"title": "yay"}',
        ]);
        $handler = new TestRequestHandler(function ($req) {
            $this->assertEquals(['title' => 'yay'], $req->getParsedBody());

            return new Response();
        });
        $parser->process($request, $handler);
    }

    /**
     * test parsing on valid http method
     *
     * @dataProvider httpMethodProvider
     */
    public function testInvokeParse(string $method): void
    {
        $parser = new BodyParserMiddleware();

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'CONTENT_TYPE' => 'application/json',
            ],
            'input' => '{"title": "yay"}',
        ]);
        $handler = new TestRequestHandler(function ($req) {
            $this->assertEquals(['title' => 'yay'], $req->getParsedBody());

            return new Response();
        });
        $parser->process($request, $handler);
    }

    /**
     * test parsing on valid http method with charset
     */
    public function testInvokeParseStripCharset(): void
    {
        $parser = new BodyParserMiddleware();

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json; charset=utf-8',
            ],
            'input' => '{"title": "yay"}',
        ]);
        $handler = new TestRequestHandler(function ($req) {
            $this->assertEquals(['title' => 'yay'], $req->getParsedBody());

            return new Response();
        });
        $parser->process($request, $handler);
    }

    /**
     * test parsing on ignored http method
     *
     * @dataProvider safeHttpMethodProvider
     */
    public function testInvokeNoParseOnSafe(string $method): void
    {
        $parser = new BodyParserMiddleware();

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => $method,
                'CONTENT_TYPE' => 'application/json',
            ],
            'input' => '{"title": "yay"}',
        ]);
        $handler = new TestRequestHandler(function ($req) {
            $this->assertEquals([], $req->getParsedBody());

            return new Response();
        });
        $parser->process($request, $handler);
    }

    /**
     * test parsing XML bodies.
     */
    public function testInvokeXml(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<article>
    <title>yay</title>
</article>
XML;

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/xml',
            ],
            'input' => $xml,
        ]);
        $handler = new TestRequestHandler(function ($req) {
            $expected = [
                'article' => ['title' => 'yay'],
            ];
            $this->assertEquals($expected, $req->getParsedBody());

            return new Response();
        });
        $parser = new BodyParserMiddleware(['xml' => true]);
        $parser->process($request, $handler);
    }

    /**
     * Test that CDATA is removed in XML data.
     */
    public function testInvokeXmlCdata(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<article>
    <id>1</id>
    <title><![CDATA[first]]></title>
</article>
XML;
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/xml',
            ],
            'input' => $xml,
        ]);
        $handler = new TestRequestHandler(function ($req) {
            $expected = [
                'article' => [
                    'id' => 1,
                    'title' => 'first',
                ],
            ];
            $this->assertEquals($expected, $req->getParsedBody());

            return new Response();
        });
        $parser = new BodyParserMiddleware(['xml' => true]);
        $parser->process($request, $handler);
    }

    /**
     * Test that internal entity recursion is ignored.
     */
    public function testInvokeXmlInternalEntities(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE item [
  <!ENTITY item "item">
  <!ENTITY item1 "&item;&item;&item;&item;&item;&item;">
  <!ENTITY item2 "&item1;&item1;&item1;&item1;&item1;&item1;&item1;&item1;&item1;">
  <!ENTITY item3 "&item2;&item2;&item2;&item2;&item2;&item2;&item2;&item2;&item2;">
  <!ENTITY item4 "&item3;&item3;&item3;&item3;&item3;&item3;&item3;&item3;&item3;">
  <!ENTITY item5 "&item4;&item4;&item4;&item4;&item4;&item4;&item4;&item4;&item4;">
  <!ENTITY item6 "&item5;&item5;&item5;&item5;&item5;&item5;&item5;&item5;&item5;">
  <!ENTITY item7 "&item6;&item6;&item6;&item6;&item6;&item6;&item6;&item6;&item6;">
  <!ENTITY item8 "&item7;&item7;&item7;&item7;&item7;&item7;&item7;&item7;&item7;">
]>
<item>
  <description>&item8;</description>
</item>
XML;
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/xml',
            ],
            'input' => $xml,
        ]);
        $response = new Response();
        $handler = new TestRequestHandler(function ($req) {
            $this->assertEquals([], $req->getParsedBody());

            return new Response();
        });
        $parser = new BodyParserMiddleware(['xml' => true]);
        $parser->process($request, $handler);
    }

    /**
     * test parsing non array/object values on JSON
     *
     * @dataProvider jsonScalarValues
     * @param mixed $expected
     */
    public function testInvokeParseNoArray(string $body, $expected): void
    {
        $parser = new BodyParserMiddleware();

        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
            'input' => $body,
        ]);
        $handler = new TestRequestHandler(function ($req) use ($expected) {
            $this->assertSame($expected, $req->getParsedBody());

            return new Response();
        });
        $parser->process($request, $handler);
    }

    /**
     * test parsing fails will raise a bad request.
     */
    public function testInvokeParseInvalidJson(): void
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
            'input' => 'lol',
        ]);
        $handler = new TestRequestHandler(function ($req) {
            return new Response();
        });
        $this->expectException(BadRequestException::class);
        $parser = new BodyParserMiddleware();
        $parser->process($request, $handler);
    }
}
