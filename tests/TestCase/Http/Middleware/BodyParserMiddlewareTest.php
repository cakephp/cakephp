<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
    public static function safeHttpMethodProvider()
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
    public static function httpMethodProvider()
    {
        return [
            ['PATCH'], ['PUT'], ['POST'], ['DELETE'],
        ];
    }

    /**
     * test constructor options
     *
     * @return void
     */
    public function testConstructorMethodsOption()
    {
        $parser = new BodyParserMiddleware(['methods' => ['PUT']]);
        $this->assertEquals(['PUT'], $parser->getMethods());
    }

    /**
     * test constructor options
     *
     * @return void
     */
    public function testConstructorXmlOption()
    {
        $parser = new BodyParserMiddleware(['json' => false]);
        $this->assertEquals([], $parser->getParsers(), 'Xml off by default');

        $parser = new BodyParserMiddleware(['json' => false, 'xml' => false]);
        $this->assertEquals([], $parser->getParsers(), 'No Xml types set.');

        $parser = new BodyParserMiddleware(['json' => false, 'xml' => true]);
        $expected = [
            'application/xml' => [$parser, 'decodeXml'],
            'text/xml' => [$parser, 'decodeXml'],
        ];
        $this->assertEquals($expected, $parser->getParsers(), 'Xml types are incorrect.');
    }

    /**
     * test constructor options
     *
     * @return void
     */
    public function testConstructorJsonOption()
    {
        $parser = new BodyParserMiddleware(['json' => false]);
        $this->assertEquals([], $parser->getParsers(), 'No JSON types set.');

        $parser = new BodyParserMiddleware([]);
        $expected = [
            'application/json' => [$parser, 'decodeJson'],
            'text/json' => [$parser, 'decodeJson'],
        ];
        $this->assertEquals($expected, $parser->getParsers(), 'JSON types are incorrect.');
    }

    /**
     * test setMethods()
     *
     * @return void
     */
    public function testSetMethodsReturn()
    {
        $parser = new BodyParserMiddleware();
        $this->assertSame($parser, $parser->setMethods(['PUT']));
        $this->assertEquals(['PUT'], $parser->getMethods());
    }

    /**
     * test addParser()
     *
     * @return void
     */
    public function testAddParserReturn()
    {
        $parser = new BodyParserMiddleware(['json' => false]);
        $this->assertSame($parser, $parser->addParser(['application/json'], 'json_decode'));
    }

    /**
     * test last parser defined wins
     *
     * @return void
     */
    public function testAddParserOverwrite()
    {
        $parser = new BodyParserMiddleware(['json' => false]);
        $parser->addParser(['application/json'], 'json_decode');
        $parser->addParser(['application/json'], 'strpos');

        $this->assertEquals(['application/json' => 'strpos'], $parser->getParsers());
    }

    /**
     * test skipping parsing on unknown type
     *
     * @dataProvider httpMethodProvider
     * @return void
     */
    public function testInvokeMismatchedType($method)
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
     * @return void
     */
    public function testInvokeCaseInsensitiveContentType($method)
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
     * @return void
     */
    public function testInvokeParse($method)
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
     *
     * @return void
     */
    public function testInvokeParseStripCharset()
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
     * @return void
     */
    public function testInvokeNoParseOnSafe($method)
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
     *
     * @return void
     */
    public function testInvokeXml()
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
     *
     * @return void
     */
    public function testInvokeXmlCdata()
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
     *
     * @return void
     */
    public function testInvokeXmlInternalEntities()
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
     * test parsing fails will raise a bad request.
     *
     * @return void
     */
    public function testInvokeParseNoArray()
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
