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
namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\TextHelper;
use Cake\View\View;
use TestApp\Utility\TestAppEngine;
use TestApp\Utility\TextMock;
use TestApp\View\Helper\TextHelperTestObject;
use TestPlugin\Utility\TestPluginEngine;

/**
 * TextHelperTest class
 */
class TextHelperTest extends TestCase
{
    /**
     * @var \Cake\View\Helper\TextHelper
     */
    protected $Text;

    /**
     * @var \Cake\View\View
     */
    protected $View;

    /**
     * @var string
     */
    protected $appNamespace;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->View = new View();
        $this->Text = new TextHelper($this->View);

        $this->appNamespace = Configure::read('App.namespace');
        static::setAppNamespace();
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        unset($this->Text, $this->View);
        static::setAppNamespace($this->appNamespace);
        parent::tearDown();
    }

    /**
     * test String class methods are called correctly
     */
    public function testTextHelperProxyMethodCalls(): void
    {
        $methods = [
            'stripLinks', 'toList',
        ];
        $String = $this->getMockBuilder(TextMock::class)
            ->addMethods($methods)
            ->getMock();
        $Text = new TextHelperTestObject($this->View, ['engine' => TextMock::class]);
        $Text->attach($String);
        foreach ($methods as $method) {
            $String->expects($this->once())->method($method)->willReturn('');
            $Text->{$method}(['who'], 'what', 'when', 'where', 'how');
        }

        $methods = [
            'excerpt',
        ];
        $String = $this->getMockBuilder(TextMock::class)
            ->addMethods($methods)
            ->getMock();
        $Text = new TextHelperTestObject($this->View, ['engine' => TextMock::class]);
        $Text->attach($String);
        foreach ($methods as $method) {
            $String->expects($this->once())->method($method)->willReturn('');
            $Text->{$method}('who', 'what');
        }

        $methods = [
            'highlight',
        ];
        $String = $this->getMockBuilder(TextMock::class)
            ->addMethods($methods)
            ->getMock();
        $Text = new TextHelperTestObject($this->View, ['engine' => TextMock::class]);
        $Text->attach($String);
        foreach ($methods as $method) {
            $String->expects($this->once())->method($method)->willReturn('');
            $Text->{$method}('who', 'what');
        }

        $methods = [
            'tail', 'truncate',
        ];
        $String = $this->getMockBuilder(TextMock::class)
            ->addMethods($methods)
            ->getMock();
        $Text = new TextHelperTestObject($this->View, ['engine' => TextMock::class]);
        $Text->attach($String);
        foreach ($methods as $method) {
            $String->expects($this->once())->method($method)->willReturn('');
            $Text->{$method}('who', 1, ['what']);
        }
    }

    /**
     * test engine override
     */
    public function testEngineOverride(): void
    {
        $Text = new TextHelperTestObject($this->View, ['engine' => 'TestAppEngine']);
        $this->assertInstanceOf(TestAppEngine::class, $Text->engine());

        $this->loadPlugins(['TestPlugin']);
        $Text = new TextHelperTestObject($this->View, ['engine' => 'TestPlugin.TestPluginEngine']);
        $this->assertInstanceOf(TestPluginEngine::class, $Text->engine());
        $this->clearPlugins();
    }

    /**
     * testAutoLink method
     */
    public function testAutoLink(): void
    {
        $text = 'The AWWWARD show happened today';
        $result = $this->Text->autoLink($text);
        $this->assertSame($text, $result);

        $text = 'This is a test text';
        $expected = 'This is a test text';
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);

        $text = 'Text with a partial www.cakephp.org URL and test@cakephp.org email address';
        $result = $this->Text->autoLink($text);
        $expected = 'Text with a partial <a href="http://www.cakephp.org">www.cakephp.org</a> URL and <a href="mailto:test@cakephp\.org">test@cakephp\.org</a> email address';
        $this->assertMatchesRegularExpression('#^' . $expected . '$#', $result);

        $text = 'This is a test text with URL http://www.cakephp.org';
        $expected = 'This is a test text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>';
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);

        $text = 'This is a test text with URL http://www.cakephp.org and some more text';
        $expected = 'This is a test text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a> and some more text';
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);

        $text = "This is a test text with URL http://www.cakephp.org\tand some more text";
        $expected = "This is a test text with URL <a href=\"http://www.cakephp.org\">http://www.cakephp.org</a>\tand some more text";
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);

        $text = 'This is a test text with URL http://www.cakephp.org(and some more text)';
        $expected = 'This is a test text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>(and some more text)';
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);

        $text = 'This is a test text with URL (http://www.cakephp.org/page/4) in brackets';
        $expected = 'This is a test text with URL (<a href="http://www.cakephp.org/page/4">http://www.cakephp.org/page/4</a>) in brackets';
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);

        $text = 'This is a test text with URL [http://www.cakephp.org/page/4] in square brackets';
        $expected = 'This is a test text with URL [<a href="http://www.cakephp.org/page/4">http://www.cakephp.org/page/4</a>] in square brackets';
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);

        $text = 'This is a test text with URL [http://www.example.com?aParam[]=value1&aParam[]=value2&aParam[]=value3] in square brackets';
        $expected = 'This is a test text with URL [<a href="http://www.example.com?aParam[]=value1&amp;aParam[]=value2&amp;aParam[]=value3">http://www.example.com?aParam[]=value1&amp;aParam[]=value2&amp;aParam[]=value3</a>] in square brackets';
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);

        $text = 'This is a test text with URL ;http://www.cakephp.org/page/4; semi-colon';
        $expected = 'This is a test text with URL ;<a href="http://www.cakephp.org/page/4">http://www.cakephp.org/page/4</a>; semi-colon';
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);

        $text = 'This is a test text with URL (http://www.cakephp.org/page/4/other(thing)) brackets';
        $expected = 'This is a test text with URL (<a href="http://www.cakephp.org/page/4/other(thing)">http://www.cakephp.org/page/4/other(thing)</a>) brackets';
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);
    }

    /**
     * Test mixing URLs and Email addresses in one confusing string.
     */
    public function testAutoLinkMixed(): void
    {
        $text = 'Text with a url/email http://example.com/store?email=mark@example.com and email.';
        $expected = 'Text with a url/email <a href="http://example.com/store?email=mark@example.com">' .
            'http://example.com/store?email=mark@example.com</a> and email.';
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);
    }

    /**
     * test autoLink() and options.
     */
    public function testAutoLinkOptions(): void
    {
        $text = 'This is a test text with URL http://www.cakephp.org';
        $expected = 'This is a test text with URL <a href="http://www.cakephp.org" class="link">http://www.cakephp.org</a>';
        $result = $this->Text->autoLink($text, ['class' => 'link']);
        $this->assertSame($expected, $result);

        $text = 'This is a test text with URL http://www.cakephp.org';
        $expected = 'This is a test text with URL <a href="http://www.cakephp.org" class="link" id="MyLink">http://www.cakephp.org</a>';
        $result = $this->Text->autoLink($text, ['class' => 'link', 'id' => 'MyLink']);
        $this->assertSame($expected, $result);
    }

    /**
     * Test escaping for autoLink
     */
    public function testAutoLinkEscape(): void
    {
        $text = 'This is a <b>test</b> text with URL http://www.cakephp.org';
        $expected = 'This is a &lt;b&gt;test&lt;/b&gt; text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>';
        $result = $this->Text->autoLink($text);
        $this->assertSame($expected, $result);

        $text = 'This is a <b>test</b> text with URL http://www.cakephp.org';
        $expected = 'This is a <b>test</b> text with URL <a href="http://www.cakephp.org">http://www.cakephp.org</a>';
        $result = $this->Text->autoLink($text, ['escape' => false]);
        $this->assertSame($expected, $result);

        $text = 'test <ul>
		<li>lorem: http://example.org?some</li>
		<li>ipsum: http://othersite.com/abc</li>
		</ul> test';
        $expected = 'test <ul>
		<li>lorem: <a href="http://example.org?some">http://example.org?some</a></li>
		<li>ipsum: <a href="http://othersite.com/abc">http://othersite.com/abc</a></li>
		</ul> test';
        $result = $this->Text->autoLink($text, ['escape' => false]);
        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for autoLinking
     *
     * @return array
     */
    public static function autoLinkProvider(): array
    {
        return [
            [
                'This is a test text',
                'This is a test text',
            ],
            [
                'This is a test that includes (www.cakephp.org)',
                'This is a test that includes (<a href="http://www.cakephp.org">www.cakephp.org</a>)',
            ],
            [
                'This is a test that includes www.cakephp.org:8080',
                'This is a test that includes <a href="http://www.cakephp.org:8080">www.cakephp.org:8080</a>',
            ],
            [
                'This is a test that includes http://de.wikipedia.org/wiki/Kanton_(Schweiz)#fragment',
                'This is a test that includes <a href="http://de.wikipedia.org/wiki/Kanton_(Schweiz)#fragment">http://de.wikipedia.org/wiki/Kanton_(Schweiz)#fragment</a>',
            ],
            [
                'This is a test that includes www.wikipedia.org/wiki/Kanton_(Schweiz)#fragment',
                'This is a test that includes <a href="http://www.wikipedia.org/wiki/Kanton_(Schweiz)#fragment">www.wikipedia.org/wiki/Kanton_(Schweiz)#fragment</a>',
            ],
            [
                'This is a test that includes Http://example.com/test.php?foo=bar text',
                'This is a test that includes <a href="Http://example.com/test.php?foo=bar">Http://example.com/test.php?foo=bar</a> text',
            ],
            [
                'This is a test that includes http://example.com/test.php?foo=bar text',
                'This is a test that includes <a href="http://example.com/test.php?foo=bar">http://example.com/test.php?foo=bar</a> text',
            ],
            [
                'This is a test that includes www.example.com/test.php?foo=bar text',
                'This is a test that includes <a href="http://www.example.com/test.php?foo=bar">www.example.com/test.php?foo=bar</a> text',
            ],
            [
                'Text with a partial www.cakephp.org URL',
                'Text with a partial <a href="http://www.cakephp.org">www.cakephp.org</a> URL',
            ],
            [
                'Text with a partial WWW.cakephp.org URL',
                'Text with a partial <a href="http://WWW.cakephp.org">WWW.cakephp.org</a> URL',
            ],
            [
                'Text with a partial WWW.cakephp.org &copy, URL',
                'Text with a partial <a href="http://WWW.cakephp.org">WWW.cakephp.org</a> &amp;copy, URL',
            ],
            [
                'Text with a url www.cot.ag/cuIb2Q and more',
                'Text with a url <a href="http://www.cot.ag/cuIb2Q">www.cot.ag/cuIb2Q</a> and more',
            ],
            [
                'Text with a url http://www.does--not--work.com and more',
                'Text with a url <a href="http://www.does--not--work.com">http://www.does--not--work.com</a> and more',
            ],
            [
                'Text with a url http://www.not--work.com and more',
                'Text with a url <a href="http://www.not--work.com">http://www.not--work.com</a> and more',
            ],
            [
                'Text with a url http://www.sub_domain.domain.pl and more',
                'Text with a url <a href="http://www.sub_domain.domain.pl">http://www.sub_domain.domain.pl</a> and more',
            ],
            [
                'Text with a partial www.küchenschöhn-not-working.de URL',
                'Text with a partial <a href="http://www.küchenschöhn-not-working.de">www.küchenschöhn-not-working.de</a> URL',
            ],
            [
                'Text with a partial http://www.küchenschöhn-not-working.de URL',
                'Text with a partial <a href="http://www.küchenschöhn-not-working.de">http://www.küchenschöhn-not-working.de</a> URL',
            ],
            [
                "Text with partial www.cakephp.org\r\nwww.cakephp.org urls and CRLF",
                "Text with partial <a href=\"http://www.cakephp.org\">www.cakephp.org</a>\r\n<a href=\"http://www.cakephp.org\">www.cakephp.org</a> urls and CRLF",
            ],
            [
                'https://nl.wikipedia.org/wiki/Exploit_(computerbeveiliging)',
                '<a href="https://nl.wikipedia.org/wiki/Exploit_(computerbeveiliging)">https://nl.wikipedia.org/wiki/Exploit_(computerbeveiliging)</a>',
            ],
            [
                'http://dev.local/threads/search?search_string=this+is+a+test',
                '<a href="http://dev.local/threads/search?search_string=this+is+a+test">http://dev.local/threads/search?search_string=this+is+a+test</a>',
            ],
            [
                'http://www.ad.nl/show/giel-beelen-heeft-weinig-moeite-met-rijontzegging~acd8b6ed',
                '<a href="http://www.ad.nl/show/giel-beelen-heeft-weinig-moeite-met-rijontzegging~acd8b6ed">http://www.ad.nl/show/giel-beelen-heeft-weinig-moeite-met-rijontzegging~acd8b6ed</a>',
            ],
            [
                'https://sevvlor.com/page%20not%20found',
                '<a href="https://sevvlor.com/page%20not%20found">https://sevvlor.com/page%20not%20found</a>',
            ],
            [
                'https://fakedomain.ext/path/#!topic/test',
                '<a href="https://fakedomain.ext/path/#!topic/test">https://fakedomain.ext/path/#!topic/test</a>',
            ],
            [
                'https://fakedomain.ext/path/#!topic/test;other;tag',
                '<a href="https://fakedomain.ext/path/#!topic/test;other;tag">https://fakedomain.ext/path/#!topic/test;other;tag</a>',
            ],
            [
                'This is text,https://fakedomain.ext/path/#!topic/test,tag, with a comma',
                'This is text,<a href="https://fakedomain.ext/path/#!topic/test,tag">https://fakedomain.ext/path/#!topic/test,tag</a>, with a comma',
            ],
            [
                'This is text https://fakedomain.ext/path/#!topic/path!',
                'This is text <a href="https://fakedomain.ext/path/#!topic/path">https://fakedomain.ext/path/#!topic/path</a>!',
            ],
        ];
    }

    /**
     * testAutoLinkUrls method
     *
     * @dataProvider autoLinkProvider
     */
    public function testAutoLinkUrls(string $text, string $expected): void
    {
        $result = $this->Text->autoLinkUrls($text);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the options for autoLinkUrls
     */
    public function testAutoLinkUrlsOptions(): void
    {
        $text = 'Text with a partial www.cakephp.org URL';
        $expected = 'Text with a partial <a href="http://www.cakephp.org" \s*class="link">www.cakephp.org</a> URL';
        $result = $this->Text->autoLinkUrls($text, ['class' => 'link']);
        $this->assertMatchesRegularExpression('#^' . $expected . '$#', $result);

        $text = 'Text with a partial WWW.cakephp.org &copy; URL';
        $expected = 'Text with a partial <a href="http://WWW.cakephp.org"\s*>WWW.cakephp.org</a> &copy; URL';
        $result = $this->Text->autoLinkUrls($text, ['escape' => false]);
        $this->assertMatchesRegularExpression('#^' . $expected . '$#', $result);
    }

    /**
     * Test autoLinkUrls with the escape option.
     */
    public function testAutoLinkUrlsEscape(): void
    {
        $text = 'Text with a partial <a href="http://www.example.com">http://www.example.com</a> link';
        $expected = 'Text with a partial <a href="http://www.example.com">http://www.example.com</a> link';
        $result = $this->Text->autoLinkUrls($text, ['escape' => false]);
        $this->assertSame($expected, $result);

        $text = 'Text with a partial <a href="http://www.example.com">www.example.com</a> link';
        $expected = 'Text with a partial <a href="http://www.example.com">www.example.com</a> link';
        $result = $this->Text->autoLinkUrls($text, ['escape' => false]);
        $this->assertSame($expected, $result);

        $text = 'Text with a partial <a href="http://www.cakephp.org">link</a> link';
        $expected = 'Text with a partial <a href="http://www.cakephp.org">link</a> link';
        $result = $this->Text->autoLinkUrls($text, ['escape' => false]);
        $this->assertSame($expected, $result);

        $text = 'Text with a partial <iframe src="http://www.cakephp.org" /> link';
        $expected = 'Text with a partial <iframe src="http://www.cakephp.org" /> link';
        $result = $this->Text->autoLinkUrls($text, ['escape' => false]);
        $this->assertSame($expected, $result);

        $text = 'Text with a partial <iframe src="http://www.cakephp.org" /> link';
        $expected = 'Text with a partial &lt;iframe src=&quot;http://www.cakephp.org&quot; /&gt; link';
        $result = $this->Text->autoLinkUrls($text, ['escape' => true]);
        $this->assertSame($expected, $result);

        $text = 'Text with a url <a href="http://www.not-working-www.com">www.not-working-www.com</a> and more';
        $expected = 'Text with a url &lt;a href=&quot;http://www.not-working-www.com&quot;&gt;www.not-working-www.com&lt;/a&gt; and more';
        $result = $this->Text->autoLinkUrls($text, ['escape' => true]);
        $this->assertSame($expected, $result);

        $text = 'Text with a url www.not-working-www.com and more';
        $expected = 'Text with a url <a href="http://www.not-working-www.com">www.not-working-www.com</a> and more';
        $result = $this->Text->autoLinkUrls($text, ['escape' => false]);
        $this->assertSame($expected, $result);

        $text = 'Text with a url http://www.not-working-www.com and more';
        $expected = 'Text with a url <a href="http://www.not-working-www.com">http://www.not-working-www.com</a> and more';
        $result = $this->Text->autoLinkUrls($text, ['escape' => false]);
        $this->assertSame($expected, $result);

        $text = 'Text with a url http://www.www.not-working-www.com and more';
        $expected = 'Text with a url <a href="http://www.www.not-working-www.com">http://www.www.not-working-www.com</a> and more';
        $result = $this->Text->autoLinkUrls($text, ['escape' => false]);
        $this->assertSame($expected, $result);
    }

    /**
     * Test autoLinkUrls with query strings.
     */
    public function testAutoLinkUrlsQueryString(): void
    {
        $text = 'Text with a partial http://www.cakephp.org?product_id=123&foo=bar link';
        $expected = 'Text with a partial <a href="http://www.cakephp.org?product_id=123&amp;foo=bar">http://www.cakephp.org?product_id=123&amp;foo=bar</a> link';
        $result = $this->Text->autoLinkUrls($text);
        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for autoLinkEmail.
     *
     * @return array
     */
    public function autoLinkEmailProvider(): array
    {
        return [
            [
                'This is a test text',
                'This is a test text',
            ],

            [
                'email@example.com address',
                '<a href="mailto:email@example.com">email@example.com</a> address',
            ],

            [
                'email@example.com address',
                '<a href="mailto:email@example.com">email@example.com</a> address',
            ],

            [
                '(email@example.com) address',
                '(<a href="mailto:email@example.com">email@example.com</a>) address',
            ],

            [
                'Text with email@example.com address',
                'Text with <a href="mailto:email@example.com">email@example.com</a> address',
            ],

            [
                "Text with o'hare._-bob@example.com address",
                'Text with <a href="mailto:o&#039;hare._-bob@example.com">o&#039;hare._-bob@example.com</a> address',
            ],

            [
                'Text with düsentrieb@küchenschöhn-not-working.de address',
                'Text with <a href="mailto:düsentrieb@küchenschöhn-not-working.de">düsentrieb@küchenschöhn-not-working.de</a> address',
            ],

            [
                'Text with me@subdomain.küchenschöhn.de address',
                'Text with <a href="mailto:me@subdomain.küchenschöhn.de">me@subdomain.küchenschöhn.de</a> address',
            ],

            [
                'Text with email@example.com address',
                'Text with <a href="mailto:email@example.com" class="link">email@example.com</a> address',
                ['class' => 'link'],
            ],

            [
                '<p>mark@example.com</p>',
                '<p><a href="mailto:mark@example.com">mark@example.com</a></p>',
                ['escape' => false],
            ],

            [
                'Some&nbsp;mark@example.com&nbsp;Text',
                'Some&nbsp;<a href="mailto:mark@example.com">mark@example.com</a>&nbsp;Text',
                ['escape' => false],
            ],
        ];
    }

    /**
     * testAutoLinkEmails method
     *
     * @param string $text The text to link
     * @param string $expected The expected results.
     * @dataProvider autoLinkEmailProvider
     */
    public function testAutoLinkEmails(string $text, string $expected, array $attrs = []): void
    {
        $result = $this->Text->autoLinkEmails($text, $attrs);
        $this->assertSame($expected, $result);
    }

    /**
     * test invalid email addresses.
     */
    public function testAutoLinkEmailInvalid(): void
    {
        $result = $this->Text->autoLinkEmails('this is a myaddress@gmx-de test');
        $expected = 'this is a myaddress@gmx-de test';
        $this->assertSame($expected, $result);
    }

    /**
     * testAutoParagraph method
     */
    public function testAutoParagraph(): void
    {
        $text = 'This is a test text';
        $expected = <<<TEXT
<p>This is a test text</p>

TEXT;
        $result = $this->Text->autoParagraph($text);
        $text = 'This is a <br/> <BR> test text';
        $expected = <<<TEXT
<p>This is a </p>
<p> test text</p>

TEXT;
        $result = $this->Text->autoParagraph($text);
        $this->assertTextEquals($expected, $result);
        $result = $this->Text->autoParagraph($text);
        $text = 'This is a <BR id="test"/><br class="test"> test text';
        $expected = <<<TEXT
<p>This is a </p>
<p> test text</p>

TEXT;
        $result = $this->Text->autoParagraph($text);
        $this->assertTextEquals($expected, $result);
        $text = <<<TEXT
This is a test text.
This is a line return.
TEXT;
        $expected = <<<TEXT
<p>This is a test text.<br />
This is a line return.</p>

TEXT;
        $result = $this->Text->autoParagraph($text);
        $this->assertTextEquals($expected, $result);
        $text = <<<TEXT
This is a test text.

This is a new line.
TEXT;
        $expected = <<<TEXT
<p>This is a test text.</p>
<p>This is a new line.</p>

TEXT;
        $result = $this->Text->autoParagraph($text);
        $this->assertTextEquals($expected, $result);

        $expected = '';
        $result = $this->Text->autoParagraph(null);
        $this->assertTextEquals($expected, $result);
    }

    /**
     * testSlug method
     *
     * @param string $string String
     * @param array $options Options
     * @param String $expected Expected string
     * @dataProvider slugInputProvider
     */
    public function testSlug($string, $options, $expected): void
    {
        $result = $this->Text->slug($string, $options);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function slugInputProvider(): array
    {
        return [
            [
                'Foo Bar: Not just for breakfast any-more', [],
                'Foo-Bar-Not-just-for-breakfast-any-more',
            ],
            [
                'Foo Bar: Not just for breakfast any-more', ['replacement' => false],
                'FooBarNotjustforbreakfastanymore',
            ],
        ];
    }
}
