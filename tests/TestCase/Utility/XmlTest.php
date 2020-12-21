<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\Utility\Exception\XmlException;
use Cake\Utility\Xml;
use TypeError;

/**
 * XmlTest class
 */
class XmlTest extends TestCase
{
    /**
     * autoFixtures property
     *
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * fixtures property
     *
     * @var array
     */
    protected $fixtures = [
        'core.Articles', 'core.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->_appEncoding = Configure::read('App.encoding');
        Configure::write('App.encoding', 'UTF-8');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Configure::write('App.encoding', $this->_appEncoding);
    }

    public function testExceptionChainingForInvalidInput()
    {
        try {
            $value = 'invalid-xml-input<<';
            Xml::build($value);
            $this->fail('This line should not be executed because of exception above.');
        } catch (XmlException $exception) {
            $cause = $exception->getPrevious();
            $this->assertNotNull($cause);
            $this->assertInstanceOf(\Exception::class, $cause);
        }
    }

    /**
     * testBuild method
     *
     * @return void
     */
    public function testBuild()
    {
        $xml = '<tag>value</tag>';
        $obj = Xml::build($xml);
        $this->assertInstanceOf(\SimpleXMLElement::class, $obj);
        $this->assertSame('tag', (string)$obj->getName());
        $this->assertSame('value', (string)$obj);

        $xml = '<?xml version="1.0" encoding="UTF-8"?><tag>value</tag>';
        $this->assertEquals($obj, Xml::build($xml));

        $obj = Xml::build($xml, ['return' => 'domdocument']);
        $this->assertInstanceOf(\DOMDocument::class, $obj);
        $this->assertSame('tag', $obj->firstChild->nodeName);
        $this->assertSame('value', $obj->firstChild->nodeValue);

        $xml = CORE_TESTS . 'Fixture/sample.xml';
        $obj = Xml::build($xml, ['readFile' => true]);
        $this->assertSame('tags', $obj->getName());
        $this->assertSame(2, count($obj));

        $this->assertEquals(
            Xml::build($xml, ['readFile' => true]),
            Xml::build(file_get_contents($xml))
        );

        $obj = Xml::build($xml, ['return' => 'domdocument', 'readFile' => true]);
        $this->assertSame('tags', $obj->firstChild->nodeName);

        $this->assertEquals(
            Xml::build($xml, ['return' => 'domdocument', 'readFile' => true]),
            Xml::build(file_get_contents($xml), ['return' => 'domdocument'])
        );

        $xml = ['tag' => 'value'];
        $obj = Xml::build($xml);
        $this->assertSame('tag', $obj->getName());
        $this->assertSame('value', (string)$obj);

        $obj = Xml::build($xml, ['return' => 'domdocument']);
        $this->assertSame('tag', $obj->firstChild->nodeName);
        $this->assertSame('value', $obj->firstChild->nodeValue);

        $obj = Xml::build($xml, ['return' => 'domdocument', 'encoding' => '']);
        $this->assertDoesNotMatchRegularExpression('/encoding/', $obj->saveXML());
    }

    /**
     * test build() method with huge option
     *
     * @return void
     */
    public function testBuildHuge()
    {
        $xml = '<tag>value</tag>';
        $obj = Xml::build($xml, ['parseHuge' => true]);
        $this->assertSame('tag', $obj->getName());
        $this->assertSame('value', (string)$obj);
    }

    /**
     * Test that the readFile option disables local file parsing.
     *
     * @return void
     */
    public function testBuildFromFileWhenDisabled()
    {
        $this->expectException(\Cake\Utility\Exception\XmlException::class);
        $xml = CORE_TESTS . 'Fixture/sample.xml';
        $obj = Xml::build($xml, ['readFile' => false]);
    }

    /**
     * Test build() with a Collection instance.
     *
     * @return void
     */
    public function testBuildCollection()
    {
        $xml = new Collection(['tag' => 'value']);
        $obj = Xml::build($xml);

        $this->assertSame('tag', $obj->getName());
        $this->assertSame('value', (string)$obj);

        $xml = new Collection([
            'response' => [
                'users' => new Collection(['leonardo', 'raphael']),
            ],
        ]);
        $obj = Xml::build($xml);
        $this->assertStringContainsString('<users>leonardo</users>', $obj->saveXML());
    }

    /**
     * Test build() with ORM\Entity instances wrapped in a Collection.
     *
     * @return void
     */
    public function testBuildOrmEntity()
    {
        $user = new Entity(['username' => 'mark', 'email' => 'mark@example.com']);
        $xml = new Collection([
            'response' => [
                'users' => new Collection([$user]),
            ],
        ]);
        $obj = Xml::build($xml);
        $output = $obj->saveXML();
        $this->assertStringContainsString('<username>mark</username>', $output);
        $this->assertStringContainsString('<email>mark@example.com</email>', $output);
    }

    /**
     * data provider function for testBuildInvalidData
     *
     * @return array
     */
    public static function invalidDataProvider()
    {
        return [
            [null],
            [false],
            [''],
            ['http://localhost/notthere.xml'],
        ];
    }

    /**
     * testBuildInvalidData
     *
     * @dataProvider invalidDataProvider
     * @return void
     */
    public function testBuildInvalidData($value)
    {
        $this->expectException(\RuntimeException::class);
        Xml::build($value);
    }

    /**
     * Test that building SimpleXmlElement with invalid XML causes the right exception.
     *
     * @return void
     */
    public function testBuildInvalidDataSimpleXml()
    {
        $this->expectException(\Cake\Utility\Exception\XmlException::class);
        $input = '<derp';
        Xml::build($input, ['return' => 'simplexml']);
    }

    /**
     * test build with a single empty tag
     *
     * @return void
     */
    public function testBuildEmptyTag()
    {
        try {
            Xml::build('<tag>');
            $this->fail('No exception');
        } catch (\Exception $e) {
            $this->assertTrue(true, 'An exception was raised');
        }
    }

    /**
     * testLoadHtml method
     *
     * @return void
     */
    public function testLoadHtml()
    {
        $htmlFile = CORE_TESTS . 'Fixture/sample.html';
        $html = file_get_contents($htmlFile);
        $paragraph = 'Browsers usually indent blockquote elements.';
        $blockquote = "
For 50 years, WWF has been protecting the future of nature.
The world's leading conservation organization,
WWF works in 100 countries and is supported by
1.2 million members in the United States and
close to 5 million globally.
";

        $xml = Xml::loadHtml($html);
        $this->assertTrue(isset($xml->body->p), 'Paragraph present');
        $this->assertSame($paragraph, (string)$xml->body->p);
        $this->assertTrue(isset($xml->body->blockquote), 'Blockquote present');
        $this->assertSame($blockquote, (string)$xml->body->blockquote);

        $xml = Xml::loadHtml($html, ['parseHuge' => true]);
        $this->assertTrue(isset($xml->body->p), 'Paragraph present');
        $this->assertSame($paragraph, (string)$xml->body->p);
        $this->assertTrue(isset($xml->body->blockquote), 'Blockquote present');
        $this->assertSame($blockquote, (string)$xml->body->blockquote);

        $xml = Xml::loadHtml($html);
        $this->assertSame($html, "<!DOCTYPE html>\n" . $xml->asXML() . "\n");

        $xml = Xml::loadHtml($html, ['return' => 'dom']);
        $this->assertSame($html, $xml->saveHTML());
    }

    /**
     * test loadHtml with a empty HTML string
     *
     * @return void
     */
    public function testLoadHtmlEmptyHtml()
    {
        $this->expectException(TypeError::class);
        Xml::loadHtml(null);
    }

    /**
     * testFromArray method
     *
     * @return void
     */
    public function testFromArray()
    {
        $xml = ['tag' => 'value'];
        $obj = Xml::fromArray($xml);
        $this->assertSame('tag', $obj->getName());
        $this->assertSame('value', (string)$obj);

        $xml = ['tag' => null];
        $obj = Xml::fromArray($xml);
        $this->assertSame('tag', $obj->getName());
        $this->assertSame('', (string)$obj);

        $xml = ['tag' => ['@' => 'value']];
        $obj = Xml::fromArray($xml);
        $this->assertSame('tag', $obj->getName());
        $this->assertSame('value', (string)$obj);

        $xml = [
            'tags' => [
                'tag' => [
                    [
                        'id' => '1',
                        'name' => 'defect',
                    ],
                    [
                        'id' => '2',
                        'name' => 'enhancement',
                    ],
                ],
            ],
        ];
        $obj = Xml::fromArray($xml, ['format' => 'attributes']);
        $this->assertInstanceOf(\SimpleXMLElement::class, $obj);
        $this->assertSame('tags', $obj->getName());
        $this->assertSame(2, count($obj));
        $xmlText = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tags>
  <tag id="1" name="defect"/>
  <tag id="2" name="enhancement"/>
</tags>
XML;
        $this->assertXmlStringEqualsXmlString($xmlText, $obj->asXML());

        $obj = Xml::fromArray($xml);
        $this->assertInstanceOf(\SimpleXMLElement::class, $obj);
        $this->assertSame('tags', $obj->getName());
        $this->assertSame(2, count($obj));
        $xmlText = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tags>
  <tag>
    <id>1</id>
    <name>defect</name>
  </tag>
  <tag>
    <id>2</id>
    <name>enhancement</name>
  </tag>
</tags>
XML;
        $this->assertXmlStringEqualsXmlString($xmlText, $obj->asXML());

        $xml = [
            'tags' => [
            ],
        ];
        $obj = Xml::fromArray($xml);
        $this->assertSame('tags', $obj->getName());
        $this->assertSame('', (string)$obj);

        $xml = [
            'tags' => [
                'bool' => true,
                'int' => 1,
                'float' => 10.2,
                'string' => 'ok',
                'null' => null,
                'array' => [],
            ],
        ];
        $obj = Xml::fromArray($xml, ['format' => 'tags']);
        $this->assertSame(6, count($obj));
        $this->assertSame((string)$obj->bool, '1');
        $this->assertSame((string)$obj->int, '1');
        $this->assertSame((string)$obj->float, '10.2');
        $this->assertSame((string)$obj->string, 'ok');
        $this->assertSame((string)$obj->null, '');
        $this->assertSame((string)$obj->array, '');

        $xml = [
            'tags' => [
                'tag' => [
                    [
                        '@id' => '1',
                        'name' => 'defect',
                    ],
                    [
                        '@id' => '2',
                        'name' => 'enhancement',
                    ],
                ],
            ],
        ];
        $obj = Xml::fromArray($xml, ['format' => 'tags']);
        $xmlText = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tags>
  <tag id="1">
    <name>defect</name>
  </tag>
  <tag id="2">
    <name>enhancement</name>
  </tag>
</tags>
XML;
        $this->assertXmlStringEqualsXmlString($xmlText, $obj->asXML());

        $xml = [
            'tags' => [
                'tag' => [
                    [
                        '@id' => '1',
                        'name' => 'defect',
                        '@' => 'Tag 1',
                    ],
                    [
                        '@id' => '2',
                        'name' => 'enhancement',
                    ],
                ],
                '@' => 'All tags',
            ],
        ];
        $obj = Xml::fromArray($xml, ['format' => 'tags']);
        $xmlText = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tags>All tags<tag id="1">Tag 1<name>defect</name></tag><tag id="2"><name>enhancement</name></tag></tags>
XML;
        $this->assertXmlStringEqualsXmlString($xmlText, $obj->asXML());

        $xml = [
            'tags' => [
                'tag' => [
                    'id' => 1,
                    '@' => 'defect',
                ],
            ],
        ];
        $obj = Xml::fromArray($xml, ['format' => 'attributes']);
        $xmlText = '<' . '?xml version="1.0" encoding="UTF-8"?><tags><tag id="1">defect</tag></tags>';
        $this->assertXmlStringEqualsXmlString($xmlText, $obj->asXML());
    }

    /**
     * Test fromArray() with zero values.
     *
     * @return void
     */
    public function testFromArrayZeroValue()
    {
        $xml = [
            'tag' => [
                '@' => 0,
                '@test' => 'A test',
            ],
        ];
        $obj = Xml::fromArray($xml);
        $xmlText = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tag test="A test">0</tag>
XML;
        $this->assertXmlStringEqualsXmlString($xmlText, $obj->asXML());

        $xml = [
            'tag' => ['0'],
        ];
        $obj = Xml::fromArray($xml);
        $xmlText = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tag>0</tag>
XML;
        $this->assertXmlStringEqualsXmlString($xmlText, $obj->asXML());
    }

    /**
     * Test non-sequential keys in list types.
     *
     * @return void
     */
    public function testFromArrayNonSequentialKeys()
    {
        $xmlArray = [
            'Event' => [
                [
                    'id' => '235',
                    'Attribute' => [
                        0 => [
                            'id' => '9646',
                        ],
                        2 => [
                            'id' => '9647',
                        ],
                    ],
                ],
            ],
        ];
        $obj = Xml::fromArray($xmlArray);
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Event>
  <id>235</id>
  <Attribute>
    <id>9646</id>
  </Attribute>
  <Attribute>
    <id>9647</id>
  </Attribute>
</Event>
XML;
        $this->assertXmlStringEqualsXmlString($expected, $obj->asXML());
    }

    /**
     * testFromArrayPretty method
     *
     * @return void
     */
    public function testFromArrayPretty()
    {
        $xml = [
            'tags' => [
                'tag' => [
                    [
                        'id' => '1',
                        'name' => 'defect',
                    ],
                    [
                        'id' => '2',
                        'name' => 'enhancement',
                    ],
                ],
            ],
        ];

        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tags><tag><id>1</id><name>defect</name></tag><tag><id>2</id><name>enhancement</name></tag></tags>

XML;
        $xmlResponse = Xml::fromArray($xml, ['pretty' => false]);
        $this->assertTextEquals($expected, $xmlResponse->asXML());

        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tags>
  <tag>
    <id>1</id>
    <name>defect</name>
  </tag>
  <tag>
    <id>2</id>
    <name>enhancement</name>
  </tag>
</tags>

XML;
        $xmlResponse = Xml::fromArray($xml, ['pretty' => true]);
        $this->assertTextEquals($expected, $xmlResponse->asXML());

                $xml = [
            'tags' => [
                'tag' => [
                    [
                        'id' => '1',
                        'name' => 'defect',
                    ],
                    [
                        'id' => '2',
                        'name' => 'enhancement',
                    ],
                ],
            ],
                ];

                $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tags><tag id="1" name="defect"/><tag id="2" name="enhancement"/></tags>

XML;
                $xmlResponse = Xml::fromArray($xml, ['pretty' => false, 'format' => 'attributes']);
                $this->assertTextEquals($expected, $xmlResponse->asXML());

                $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tags>
  <tag id="1" name="defect"/>
  <tag id="2" name="enhancement"/>
</tags>

XML;
                $xmlResponse = Xml::fromArray($xml, ['pretty' => true, 'format' => 'attributes']);
                $this->assertTextEquals($expected, $xmlResponse->asXML());
    }

    /**
     * data provider for fromArray() failures
     *
     * @return array
     */
    public static function invalidArrayDataProvider()
    {
        return [
            [''],
            [null],
            [false],
            [[]],
            [['numeric key as root']],
            [['item1' => '', 'item2' => '']],
            [['items' => ['item1', 'item2']]],
            [[
                'tags' => [
                    'tag' => [
                        [
                            [
                                'string',
                            ],
                        ],
                    ],
                ],
            ]],
            [[
                'tags' => [
                    '@tag' => [
                        [
                            '@id' => '1',
                            'name' => 'defect',
                        ],
                        [
                            '@id' => '2',
                            'name' => 'enhancement',
                        ],
                    ],
                ],
            ]],
            [new \DateTime()],
        ];
    }

    /**
     * testFromArrayFail method
     *
     * @dataProvider invalidArrayDataProvider
     * @return void
     */
    public function testFromArrayFail($value)
    {
        $this->expectException(\Exception::class);
        Xml::fromArray($value);
    }

    /**
     * Test that there are not unterminated errors when building XML
     *
     * @return void
     */
    public function testFromArrayUnterminatedError()
    {
        $data = [
            'product_ID' => 'GENERT-DL',
            'deeplink' => 'http://example.com/deep',
            'image_URL' => 'http://example.com/image',
            'thumbnail_image_URL' => 'http://example.com/thumb',
            'brand' => 'Malte Lange & Co',
            'availability' => 'in stock',
            'authors' => [
                'author' => ['Malte Lange & Co'],
            ],
        ];
        $xml = Xml::fromArray(['products' => $data], ['format' => 'tags']);
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<products>
  <product_ID>GENERT-DL</product_ID>
  <deeplink>http://example.com/deep</deeplink>
  <image_URL>http://example.com/image</image_URL>
  <thumbnail_image_URL>http://example.com/thumb</thumbnail_image_URL>
  <brand>Malte Lange &amp; Co</brand>
  <availability>in stock</availability>
  <authors>
    <author>Malte Lange &amp; Co</author>
  </authors>
</products>
XML;
        $this->assertXmlStringEqualsXmlString($expected, $xml->asXML());
    }

    /**
     * testToArray method
     *
     * @return void
     */
    public function testToArray()
    {
        $xml = '<tag>name</tag>';
        $obj = Xml::build($xml);
        $this->assertSame(['tag' => 'name'], Xml::toArray($obj));

        $xml = CORE_TESTS . 'Fixture/sample.xml';
        $obj = Xml::build($xml, ['readFile' => true]);
        $expected = [
            'tags' => [
                'tag' => [
                    [
                        '@id' => '1',
                        'name' => 'defect',
                    ],
                    [
                        '@id' => '2',
                        'name' => 'enhancement',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, Xml::toArray($obj));

        $array = [
            'tags' => [
                'tag' => [
                    [
                        'id' => '1',
                        'name' => 'defect',
                    ],
                    [
                        'id' => '2',
                        'name' => 'enhancement',
                    ],
                ],
            ],
        ];
        $this->assertSame(Xml::toArray(Xml::fromArray($array, ['format' => 'tags'])), $array);

        $expected = [
            'tags' => [
                'tag' => [
                    [
                        '@id' => '1',
                        '@name' => 'defect',
                    ],
                    [
                        '@id' => '2',
                        '@name' => 'enhancement',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, Xml::toArray(Xml::fromArray($array, ['format' => 'attributes'])));
        $this->assertSame($expected, Xml::toArray(Xml::fromArray($array, ['return' => 'domdocument', 'format' => 'attributes'])));
        $this->assertSame(Xml::toArray(Xml::fromArray($array)), $array);
        $this->assertSame(Xml::toArray(Xml::fromArray($array, ['return' => 'domdocument'])), $array);

        $array = [
            'tags' => [
                'tag' => [
                    'id' => '1',
                    'posts' => [
                        ['id' => '1'],
                        ['id' => '2'],
                    ],
                ],
                'tagOther' => [
                    'subtag' => [
                        'id' => '1',
                    ],
                ],
            ],
        ];
        $expected = [
            'tags' => [
                'tag' => [
                    '@id' => '1',
                    'posts' => [
                        ['@id' => '1'],
                        ['@id' => '2'],
                    ],
                ],
                'tagOther' => [
                    'subtag' => [
                        '@id' => '1',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, Xml::toArray(Xml::fromArray($array, ['format' => 'attributes'])));
        $this->assertSame($expected, Xml::toArray(Xml::fromArray($array, ['format' => 'attributes', 'return' => 'domdocument'])));

        $xml = <<<XML
<root>
<tag id="1">defect</tag>
</root>
XML;
        $obj = Xml::build($xml);

        $expected = [
            'root' => [
                'tag' => [
                    '@id' => '1',
                    '@' => 'defect',
                ],
            ],
        ];
        $this->assertSame($expected, Xml::toArray($obj));

        $xml = <<<XML
<root>
	<table xmlns="http://www.w3.org/TR/html4/"><tr><td>Apples</td><td>Bananas</td></tr></table>
	<table xmlns="http://www.cakephp.org"><name>CakePHP</name><license>MIT</license></table>
	<table>The book is on the table.</table>
</root>
XML;
        $obj = Xml::build($xml);

        $expected = [
            'root' => [
                'table' => [
                    ['tr' => ['td' => ['Apples', 'Bananas']]],
                    ['name' => 'CakePHP', 'license' => 'MIT'],
                    'The book is on the table.',
                ],
            ],
        ];
        $this->assertSame($expected, Xml::toArray($obj));

        $xml = <<<XML
<root xmlns:cake="http://www.cakephp.org/">
<tag>defect</tag>
<cake:bug>1</cake:bug>
</root>
XML;
        $obj = Xml::build($xml);

        $expected = [
            'root' => [
                'tag' => 'defect',
                'cake:bug' => '1',
            ],
        ];
        $this->assertSame($expected, Xml::toArray($obj));

        $xml = '<tag type="myType">0</tag>';
        $obj = Xml::build($xml);
        $expected = [
            'tag' => [
                '@type' => 'myType',
                '@' => '0',
            ],
        ];
        $this->assertSame($expected, Xml::toArray($obj));
    }

    /**
     * testRss
     *
     * @return void
     */
    public function testRss()
    {
        $rss = file_get_contents(CORE_TESTS . 'Fixture/rss.xml');
        $rssAsArray = Xml::toArray(Xml::build($rss));
        $this->assertSame('2.0', $rssAsArray['rss']['@version']);
        $this->assertCount(2, $rssAsArray['rss']['channel']['item']);

        $atomLink = ['@href' => 'http://bakery.cakephp.org/articles/rss', '@rel' => 'self', '@type' => 'application/rss+xml'];
        $this->assertSame($rssAsArray['rss']['channel']['atom:link'], $atomLink);
        $this->assertSame('http://bakery.cakephp.org/', $rssAsArray['rss']['channel']['link']);

        $expected = [
            'title' => 'Alertpay automated sales via IPN',
            'link' => 'http://bakery.cakephp.org/articles/view/alertpay-automated-sales-via-ipn',
            'description' => 'I\'m going to show you how I implemented a payment module via the Alertpay payment processor.',
            'pubDate' => 'Tue, 31 Aug 2010 01:42:00 -0500',
            'guid' => 'http://bakery.cakephp.org/articles/view/alertpay-automated-sales-via-ipn',
        ];
        $this->assertSame($expected, $rssAsArray['rss']['channel']['item'][1]);

        $rss = [
            'rss' => [
                'xmlns:atom' => 'http://www.w3.org/2005/Atom',
                '@version' => '2.0',
                'channel' => [
                    'atom:link' => [
                        '@href' => 'http://bakery.cakephp.org/articles/rss',
                        '@rel' => 'self',
                        '@type' => 'application/rss+xml',
                    ],
                    'title' => 'The Bakery: ',
                    'link' => 'http://bakery.cakephp.org/',
                    'description' => 'Recent  Articles at The Bakery.',
                    'pubDate' => 'Sun, 12 Sep 2010 04:18:26 -0500',
                    'item' => [
                        [
                            'title' => 'CakePHP 1.3.4 released',
                            'link' => 'http://bakery.cakephp.org/articles/view/cakephp-1-3-4-released',
                        ],
                        [
                            'title' => 'Wizard Component 1.2 Tutorial',
                            'link' => 'http://bakery.cakephp.org/articles/view/wizard-component-1-2-tutorial',
                        ],
                    ],
                ],
            ],
        ];
        $rssAsSimpleXML = Xml::fromArray($rss);
        $xmlText = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">
  <channel>
    <atom:link href="http://bakery.cakephp.org/articles/rss" rel="self" type="application/rss+xml"/>
    <title>The Bakery: </title>
    <link>http://bakery.cakephp.org/</link>
    <description>Recent  Articles at The Bakery.</description>
    <pubDate>Sun, 12 Sep 2010 04:18:26 -0500</pubDate>
    <item>
      <title>CakePHP 1.3.4 released</title>
      <link>http://bakery.cakephp.org/articles/view/cakephp-1-3-4-released</link>
    </item>
    <item>
      <title>Wizard Component 1.2 Tutorial</title>
      <link>http://bakery.cakephp.org/articles/view/wizard-component-1-2-tutorial</link>
    </item>
  </channel>
</rss>
XML;
        $this->assertXmlStringEqualsXmlString($xmlText, $rssAsSimpleXML->asXML());
    }

    /**
     * testXmlRpc
     *
     * @return void
     */
    public function testXmlRpc()
    {
        $xml = Xml::build('<methodCall><methodName>test</methodName><params /></methodCall>');
        $expected = [
            'methodCall' => [
                'methodName' => 'test',
                'params' => '',
            ],
        ];
        $this->assertSame($expected, Xml::toArray($xml));

        $xml = Xml::build('<methodCall><methodName>test</methodName><params><param><value><array><data><value><int>12</int></value><value><string>Egypt</string></value><value><boolean>0</boolean></value><value><int>-31</int></value></data></array></value></param></params></methodCall>');
        $expected = [
            'methodCall' => [
                'methodName' => 'test',
                'params' => [
                    'param' => [
                        'value' => [
                            'array' => [
                                'data' => [
                                    'value' => [
                                        ['int' => '12'],
                                        ['string' => 'Egypt'],
                                        ['boolean' => '0'],
                                        ['int' => '-31'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, Xml::toArray($xml));

        $xmlText = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<methodResponse>
  <params>
    <param>
      <value>
        <array>
          <data>
            <value>
              <int>1</int>
            </value>
            <value>
              <string>testing</string>
            </value>
          </data>
        </array>
      </value>
    </param>
  </params>
</methodResponse>
XML;
        $xml = Xml::build($xmlText);
        $expected = [
            'methodResponse' => [
                'params' => [
                    'param' => [
                        'value' => [
                            'array' => [
                                'data' => [
                                    'value' => [
                                        ['int' => '1'],
                                        ['string' => 'testing'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, Xml::toArray($xml));

        $xml = Xml::fromArray($expected, ['format' => 'tags']);
        $this->assertXmlStringEqualsXmlString($xmlText, $xml->asXML());
    }

    /**
     * testSoap
     *
     * @return void
     */
    public function testSoap()
    {
        $xmlRequest = Xml::build(CORE_TESTS . 'Fixture/soap_request.xml', ['readFile' => true]);
        $expected = [
            'Envelope' => [
                '@soap:encodingStyle' => 'http://www.w3.org/2001/12/soap-encoding',
                'soap:Body' => [
                    'm:GetStockPrice' => [
                        'm:StockName' => 'IBM',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, Xml::toArray($xmlRequest));

        $xmlResponse = Xml::build(CORE_TESTS . DS . 'Fixture/soap_response.xml', ['readFile' => true]);
        $expected = [
            'Envelope' => [
                '@soap:encodingStyle' => 'http://www.w3.org/2001/12/soap-encoding',
                'soap:Body' => [
                    'm:GetStockPriceResponse' => [
                        'm:Price' => '34.5',
                    ],
                ],
            ],
        ];
        $this->assertSame($expected, Xml::toArray($xmlResponse));

        $xml = [
            'soap:Envelope' => [
                'xmlns:soap' => 'http://www.w3.org/2001/12/soap-envelope',
                '@soap:encodingStyle' => 'http://www.w3.org/2001/12/soap-encoding',
                'soap:Body' => [
                    'xmlns:m' => 'http://www.example.org/stock',
                    'm:GetStockPrice' => [
                        'm:StockName' => 'IBM',
                    ],
                ],
            ],
        ];
        $xmlRequest = Xml::fromArray($xml, ['encoding' => '']);
        $xmlText = <<<XML
<?xml version="1.0"?>
<soap:Envelope xmlns:soap="http://www.w3.org/2001/12/soap-envelope" soap:encodingStyle="http://www.w3.org/2001/12/soap-encoding">
  <soap:Body xmlns:m="http://www.example.org/stock">
    <m:GetStockPrice>
      <m:StockName>IBM</m:StockName>
    </m:GetStockPrice>
  </soap:Body>
</soap:Envelope>
XML;
        $this->assertXmlStringEqualsXmlString($xmlText, $xmlRequest->asXML());
    }

    /**
     * testNamespace
     *
     * @return void
     */
    public function testNamespace()
    {
        $xml = <<<XML
<root xmlns:ns="http://cakephp.org">
	<ns:tag id="1">
		<child>good</child>
		<otherchild>bad</otherchild>
	</ns:tag>
	<tag>Tag without ns</tag>
</root>
XML;
        $xmlResponse = Xml::build($xml);
        $expected = [
            'root' => [
                'ns:tag' => [
                    '@id' => '1',
                    'child' => 'good',
                    'otherchild' => 'bad',
                ],
                'tag' => 'Tag without ns',
            ],
        ];
        $this->assertEquals($expected, Xml::toArray($xmlResponse));

        $xmlResponse = Xml::build('<root xmlns:ns="http://cakephp.org"><ns:tag id="1" /><tag><id>1</id></tag></root>');
        $expected = [
            'root' => [
                'ns:tag' => [
                    '@id' => '1',
                ],
                'tag' => [
                    'id' => '1',
                ],
            ],
        ];
        $this->assertEquals($expected, Xml::toArray($xmlResponse));

        $xmlResponse = Xml::build('<root xmlns:ns="http://cakephp.org"><ns:attr>1</ns:attr></root>');
        $expected = [
            'root' => [
                'ns:attr' => '1',
            ],
        ];
        $this->assertSame($expected, Xml::toArray($xmlResponse));

        $xmlResponse = Xml::build('<root><ns:attr xmlns:ns="http://cakephp.org">1</ns:attr></root>');
        $this->assertSame($expected, Xml::toArray($xmlResponse));

        $xml = [
            'root' => [
                'ns:attr' => [
                    'xmlns:ns' => 'http://cakephp.org',
                    '@' => 1,
                ],
            ],
        ];
        $expected = '<' . '?xml version="1.0" encoding="UTF-8"?><root><ns:attr xmlns:ns="http://cakephp.org">1</ns:attr></root>';
        $xmlResponse = Xml::fromArray($xml);
        $this->assertSame($expected, str_replace(["\r", "\n"], '', $xmlResponse->asXML()));

        $xml = [
            'root' => [
                'tag' => [
                    'xmlns:pref' => 'http://cakephp.org',
                    'pref:item' => [
                        'item 1',
                        'item 2',
                    ],
                ],
            ],
        ];
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
  <tag xmlns:pref="http://cakephp.org">
    <pref:item>item 1</pref:item>
    <pref:item>item 2</pref:item>
  </tag>
</root>
XML;
        $xmlResponse = Xml::fromArray($xml);
        $this->assertXmlStringEqualsXmlString($expected, $xmlResponse->asXML());

        $xml = [
            'root' => [
                'tag' => [
                    'xmlns:' => 'http://cakephp.org',
                ],
            ],
        ];
        $expected = '<' . '?xml version="1.0" encoding="UTF-8"?><root><tag xmlns="http://cakephp.org"/></root>';
        $xmlResponse = Xml::fromArray($xml);
        $this->assertXmlStringEqualsXmlString($expected, $xmlResponse->asXML());

        $xml = [
            'root' => [
                'xmlns:' => 'http://cakephp.org',
            ],
        ];
        $expected = '<' . '?xml version="1.0" encoding="UTF-8"?><root xmlns="http://cakephp.org"/>';
        $xmlResponse = Xml::fromArray($xml);
        $this->assertXmlStringEqualsXmlString($expected, $xmlResponse->asXML());

        $xml = [
            'root' => [
                'xmlns:ns' => 'http://cakephp.org',
            ],
        ];
        $expected = '<' . '?xml version="1.0" encoding="UTF-8"?><root xmlns:ns="http://cakephp.org"/>';
        $xmlResponse = Xml::fromArray($xml);
        $this->assertXmlStringEqualsXmlString($expected, $xmlResponse->asXML());
    }

    /**
     * test that CDATA blocks don't get screwed up by SimpleXml
     *
     * @return void
     */
    public function testCdata()
    {
        $xml = '<' . '?xml version="1.0" encoding="UTF-8"?>' .
            '<people><name><![CDATA[ Mark ]]></name></people>';

        $result = Xml::build($xml);
        $this->assertSame(' Mark ', (string)$result->name);
    }

    /**
     * data provider for toArray() failures
     *
     * @return array
     */
    public static function invalidToArrayDataProvider()
    {
        return [
            [new \DateTime()],
            [[]],
        ];
    }

    /**
     * testToArrayFail method
     *
     * @dataProvider invalidToArrayDataProvider
     * @return void
     */
    public function testToArrayFail($value)
    {
        $this->expectException(\Cake\Utility\Exception\XmlException::class);
        Xml::toArray($value);
    }

    /**
     * Test ampersand in text elements.
     *
     * @return void
     */
    public function testAmpInText()
    {
        $data = [
            'outer' => [
                'inner' => ['name' => 'mark & mark'],
            ],
        ];
        $obj = Xml::build($data);
        $result = $obj->asXml();
        $this->assertStringContainsString('mark &amp; mark', $result);
    }

    /**
     * Test that entity loading is disabled by default.
     *
     * @return void
     */
    public function testNoEntityLoading()
    {
        $file = str_replace(' ', '%20', CAKE . 'VERSION.txt');
        $xml = <<<XML
<!DOCTYPE cakephp [
  <!ENTITY payload SYSTEM "file://$file" >]>
<request>
  <xxe>&payload;</xxe>
</request>
XML;
        $result = Xml::build($xml);
        $this->assertSame('', (string)$result->xxe);
    }

    /**
     * Test building Xml with valid class-name in value.
     *
     * @see https://github.com/cakephp/cakephp/pull/9754
     * @return void
     */
    public function testClassnameInValueRegressionTest()
    {
        $classname = self::class; // Will always be a valid class name
        $data = [
            'outer' => [
                'inner' => $classname,
            ],
        ];
        $obj = Xml::build($data);
        $result = $obj->asXml();
        $this->assertStringContainsString('<inner>' . $classname . '</inner>', $result);
    }

    /**
     * Needed function for testClassnameInValueRegressionTest.
     *
     * @ignore
     * @return array
     */
    public function toArray()
    {
        return [];
    }
}
