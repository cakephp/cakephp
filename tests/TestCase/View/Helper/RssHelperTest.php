<?php
/**
 * RssHelperTest file
 *
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
namespace Cake\Test\TestCase\View\Helper;

use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\RssHelper;
use Cake\View\View;

/**
 * RssHelperTest class
 */
class RssHelperTest extends TestCase
{

    /**
     * @var \Cake\View\Helper\RssHelper
     */
    public $Rss;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->View = new View();
        $this->Rss = new RssHelper($this->View);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Rss);
    }

    /**
     * testDocument method
     *
     * @return void
     */
    public function testDocument()
    {
        $result = $this->Rss->document();
        $expected = [
            'rss' => [
                'version' => '2.0'
            ]
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Rss->document(null, 'content');
        $expected = [
            'rss' => [
                'version' => '2.0'
            ],
            'content'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Rss->document(['contrived' => 'parameter'], 'content');
        $expected = [
            'rss' => [
                'contrived' => 'parameter',
                'version' => '2.0'
            ],
            'content'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testChannel method
     *
     * @return void
     */
    public function testChannel()
    {
        $attrib = ['a' => '1', 'b' => '2'];
        $elements = ['title' => 'Title'];
        $content = 'content';

        $result = $this->Rss->channel($attrib, $elements, $content);
        $expected = [
            'channel' => [
                'a' => '1',
                'b' => '2'
            ],
            '<title',
            'Title',
            '/title',
            '<link',
            $this->Rss->Url->build('/', true),
            '/link',
            '<description',
            'content',
            '/channel'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test correct creation of channel sub elements.
     *
     * @return void
     */
    public function testChannelElements()
    {
        $attrib = [];
        $elements = [
            'title' => 'Title of RSS Feed',
            'link' => 'http://example.com',
            'description' => 'Description of RSS Feed',
            'image' => [
                'title' => 'Title of image',
                'url' => 'http://example.com/example.png',
                'link' => 'http://example.com'
            ],
            'cloud' => [
                'domain' => 'rpc.sys.com',
                'port' => '80',
                'path' => '/RPC2',
                'registerProcedure' => 'myCloud.rssPleaseNotify',
                'protocol' => 'xml-rpc'
            ]
        ];
        $content = 'content-here';
        $result = $this->Rss->channel($attrib, $elements, $content);
        //@codingStandardsIgnoreStart
        $expected = [
            '<channel',
                '<title', 'Title of RSS Feed', '/title',
                '<link', 'http://example.com', '/link',
                '<description', 'Description of RSS Feed', '/description',
                '<image',
                    '<title', 'Title of image', '/title',
                    '<url', 'http://example.com/example.png', '/url',
                    '<link', 'http://example.com', '/link',
                '/image',
                'cloud' => [
                    'domain' => 'rpc.sys.com',
                    'port' => '80',
                    'path' => '/RPC2',
                    'registerProcedure' => 'myCloud.rssPleaseNotify',
                    'protocol' => 'xml-rpc'
                ],
            'content-here',
            '/channel',
        ];
        //@codingStandardsIgnoreEnd
        $this->assertHtml($expected, $result);
    }

    public function testChannelElementAttributes()
    {
        $attrib = [];
        $elements = [
            'title' => 'Title of RSS Feed',
            'link' => 'http://example.com',
            'description' => 'Description of RSS Feed',
            'image' => [
                'title' => 'Title of image',
                'url' => 'http://example.com/example.png',
                'link' => 'http://example.com'
            ],
            'atom:link' => [
                'attrib' => [
                    'href' => 'http://www.example.com/rss.xml',
                    'rel' => 'self',
                    'type' => 'application/rss+xml']
            ]
        ];
        $content = 'content-here';
        $result = $this->Rss->channel($attrib, $elements, $content);
        //@codingStandardsIgnoreStart
        $expected = [
            '<channel',
                '<title', 'Title of RSS Feed', '/title',
                '<link', 'http://example.com', '/link',
                '<description', 'Description of RSS Feed', '/description',
                '<image',
                    '<title', 'Title of image', '/title',
                    '<url', 'http://example.com/example.png', '/url',
                    '<link', 'http://example.com', '/link',
                '/image',
                'atom:link' => [
                    'xmlns:atom' => 'http://www.w3.org/2005/Atom',
                    'href' => 'http://www.example.com/rss.xml',
                    'rel' => 'self',
                    'type' => 'application/rss+xml'
                ],
            'content-here',
            '/channel',
        ];
        //@codingStandardsIgnoreEnd
        $this->assertHtml($expected, $result);
    }

    /**
     * testItems method
     *
     * @return void
     */
    public function testItems()
    {
        $items = [
            ['title' => 'title1', 'guid' => 'http://www.example.com/guid1', 'link' => 'http://www.example.com/link1', 'description' => 'description1'],
            ['title' => 'title2', 'guid' => 'http://www.example.com/guid2', 'link' => 'http://www.example.com/link2', 'description' => 'description2'],
            ['title' => 'title3', 'guid' => 'http://www.example.com/guid3', 'link' => 'http://www.example.com/link3', 'description' => 'description3']
        ];

        $result = $this->Rss->items($items);
        $expected = [
            '<item',
                '<title', 'title1', '/title',
                '<guid', 'http://www.example.com/guid1', '/guid',
                '<link', 'http://www.example.com/link1', '/link',
                '<description', 'description1', '/description',
            '/item',
            '<item',
                '<title', 'title2', '/title',
                '<guid', 'http://www.example.com/guid2', '/guid',
                '<link', 'http://www.example.com/link2', '/link',
                '<description', 'description2', '/description',
            '/item',
            '<item',
                '<title', 'title3', '/title',
                '<guid', 'http://www.example.com/guid3', '/guid',
                '<link', 'http://www.example.com/link3', '/link',
                '<description', 'description3', '/description',
            '/item'
        ];
        $this->assertHtml($expected, $result);

        $items = [
            ['title' => 'title1', 'guid' => 'http://www.example.com/guid1', 'link' => 'http://www.example.com/link1', 'description' => 'description1'],
            ['title' => 'title2', 'guid' => 'http://www.example.com/guid2', 'link' => 'http://www.example.com/link2', 'description' => 'description2'],
            ['title' => 'title3', 'guid' => 'http://www.example.com/guid3', 'link' => 'http://www.example.com/link3', 'description' => 'description3']
        ];

        $result = $this->Rss->items($items, function ($v) {
            $v['title'] = $v['title'] . '-transformed';

            return $v;
        });
        $expected = [
            '<item',
                '<title', 'title1-transformed', '/title',
                '<guid', 'http://www.example.com/guid1', '/guid',
                '<link', 'http://www.example.com/link1', '/link',
                '<description', 'description1', '/description',
            '/item',
            '<item',
                '<title', 'title2-transformed', '/title',
                '<guid', 'http://www.example.com/guid2', '/guid',
                '<link', 'http://www.example.com/link2', '/link',
                '<description', 'description2', '/description',
            '/item',
            '<item',
                '<title', 'title3-transformed', '/title',
                '<guid', 'http://www.example.com/guid3', '/guid',
                '<link', 'http://www.example.com/link3', '/link',
                '<description', 'description3', '/description',
            '/item'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Rss->items([]);
        $expected = '';
        $this->assertEquals($expected, $result);
    }

    /**
     * testItem method
     *
     * @return void
     */
    public function testItem()
    {
        $item = [
            'title' => 'My title',
            'description' => 'My description',
            'link' => 'http://www.google.com/'
        ];
        $result = $this->Rss->item(null, $item);
        $expected = [
            '<item',
            '<title',
            'My title',
            '/title',
            '<description',
            'My description',
            '/description',
            '<link',
            'http://www.google.com/',
            '/link',
            '<guid',
            'http://www.google.com/',
            '/guid',
            '/item'
        ];
        $this->assertHtml($expected, $result);

        $item = [
            'title' => 'My Title',
            'link' => 'http://www.example.com/1',
            'description' => 'descriptive words',
            'pubDate' => '2008-05-31 12:00:00',
            'source' => ['http://www.google.com/', 'Google'],
            'guid' => 'http://www.example.com/1'
        ];
        $result = $this->Rss->item(null, $item);

        $expected = [
            '<item',
            '<title',
            'My Title',
            '/title',
            '<link',
            'http://www.example.com/1',
            '/link',
            '<description',
            'descriptive words',
            '/description',
            '<pubDate',
            date('r', strtotime('2008-05-31 12:00:00')),
            '/pubDate',
            'source' => ['url' => 'http://www.google.com/'],
            'Google',
            '/source',
            '<guid',
            'http://www.example.com/1',
            '/guid',
            '/item'
        ];
        $this->assertHtml($expected, $result);

        $item = [
            'title' => 'My Title & more'
        ];
        $result = $this->Rss->item(null, $item);
        $expected = [
            '<item',
            '<title', 'My Title &amp; more', '/title',
            '/item'
        ];
        $this->assertHtml($expected, $result);

        $item = [
            'title' => 'Foo bar',
            'link' => [
                'url' => 'http://example.com/foo?a=1&b=2',
                'convertEntities' => false
            ],
            'description' => [
                'value' => 'descriptive words',
                'cdata' => true,
            ],
            'pubDate' => '2008-05-31 12:00:00',
            'source' => 'http://www.google.com/'
        ];
        $result = $this->Rss->item(null, $item);
        $expected = [
            '<item',
            '<title',
            'Foo bar',
            '/title',
            '<link',
            'http://example.com/foo?a=1&amp;b=2',
            '/link',
            '<description',
            '<![CDATA[descriptive words]]',
            '/description',
            '<pubDate',
            date('r', strtotime('2008-05-31 12:00:00')),
            '/pubDate',
            '<source',
            'http://www.google.com/',
            '/source',
            '<guid',
            'http://example.com/foo?a=1&amp;b=2',
            '/guid',
            '/item'
        ];
        $this->assertHtml($expected, $result);

        $item = [
            'title' => 'My title',
            'description' => 'My description',
            'link' => 'http://www.google.com/',
            'source' => ['url' => 'http://www.example.com/', 'title' => 'Example website']
        ];
        $result = $this->Rss->item(null, $item);
        $expected = [
            '<item',
            '<title',
            'My title',
            '/title',
            '<description',
            'My description',
            '/description',
            '<link',
            'http://www.google.com/',
            '/link',
            'source' => ['url' => 'http://www.example.com/'],
            'Example website',
            '/source',
            '<guid',
            'http://www.google.com/',
            '/guid',
            '/item'
        ];
        $this->assertHtml($expected, $result);

        $item = [
            'title' => 'My title',
            'description' => 'My description',
            'link' => 'http://www.google.com/',
            'category' => ['Category One', 'Category Two']
        ];
        $result = $this->Rss->item(null, $item);
        $expected = [
            '<item',
            '<title',
            'My title',
            '/title',
            '<description',
            'My description',
            '/description',
            '<link',
            'http://www.google.com/',
            '/link',
            '<category',
            'Category One',
            '/category',
            '<category',
            'Category Two',
            '/category',
            '<guid',
            'http://www.google.com/',
            '/guid',
            '/item'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test item() with cdata blocks.
     *
     * @return void
     */
    public function testItemCdata()
    {
        $item = [
            'title' => [
                'value' => 'My Title & more',
                'cdata' => true,
                'convertEntities' => false,
            ]
        ];
        $result = $this->Rss->item(null, $item);
        $expected = [
            '<item',
            '<title',
            '<![CDATA[My Title & more]]',
            '/title',
            '/item'
        ];
        $this->assertHtml($expected, $result);

        $item = [
            'category' => [
                'value' => 'CakePHP',
                'cdata' => true,
                'domain' => 'http://www.cakephp.org',
            ]
        ];
        $result = $this->Rss->item(null, $item);
        $expected = [
            '<item',
            'category' => ['domain' => 'http://www.cakephp.org'],
            '<![CDATA[CakePHP]]',
            '/category',
            '/item'
        ];
        $this->assertHtml($expected, $result);

        $item = [
            'category' => [
                [
                    'value' => 'CakePHP',
                    'cdata' => true,
                    'domain' => 'http://www.cakephp.org'
                ],
                [
                    'value' => 'Bakery',
                    'cdata' => true
                ]
            ]
        ];
        $result = $this->Rss->item(null, $item);
        $expected = [
            '<item',
            'category' => ['domain' => 'http://www.cakephp.org'],
            '<![CDATA[CakePHP]]',
            '/category',
            '<category',
            '<![CDATA[Bakery]]',
            '/category',
            '/item'
        ];
        $this->assertHtml($expected, $result);

        $item = [
            'title' => [
                'value' => 'My Title',
                'cdata' => true,
            ],
            'link' => 'http://www.example.com/1',
            'description' => [
                'value' => 'descriptive words',
                'cdata' => true,
            ],
            'enclosure' => [
                'url' => '/test.flv'
            ],
            'pubDate' => '2008-05-31 12:00:00',
            'guid' => 'http://www.example.com/1',
            'category' => [
                [
                    'value' => 'CakePHP',
                    'cdata' => true,
                    'domain' => 'http://www.cakephp.org'
                ],
                [
                    'value' => 'Bakery',
                    'cdata' => true
                ]
            ]
        ];
        $result = $this->Rss->item(null, $item);
        $expected = [
            '<item',
            '<title',
            '<![CDATA[My Title]]',
            '/title',
            '<link',
            'http://www.example.com/1',
            '/link',
            '<description',
            '<![CDATA[descriptive words]]',
            '/description',
            'enclosure' => ['url' => $this->Rss->Url->build('/test.flv', true)],
            '<pubDate',
            date('r', strtotime('2008-05-31 12:00:00')),
            '/pubDate',
            '<guid',
            'http://www.example.com/1',
            '/guid',
            'category' => ['domain' => 'http://www.cakephp.org'],
            '<![CDATA[CakePHP]]',
            '/category',
            '<category',
            '<![CDATA[Bakery]]',
            '/category',
            '/item'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test item() with enclosure data.
     *
     * @return void
     */
    public function testItemEnclosureLength()
    {
        if (!is_writable(WWW_ROOT)) {
            $this->markTestSkipped('Webroot is not writable.');
        }
        $testExists = is_dir(WWW_ROOT . 'tests');

        $tmpFile = WWW_ROOT . 'tests/cakephp.file.test.tmp';
        $File = new File($tmpFile, true);

        $this->assertTrue($File->write('1234'), 'Could not write to ' . $tmpFile);

        clearstatcache();

        $item = [
            'title' => [
                'value' => 'My Title',
                'cdata' => true,
            ],
            'link' => 'http://www.example.com/1',
            'description' => [
                'value' => 'descriptive words',
                'cdata' => true,
            ],
            'enclosure' => [
                'url' => '/tests/cakephp.file.test.tmp'
            ],
            'pubDate' => '2008-05-31 12:00:00',
            'guid' => 'http://www.example.com/1',
            'category' => [
                [
                    'value' => 'CakePHP',
                    'cdata' => true,
                    'domain' => 'http://www.cakephp.org'
                ],
                [
                    'value' => 'Bakery',
                    'cdata' => true
                ]
            ]
        ];
        $result = $this->Rss->item(null, $item);
        if (!function_exists('mime_content_type')) {
            $type = null;
        } else {
            $type = mime_content_type($tmpFile);
        }

        $expected = [
            '<item',
            '<title',
            '<![CDATA[My Title]]',
            '/title',
            '<link',
            'http://www.example.com/1',
            '/link',
            '<description',
            '<![CDATA[descriptive words]]',
            '/description',
            'enclosure' => [
                'url' => $this->Rss->Url->build('/tests/cakephp.file.test.tmp', true),
                'length' => filesize($tmpFile),
                'type' => $type
            ],
            '<pubDate',
            date('r', strtotime('2008-05-31 12:00:00')),
            '/pubDate',
            '<guid',
            'http://www.example.com/1',
            '/guid',
            'category' => ['domain' => 'http://www.cakephp.org'],
            '<![CDATA[CakePHP]]',
            '/category',
            '<category',
            '<![CDATA[Bakery]]',
            '/category',
            '/item'
        ];
        if ($type === null) {
            unset($expected['enclosure']['type']);
        }
        $this->assertHtml($expected, $result);

        $File->delete();

        if (!$testExists) {
            $Folder = new Folder(WWW_ROOT . 'tests');
            $Folder->delete();
        }
    }

    /**
     * testElementAttrNotInParent method
     *
     * @return void
     */
    public function testElementAttrNotInParent()
    {
        $attributes = [
            'title' => 'Some Title',
            'link' => 'http://link.com',
            'description' => 'description'
        ];
        $elements = ['enclosure' => ['url' => 'http://test.com']];

        $result = $this->Rss->item($attributes, $elements);
        $expected = [
            'item' => [
                'title' => 'Some Title',
                'link' => 'http://link.com',
                'description' => 'description'
            ],
            'enclosure' => [
                'url' => 'http://test.com'
            ],
            '/item'
        ];
        $this->assertHtml($expected, $result);
    }

    public function testElementNamespaceWithoutPrefix()
    {
        $item = [
                'creator' => 'Alex',
            ];
        $attributes = [
                'namespace' => 'http://link.com'
        ];
        $result = $this->Rss->item($attributes, $item);
        $expected = [
            'item' => [
                    'xmlns' => 'http://link.com'
            ],
            'creator' => [
                    'xmlns' => 'http://link.com'
            ],
            'Alex',
            '/creator',
            '/item'
        ];
        $this->assertHtml($expected, $result, true);
    }

    public function testElementNamespaceWithPrefix()
    {
        $item = [
            'title' => 'Title',
            'dc:creator' => 'Alex',
            'dc:description' => 'descriptive words'
        ];
        $attributes = [
            'namespace' => [
                'prefix' => 'dc',
                'url' => 'http://link.com'
            ]
        ];
        $result = $this->Rss->item($attributes, $item);
        $expected = [
            'item' => [
                'xmlns:dc' => 'http://link.com'
            ],
            'title' => [
                'xmlns:dc' => 'http://link.com'
            ],
            'Title',
            '/title',
            'dc:creator' => [
                'xmlns:dc' => 'http://link.com'
            ],
            'Alex',
            '/dc:creator',
            'dc:description' => [
                'xmlns:dc' => 'http://link.com'
            ],
            'descriptive words',
            '/dc:description',
            '/item'
        ];
        $this->assertHtml($expected, $result, true);
    }
}
