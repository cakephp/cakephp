<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Mailer\Message;
use Cake\Mailer\Transport\DebugTransport;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;
use TestApp\Mailer\TestMessage;

/**
 * MessageTest class
 */
class MessageTest extends TestCase
{
    /**
     * @var \Cake\Mailer\Message
     */
    protected $message;

    public function setUp(): void
    {
        parent::setUp();

        $this->message = new TestMessage();
    }

    /**
     * testWrap method
     */
    public function testWrap(): void
    {
        $renderer = new TestMessage();

        $text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
        $result = $renderer->doWrap($text, Message::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci,',
            'non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan amet.';
        $result = $renderer->doWrap($text, Message::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis',
            'orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan',
            'amet.',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = '<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula pellentesque accumsan amet.<hr></p>';
        $result = $renderer->doWrap($text, Message::LINE_LENGTH_SHOULD);
        $expected = [
            '<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac',
            'turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula',
            'pellentesque accumsan amet.<hr></p>',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac <a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
        $result = $renderer->doWrap($text, Message::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac',
            '<a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh',
            'nisi, vehicula pellentesque accumsan amet.',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum <a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">ok</a>';
        $result = $renderer->doWrap($text, Message::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum',
            '<a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">',
            'ok</a>',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite ok.';
        $result = $renderer->doWrap($text, Message::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum',
            'withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite',
            'ok.',
            '',
        ];
        $this->assertSame($expected, $result);

        /** @see https://github.com/cakephp/cakephp/issues/14459 */
        $line = 'some text <b>with html</b>';
        $trailing = str_repeat('X', Message::LINE_LENGTH_MUST - strlen($line));
        $result = $renderer->doWrap($line . $trailing, Message::LINE_LENGTH_MUST);
        $expected = [
            'some text <b>with',
            'html</b>' . $trailing,
            '',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * testWrapLongLine()
     */
    public function testWrapLongLine(): void
    {
        $transort = new DebugTransport();

        $message = '<a href="http://cakephp.org">' . str_repeat('x', Message::LINE_LENGTH_MUST) . '</a>';

        $this->message->setFrom('cake@cakephp.org');
        $this->message->setTo('cake@cakephp.org');
        $this->message->setSubject('Wordwrap Test');
        $this->message->setBodyText($message);

        $result = $transort->send($this->message);

        $expected = "<a\r\n" . 'href="http://cakephp.org">' . str_repeat('x', Message::LINE_LENGTH_MUST - 26) . "\r\n" .
            str_repeat('x', 26) . "\r\n</a>\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $str1 = 'a ';
        $str2 = ' b';
        $length = strlen($str1) + strlen($str2);
        $message = $str1 . str_repeat('x', Message::LINE_LENGTH_MUST - $length - 1) . $str2;

        $this->message->setBodyText($message);

        $result = $transort->send($this->message);
        $expected = "{$message}\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $message = $str1 . str_repeat('x', Message::LINE_LENGTH_MUST - $length) . $str2;

        $this->message->setBodyText($message);

        $result = $transort->send($this->message);
        $expected = "{$message}\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $message = $str1 . str_repeat('x', Message::LINE_LENGTH_MUST - $length + 1) . $str2;

        $this->message->setBodyText($message);

        $result = $transort->send($this->message);
        $expected = $str1 . str_repeat('x', Message::LINE_LENGTH_MUST - $length + 1) . sprintf("\r\n%s\r\n\r\n", trim($str2));
        $this->assertSame($expected, $result['message']);
        $this->assertLineLengths($result['message']);
    }

    /**
     * testWrapWithTagsAcrossLines()
     */
    public function testWrapWithTagsAcrossLines(): void
    {
        $str = <<<HTML
<table>
<th align="right" valign="top"
        style="font-weight: bold">The tag is across multiple lines</th>
</table>
HTML;
        $message = $str . str_repeat('x', Message::LINE_LENGTH_MUST + 1);

        $this->message->setFrom('cake@cakephp.org');
        $this->message->setTo('cake@cakephp.org');
        $this->message->setSubject('Wordwrap Test');
        $this->message->setBodyText($message);

        $result = (new DebugTransport())->send($this->message);

        $message = str_replace("\r\n", "\n", substr($message, 0, -9));
        $message = str_replace("\n", "\r\n", $message);
        $expected = "{$message}\r\nxxxxxxxxx\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertLineLengths($result['message']);
    }

    /**
     * CakeEmailTest::testWrapIncludeLessThanSign()
     */
    public function testWrapIncludeLessThanSign(): void
    {
        $str = 'foo<bar';
        $length = strlen($str);
        $message = $str . str_repeat('x', Message::LINE_LENGTH_MUST - $length + 1);

        $this->message->setFrom('cake@cakephp.org');
        $this->message->setTo('cake@cakephp.org');
        $this->message->setSubject('Wordwrap Test');
        $this->message->setBodyText($message);

        $result = (new DebugTransport())->send($this->message);
        $message = substr($message, 0, -1);
        $expected = "{$message}\r\nx\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertLineLengths($result['message']);
    }

    /**
     * CakeEmailTest::testWrapForJapaneseEncoding()
     */
    public function testWrapForJapaneseEncoding(): void
    {
        $this->skipIf(!function_exists('mb_convert_encoding'));

        $message = mb_convert_encoding('受け付けました', 'iso-2022-jp', 'UTF-8');

        $this->message->setFrom('cake@cakephp.org');
        $this->message->setTo('cake@cakephp.org');
        $this->message->setSubject('Wordwrap Test');
        $this->message->setCharset('iso-2022-jp');
        $this->message->setHeaderCharset('iso-2022-jp');
        $this->message->setBodyText($message);

        $result = (new DebugTransport())->send($this->message);
        $expected = "{$message}\r\n\r\n";
        $this->assertSame($expected, $result['message']);
    }

    /**
     * testHeaders method
     */
    public function testHeaders(): void
    {
        $this->message->setMessageId(false);
        $this->message->setHeaders(['X-Something' => 'nice']);
        $expected = [
            'X-Something' => 'nice',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
        ];
        $this->assertSame($expected, $this->message->getHeaders());

        $this->message->addHeaders(['X-Something' => 'very nice', 'X-Other' => 'cool']);
        $expected = [
            'X-Something' => 'very nice',
            'X-Other' => 'cool',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
        ];
        $this->assertSame($expected, $this->message->getHeaders());

        $this->message->setFrom('cake@cakephp.org');
        $this->assertSame($expected, $this->message->getHeaders());

        $expected = [
            'From' => 'cake@cakephp.org',
            'X-Something' => 'very nice',
            'X-Other' => 'cool',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
        ];
        $this->assertSame($expected, $this->message->getHeaders(['from' => true]));

        $this->message->setFrom('cake@cakephp.org', 'CakePHP');
        $expected['From'] = 'CakePHP <cake@cakephp.org>';
        $this->assertSame($expected, $this->message->getHeaders(['from' => true]));

        $this->message->setTo(['cake@cakephp.org', 'php@cakephp.org' => 'CakePHP']);
        $expected = [
            'From' => 'CakePHP <cake@cakephp.org>',
            'To' => 'cake@cakephp.org, CakePHP <php@cakephp.org>',
            'X-Something' => 'very nice',
            'X-Other' => 'cool',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
        ];
        $this->assertSame($expected, $this->message->getHeaders(['from' => true, 'to' => true]));

        $this->message->setCharset('ISO-2022-JP');
        $expected = [
            'From' => 'CakePHP <cake@cakephp.org>',
            'To' => 'cake@cakephp.org, CakePHP <php@cakephp.org>',
            'X-Something' => 'very nice',
            'X-Other' => 'cool',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=ISO-2022-JP',
            'Content-Transfer-Encoding' => '7bit',
        ];
        $this->assertSame($expected, $this->message->getHeaders(['from' => true, 'to' => true]));

        $result = $this->message->setHeaders([]);
        $this->assertInstanceOf(Message::class, $result);

        $this->message->setHeaders(['o:tag' => ['foo']]);
        $this->message->addHeaders(['o:tag' => ['bar']]);
        $result = $this->message->getHeaders();
        $this->assertEquals(['foo', 'bar'], $result['o:tag']);
    }

    /**
     * testHeadersString method
     */
    public function testHeadersString(): void
    {
        $this->message->setMessageId(false);
        $this->message->setHeaders(['X-Something' => 'nice']);
        $expected = [
            'X-Something: nice',
            'Date: ' . date(DATE_RFC2822),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        $this->assertSame(implode("\r\n", $expected), $this->message->getHeadersString());
    }

    /**
     * testFrom method
     */
    public function testFrom(): void
    {
        $this->assertSame([], $this->message->getFrom());

        $this->message->setFrom('cake@cakephp.org');
        $expected = ['cake@cakephp.org' => 'cake@cakephp.org'];
        $this->assertSame($expected, $this->message->getFrom());

        $this->message->setFrom(['cake@cakephp.org']);
        $this->assertSame($expected, $this->message->getFrom());

        $this->message->setFrom('cake@cakephp.org', 'CakePHP');
        $expected = ['cake@cakephp.org' => 'CakePHP'];
        $this->assertSame($expected, $this->message->getFrom());

        $result = $this->message->setFrom(['cake@cakephp.org' => 'CakePHP']);
        $this->assertSame($expected, $this->message->getFrom());
        $this->assertSame($this->message, $result);

        $this->expectException(InvalidArgumentException::class);
        $result = $this->message->setFrom(['cake@cakephp.org' => 'CakePHP', 'fail@cakephp.org' => 'From can only be one address']);
    }

    /**
     * Test that from addresses using colons work.
     */
    public function testFromWithColonsAndQuotes(): void
    {
        $address = [
            'info@example.com' => '70:20:00 " Forum',
        ];
        $this->message->setFrom($address);
        $this->assertEquals($address, $this->message->getFrom());

        $result = $this->message->getHeadersString(['from']);
        $this->assertStringContainsString('From: "70:20:00 \" Forum" <info@example.com>', $result);
    }

    /**
     * testSender method
     */
    public function testSender(): void
    {
        $this->message->reset();
        $this->assertSame([], $this->message->getSender());

        $this->message->setSender('cake@cakephp.org', 'Name');
        $expected = ['cake@cakephp.org' => 'Name'];
        $this->assertSame($expected, $this->message->getSender());

        $headers = $this->message->getHeaders(['from' => true, 'sender' => true]);
        $this->assertSame('', $headers['From']);
        $this->assertSame('Name <cake@cakephp.org>', $headers['Sender']);

        $this->message->setFrom('cake@cakephp.org', 'CakePHP');
        $headers = $this->message->getHeaders(['from' => true, 'sender' => true]);
        $this->assertSame('CakePHP <cake@cakephp.org>', $headers['From']);
        $this->assertSame('', $headers['Sender']);
    }

    /**
     * testTo method
     */
    public function testTo(): void
    {
        $this->assertSame([], $this->message->getTo());

        $result = $this->message->setTo('cake@cakephp.org');
        $expected = ['cake@cakephp.org' => 'cake@cakephp.org'];
        $this->assertSame($expected, $this->message->getTo());
        $this->assertSame($this->message, $result);

        $this->message->setTo('cake@cakephp.org', 'CakePHP');
        $expected = ['cake@cakephp.org' => 'CakePHP'];
        $this->assertSame($expected, $this->message->getTo());

        $list = [
            'root@localhost' => 'root',
            'bjørn@hammeröath.com' => 'Bjorn',
            'cake.php@cakephp.org' => 'Cake PHP',
            'cake-php@googlegroups.com' => 'Cake Groups',
            'root@cakephp.org',
        ];
        $this->message->setTo($list);
        $expected = [
            'root@localhost' => 'root',
            'bjørn@hammeröath.com' => 'Bjorn',
            'cake.php@cakephp.org' => 'Cake PHP',
            'cake-php@googlegroups.com' => 'Cake Groups',
            'root@cakephp.org' => 'root@cakephp.org',
        ];
        $this->assertSame($expected, $this->message->getTo());

        $this->message->addTo('jrbasso@cakephp.org');
        $this->message->addTo('mark_story@cakephp.org', 'Mark Story');
        $this->message->addTo('foobar@ætdcadsl.dk');
        $result = $this->message->addTo(['phpnut@cakephp.org' => 'PhpNut', 'jose_zap@cakephp.org']);
        $expected = [
            'root@localhost' => 'root',
            'bjørn@hammeröath.com' => 'Bjorn',
            'cake.php@cakephp.org' => 'Cake PHP',
            'cake-php@googlegroups.com' => 'Cake Groups',
            'root@cakephp.org' => 'root@cakephp.org',
            'jrbasso@cakephp.org' => 'jrbasso@cakephp.org',
            'mark_story@cakephp.org' => 'Mark Story',
            'foobar@ætdcadsl.dk' => 'foobar@ætdcadsl.dk',
            'phpnut@cakephp.org' => 'PhpNut',
            'jose_zap@cakephp.org' => 'jose_zap@cakephp.org',
        ];
        $this->assertSame($expected, $this->message->getTo());
        $this->assertSame($this->message, $result);
    }

    /**
     * test to address with _ in domain name
     */
    public function testToUnderscoreDomain(): void
    {
        $result = $this->message->setTo('cake@cake_php.org');
        $expected = ['cake@cake_php.org' => 'cake@cake_php.org'];
        $this->assertSame($expected, $this->message->getTo());
        $this->assertSame($this->message, $result);
    }

    /**
     * Data provider function for testBuildInvalidData
     *
     * @return array
     */
    public static function invalidEmails(): array
    {
        return [
            [''],
            ['string'],
            ['<tag>'],
            [['ok@cakephp.org', '1.0', '', 'string']],
        ];
    }

    /**
     * testBuildInvalidData
     *
     * @dataProvider invalidEmails
     * @param array|string $value
     */
    public function testInvalidEmail($value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->message->setTo($value);
    }

    /**
     * testBuildInvalidData
     *
     * @dataProvider invalidEmails
     * @param array|string $value
     */
    public function testInvalidEmailAdd($value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->message->addTo($value);
    }

    /**
     * test emailPattern method
     */
    public function testEmailPattern(): void
    {
        $regex = '/.+@.+\..+/i';
        $this->assertSame($regex, $this->message->setEmailPattern($regex)->getEmailPattern());
    }

    /**
     * Tests that it is possible to set email regex configuration to a CakeEmail object
     */
    public function testConfigEmailPattern(): void
    {
        $regex = '/.+@.+\..+/i';
        $email = new Message(['emailPattern' => $regex]);
        $this->assertSame($regex, $email->getEmailPattern());
    }

    /**
     * Tests that it is possible set custom email validation
     */
    public function testCustomEmailValidation(): void
    {
        $regex = '/^[\.a-z0-9!#$%&\'*+\/=?^_`{|}~-]+@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]{2,6}$/i';

        $this->message->setEmailPattern($regex)->setTo('pass.@example.com');
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
        ], $this->message->getTo());

        $this->message->addTo('pass..old.docomo@example.com');
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
            'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
        ], $this->message->getTo());

        $this->message->reset();
        $emails = [
            'pass.@example.com',
            'pass..old.docomo@example.com',
        ];
        $additionalEmails = [
            '.extend.@example.com',
            '.docomo@example.com',
        ];
        $this->message->setEmailPattern($regex)->setTo($emails);
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
            'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
        ], $this->message->getTo());

        $this->message->addTo($additionalEmails);
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
            'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
            '.extend.@example.com' => '.extend.@example.com',
            '.docomo@example.com' => '.docomo@example.com',
        ], $this->message->getTo());
    }

    /**
     * Tests that it is possible to unset the email pattern and make use of filter_var() instead.
     */
    public function testUnsetEmailPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email set for "to". You passed "fail.@example.com".');
        $email = new Message();
        $this->assertSame(Message::EMAIL_PATTERN, $email->getEmailPattern());

        $email->setEmailPattern(null);
        $this->assertNull($email->getEmailPattern());

        $email->setTo('pass@example.com');
        $email->setTo('fail.@example.com');
    }

    /**
     * Tests that passing an empty string throws an InvalidArgumentException.
     */
    public function testEmptyTo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The email set for "to" is empty.');
        $email = new Message();
        $email->setTo('');
    }

    /**
     * testFormatAddress method
     */
    public function testFormatAddress(): void
    {
        $result = $this->message->fmtAddress(['cake@cakephp.org' => 'cake@cakephp.org']);
        $expected = ['cake@cakephp.org'];
        $this->assertSame($expected, $result);

        $result = $this->message->fmtAddress([
            'cake@cakephp.org' => 'cake@cakephp.org',
            'php@cakephp.org' => 'php@cakephp.org',
        ]);
        $expected = ['cake@cakephp.org', 'php@cakephp.org'];
        $this->assertSame($expected, $result);

        $result = $this->message->fmtAddress([
            'cake@cakephp.org' => 'CakePHP',
            'php@cakephp.org' => 'Cake',
        ]);
        $expected = ['CakePHP <cake@cakephp.org>', 'Cake <php@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->message->fmtAddress(['me@example.com' => 'Last, First']);
        $expected = ['"Last, First" <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->message->fmtAddress(['me@example.com' => '"Last" First']);
        $expected = ['"\"Last\" First" <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->message->fmtAddress(['me@example.com' => 'Last First']);
        $expected = ['Last First <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->message->fmtAddress(['cake@cakephp.org' => 'ÄÖÜTest']);
        $expected = ['=?UTF-8?B?w4TDlsOcVGVzdA==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->message->fmtAddress(['cake@cakephp.org' => '日本語Test']);
        $expected = ['=?UTF-8?B?5pel5pys6KqeVGVzdA==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);
    }

    /**
     * testFormatAddressJapanese
     */
    public function testFormatAddressJapanese(): void
    {
        $this->message->setHeaderCharset('ISO-2022-JP');
        $result = $this->message->fmtAddress(['cake@cakephp.org' => '日本語Test']);
        $expected = ['=?ISO-2022-JP?B?GyRCRnxLXDhsGyhCVGVzdA==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->message->fmtAddress(['cake@cakephp.org' => '寿限無寿限無五劫の擦り切れ海砂利水魚の水行末雲来末風来末食う寝る処に住む処やぶら小路の藪柑子パイポパイポパイポのシューリンガンシューリンガンのグーリンダイグーリンダイのポンポコピーのポンポコナーの長久命の長助']);
        $expected = ["=?ISO-2022-JP?B?GyRCPHc4Qkw1PHc4Qkw1OF45ZSROOyQkakBaJGwzJDo9TXg/ZTV7GyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCJE4/ZTlUS3YxQE1oS3ZJd01oS3Y/KSQmPzIkaz1oJEs9OyRgGyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCPWgkZCRWJGk+Lk8pJE5pLjQ7O1IlUSUkJV0lUSUkJV0lUSUkGyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCJV0kTiU3JWUhPCVqJXMlLCVzJTclZSE8JWolcyUsJXMkTiUwGyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCITwlaiVzJUAlJCUwITwlaiVzJUAlJCROJV0lcyVdJTMlVCE8GyhC?=\r\n" .
            ' =?ISO-2022-JP?B?GyRCJE4lXSVzJV0lMyVKITwkTkQ5NVdMPyRORDk9dRsoQg==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);
    }

    /**
     * testAddresses method
     */
    public function testAddresses(): void
    {
        $this->message->reset();
        $this->message->setFrom('cake@cakephp.org', 'CakePHP');
        $this->message->setReplyTo('replyto@cakephp.org', 'ReplyTo CakePHP');
        $this->message->setReadReceipt('readreceipt@cakephp.org', 'ReadReceipt CakePHP');
        $this->message->setReturnPath('returnpath@cakephp.org', 'ReturnPath CakePHP');
        $this->message->setTo('to@cakephp.org', 'To, CakePHP');
        $this->message->setCc('cc@cakephp.org', 'Cc CakePHP');
        $this->message->setBcc('bcc@cakephp.org', 'Bcc CakePHP');
        $this->message->addTo('to2@cakephp.org', 'To2 CakePHP');
        $this->message->addCc('cc2@cakephp.org', 'Cc2 CakePHP');
        $this->message->addBcc('bcc2@cakephp.org', 'Bcc2 CakePHP');
        $this->message->addReplyTo('replyto2@cakephp.org', 'ReplyTo2 CakePHP');

        $this->assertSame($this->message->getFrom(), ['cake@cakephp.org' => 'CakePHP']);
        $this->assertSame($this->message->getReplyTo(), ['replyto@cakephp.org' => 'ReplyTo CakePHP', 'replyto2@cakephp.org' => 'ReplyTo2 CakePHP']);
        $this->assertSame($this->message->getReadReceipt(), ['readreceipt@cakephp.org' => 'ReadReceipt CakePHP']);
        $this->assertSame($this->message->getReturnPath(), ['returnpath@cakephp.org' => 'ReturnPath CakePHP']);
        $this->assertSame($this->message->getTo(), ['to@cakephp.org' => 'To, CakePHP', 'to2@cakephp.org' => 'To2 CakePHP']);
        $this->assertSame($this->message->getCc(), ['cc@cakephp.org' => 'Cc CakePHP', 'cc2@cakephp.org' => 'Cc2 CakePHP']);
        $this->assertSame($this->message->getBcc(), ['bcc@cakephp.org' => 'Bcc CakePHP', 'bcc2@cakephp.org' => 'Bcc2 CakePHP']);

        $headers = $this->message->getHeaders(array_fill_keys(['from', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc'], true));
        $this->assertSame($headers['From'], 'CakePHP <cake@cakephp.org>');
        $this->assertSame($headers['Reply-To'], 'ReplyTo CakePHP <replyto@cakephp.org>, ReplyTo2 CakePHP <replyto2@cakephp.org>');
        $this->assertSame($headers['Disposition-Notification-To'], 'ReadReceipt CakePHP <readreceipt@cakephp.org>');
        $this->assertSame($headers['Return-Path'], 'ReturnPath CakePHP <returnpath@cakephp.org>');
        $this->assertSame($headers['To'], '"To, CakePHP" <to@cakephp.org>, To2 CakePHP <to2@cakephp.org>');
        $this->assertSame($headers['Cc'], 'Cc CakePHP <cc@cakephp.org>, Cc2 CakePHP <cc2@cakephp.org>');
        $this->assertSame($headers['Bcc'], 'Bcc CakePHP <bcc@cakephp.org>, Bcc2 CakePHP <bcc2@cakephp.org>');

        $this->message->setReplyTo(['replyto@cakephp.org' => 'ReplyTo CakePHP', 'replyto2@cakephp.org' => 'ReplyTo2 CakePHP']);
        $this->assertSame($this->message->getReplyTo(), ['replyto@cakephp.org' => 'ReplyTo CakePHP', 'replyto2@cakephp.org' => 'ReplyTo2 CakePHP']);
        $headers = $this->message->getHeaders(array_fill_keys(['replyTo'], true));
        $this->assertSame($headers['Reply-To'], 'ReplyTo CakePHP <replyto@cakephp.org>, ReplyTo2 CakePHP <replyto2@cakephp.org>');
    }

    /**
     * test reset addresses method
     */
    public function testResetAddresses(): void
    {
        $this->message->reset();
        $this->message
            ->setFrom('cake@cakephp.org', 'CakePHP')
            ->setReplyTo('replyto@cakephp.org', 'ReplyTo CakePHP')
            ->setReadReceipt('readreceipt@cakephp.org', 'ReadReceipt CakePHP')
            ->setReturnPath('returnpath@cakephp.org', 'ReturnPath CakePHP')
            ->setTo('to@cakephp.org', 'To, CakePHP')
            ->setCc('cc@cakephp.org', 'Cc CakePHP')
            ->setBcc('bcc@cakephp.org', 'Bcc CakePHP');

        $this->assertNotEmpty($this->message->getFrom());
        $this->assertNotEmpty($this->message->getReplyTo());
        $this->assertNotEmpty($this->message->getReadReceipt());
        $this->assertNotEmpty($this->message->getReturnPath());
        $this->assertNotEmpty($this->message->getTo());
        $this->assertNotEmpty($this->message->getCc());
        $this->assertNotEmpty($this->message->getBcc());

        $this->message
            ->setFrom([])
            ->setReplyTo([])
            ->setReadReceipt([])
            ->setReturnPath([])
            ->setTo([])
            ->setCc([])
            ->setBcc([]);

        $this->assertEmpty($this->message->getFrom());
        $this->assertEmpty($this->message->getReplyTo());
        $this->assertEmpty($this->message->getReadReceipt());
        $this->assertEmpty($this->message->getReturnPath());
        $this->assertEmpty($this->message->getTo());
        $this->assertEmpty($this->message->getCc());
        $this->assertEmpty($this->message->getBcc());
    }

    /**
     * testMessageId method
     */
    public function testMessageId(): void
    {
        $this->message->setMessageId(true);
        $result = $this->message->getHeaders();
        $this->assertArrayHasKey('Message-ID', $result);

        $this->message->setMessageId(false);
        $result = $this->message->getHeaders();
        $this->assertArrayNotHasKey('Message-ID', $result);

        $result = $this->message->setMessageId('<my-email@localhost>');
        $this->assertSame($this->message, $result);
        $result = $this->message->getHeaders();
        $this->assertSame('<my-email@localhost>', $result['Message-ID']);

        $result = $this->message->getMessageId();
        $this->assertSame('<my-email@localhost>', $result);
    }

    public function testAutoMessageIdIsIdempotent(): void
    {
        $headers = $this->message->getHeaders();
        $this->assertArrayHasKey('Message-ID', $headers);

        $regeneratedHeaders = $this->message->getHeaders();
        $this->assertSame($headers['Message-ID'], $regeneratedHeaders['Message-ID']);
    }

    public function testPriority(): void
    {
        $this->message->setPriority(4);

        $this->assertSame(4, $this->message->getPriority());

        $result = $this->message->getHeaders();
        $this->assertArrayHasKey('X-Priority', $result);
    }

    /**
     * testMessageIdInvalid method
     */
    public function testMessageIdInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->message->setMessageId('my-email@localhost');
    }

    /**
     * testDomain method
     */
    public function testDomain(): void
    {
        $result = $this->message->getDomain();
        $expected = env('HTTP_HOST') ? env('HTTP_HOST') : php_uname('n');
        $this->assertSame($expected, $result);

        $this->message->setDomain('example.org');
        $result = $this->message->getDomain();
        $expected = 'example.org';
        $this->assertSame($expected, $result);
    }

    /**
     * testMessageIdWithDomain method
     */
    public function testMessageIdWithDomain(): void
    {
        $this->message->setDomain('example.org');
        $result = $this->message->getHeaders();
        $expected = '@example.org>';
        $this->assertTextContains($expected, $result['Message-ID']);

        $_SERVER['HTTP_HOST'] = 'example.org';
        $result = $this->message->getHeaders();
        $this->assertTextContains('example.org', $result['Message-ID']);

        $_SERVER['HTTP_HOST'] = 'example.org:81';
        $result = $this->message->getHeaders();
        $this->assertTextNotContains(':81', $result['Message-ID']);
    }

    /**
     * testSubject method
     */
    public function testSubject(): void
    {
        $this->message->setSubject('You have a new message.');
        $this->assertSame('You have a new message.', $this->message->getSubject());

        $this->message->setSubject('You have a new message, I think.');
        $this->assertSame($this->message->getSubject(), 'You have a new message, I think.');
        $this->message->setSubject('1');
        $this->assertSame('1', $this->message->getSubject());

        $input = 'هذه رسالة بعنوان طويل مرسل للمستلم';
        $this->message->setSubject($input);
        $expected = '=?UTF-8?B?2YfYsNmHINix2LPYp9mE2Kkg2KjYudmG2YjYp9mGINi32YjZitmEINmF2LE=?=' . "\r\n" . ' =?UTF-8?B?2LPZhCDZhNmE2YXYs9iq2YTZhQ==?=';
        $this->assertSame($expected, $this->message->getSubject());
        $this->assertSame($input, $this->message->getOriginalSubject());
    }

    /**
     * testSubjectJapanese
     */
    public function testSubjectJapanese(): void
    {
        mb_internal_encoding('UTF-8');

        $this->message->setHeaderCharset('ISO-2022-JP');
        $this->message->setSubject('日本語のSubjectにも対応するよ');
        $expected = '=?ISO-2022-JP?B?GyRCRnxLXDhsJE4bKEJTdWJqZWN0GyRCJEskYkJQMX4kOSRrJGgbKEI=?=';
        $this->assertSame($expected, $this->message->getSubject());

        $this->message->setSubject('長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？');
        $expected = "=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
            " =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
            ' =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=';
        $this->assertSame($expected, $this->message->getSubject());
    }

    /**
     * testAttachments method
     */
    public function testSetAttachments(): void
    {
        $uploadedFile = new UploadedFile(
            __FILE__,
            filesize(__FILE__),
            UPLOAD_ERR_OK,
            'MessageTest.php',
            'text/x-php'
        );

        $this->message->setAttachments([
            CAKE . 'basics.php',
            $uploadedFile,
        ]);
        $expected = [
            'basics.php' => [
                'file' => CAKE . 'basics.php',
                'mimetype' => 'text/x-php',
            ],
            'MessageTest.php' => [
                'file' => $uploadedFile,
                'mimetype' => 'text/x-php',
            ],
        ];
        $this->assertSame($expected, $this->message->getAttachments());

        $this->message->setAttachments([]);
        $this->assertSame([], $this->message->getAttachments());

        $this->message->setAttachments([
            ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain'],
        ]);
        $this->message->addAttachments([CORE_PATH . 'config' . DS . 'bootstrap.php']);
        $this->message->addAttachments([CORE_PATH . 'config' . DS . 'bootstrap.php']);
        $this->message->addAttachments([
            'other.txt' => CORE_PATH . 'config' . DS . 'bootstrap.php',
            'license' => CORE_PATH . 'LICENSE',
        ]);
        $expected = [
            'basics.php' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain'],
            'bootstrap.php' => ['file' => CORE_PATH . 'config' . DS . 'bootstrap.php', 'mimetype' => 'text/x-php'],
            'other.txt' => ['file' => CORE_PATH . 'config' . DS . 'bootstrap.php', 'mimetype' => 'text/x-php'],
            'license' => ['file' => CORE_PATH . 'LICENSE', 'mimetype' => 'text/plain'],
        ];
        $this->assertSame($expected, $this->message->getAttachments());
        $this->expectException(InvalidArgumentException::class);
        $this->message->setAttachments([['nofile' => CAKE . 'basics.php', 'mimetype' => 'text/plain']]);
    }

    /**
     * Test send() with no template and data string attachment and no mimetype
     */
    public function testSetAttachmentDataNoMimetype(): void
    {
        $this->message->setAttachments(['cake.icon.gif' => [
            'data' => 'test',
        ]]);
        $result = $this->message->getAttachments();
        $expected = [
            'cake.icon.gif' => [
                'data' => base64_encode('test') . "\r\n",
                'mimetype' => 'application/octet-stream',
            ],
        ];
        $this->assertSame($expected, $this->message->getAttachments());
    }

    public function testSetAttachmentInvalidFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'File must be a filepath or UploadedFileInterface instance. Found `boolean` instead.'
        );

        $this->message->setAttachments(['cake.icon.gif' => [
            'file' => true,
        ]]);
    }

    /**
     * testReset method
     */
    public function testReset(): void
    {
        $this->message->setTo('cake@cakephp.org');
        $this->message->setEmailPattern('/.+@.+\..+/i');
        $this->assertSame(['cake@cakephp.org' => 'cake@cakephp.org'], $this->message->getTo());

        $this->message->reset();
        $this->assertSame([], $this->message->getTo());
        $this->assertSame(Message::EMAIL_PATTERN, $this->message->getEmailPattern());
    }

    /**
     * testReset with charset
     */
    public function testResetWithCharset(): void
    {
        $this->message->setCharset('ISO-2022-JP');
        $this->message->reset();

        $this->assertSame('utf-8', $this->message->getCharset());
        $this->assertSame('utf-8', $this->message->getHeaderCharset());
    }

    /**
     * testEmailFormat method
     */
    public function testEmailFormat(): void
    {
        $result = $this->message->getEmailFormat();
        $this->assertSame('text', $result);

        $result = $this->message->setEmailFormat('html');
        $this->assertInstanceOf(Message::class, $result);

        $result = $this->message->getEmailFormat();
        $this->assertSame('html', $result);

        $this->expectException(InvalidArgumentException::class);
        $this->message->setEmailFormat('invalid');
    }

    /**
     * Tests that it is possible to add charset configuration to a CakeEmail object
     */
    public function testConfigCharset(): void
    {
        $email = new Message();
        $this->assertEquals(Configure::read('App.encoding'), $email->getCharset());
        $this->assertEquals(Configure::read('App.encoding'), $email->getHeaderCharset());

        $email = new Message(['charset' => 'iso-2022-jp', 'headerCharset' => 'iso-2022-jp-ms']);
        $this->assertSame('iso-2022-jp', $email->getCharset());
        $this->assertSame('iso-2022-jp-ms', $email->getHeaderCharset());

        $email = new Message(['charset' => 'iso-2022-jp']);
        $this->assertSame('iso-2022-jp', $email->getCharset());
        $this->assertSame('iso-2022-jp', $email->getHeaderCharset());

        $email = new Message(['headerCharset' => 'iso-2022-jp-ms']);
        $this->assertEquals(Configure::read('App.encoding'), $email->getCharset());
        $this->assertSame('iso-2022-jp-ms', $email->getHeaderCharset());
    }

    public function testGetBody(): void
    {
        $message = new Message();

        $uploadedFile = new UploadedFile(
            __FILE__,
            filesize(__FILE__),
            UPLOAD_ERR_OK,
            'MessageTest.php',
            'text/x-php'
        );
        $chunks = base64_encode(file_get_contents(__FILE__));

        $result = $message->setAttachments([$uploadedFile])
            ->setBodyText('Attached an uploaded file')
            ->getBody();
        $result = implode("\r\n", $result);
        $this->assertStringContainsString($chunks[0], $result);
    }

    /**
     * Tests that the body is encoded using the configured charset (Japanese standard encoding)
     */
    public function testBodyEncodingIso2022Jp(): void
    {
        $message = new Message([
            'charset' => 'iso-2022-jp',
            'headerCharset' => 'iso-2022-jp',
            'transport' => 'debug',
        ]);
        $message->setSubject('あれ？もしかしての前と');

        $headers = $message->getHeaders(['subject']);
        $expected = '?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=';
        $this->assertStringContainsString($expected, $headers['Subject']);

        $message->setBodyHtml('①㈱ ってテーブルを作ってやってたらう');

        $result = $message->getHeadersString();
        $this->assertTextContains('Content-Type: text/plain; charset=ISO-2022-JP', $result);
        $this->assertTextNotContains('Content-Type: text/plain; charset=ISO-2022-JP-MS', $result); // not charset=iso-2022-jp-ms

        $result = implode('', $message->getBody());
        $this->assertTextNotContains(mb_convert_encoding('①㈱ ってテーブルを作ってやってたらう', 'ISO-2022-JP-MS'), $result);
    }

    /**
     * Tests that the body is encoded using the configured charset (Japanese irregular encoding, but sometime use this)
     */
    public function testBodyEncodingIso2022JpMs(): void
    {
        $message = new Message([
            'charset' => 'iso-2022-jp-ms',
            'headerCharset' => 'iso-2022-jp-ms',
            'transport' => 'debug',
        ]);
        $message->setSubject('あれ？もしかしての前と');
        $headers = $message->getHeaders(['subject']);
        $expected = '?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=';
        $this->assertStringContainsString($expected, $headers['Subject']);

        $result = $message->setBodyText('①㈱ ってテーブルを作ってやってたらう');

        $result = $message->getHeadersString();
        $this->assertTextContains('Content-Type: text/plain; charset=ISO-2022-JP', $result);
        $this->assertTextNotContains('Content-Type: text/plain; charset=iso-2022-jp-ms', $result); // not charset=iso-2022-jp-ms

        $result = implode('', $message->getBody());
        $this->assertStringContainsString(mb_convert_encoding('①㈱ ってテーブルを作ってやってたらう', 'ISO-2022-JP-MS'), $result);
    }

    /**
     * Tests that the body is encoded using the configured charset
     */
    public function testEncodingMixed(): void
    {
        $message = new Message([
            'headerCharset' => 'iso-2022-jp-ms',
            'charset' => 'iso-2022-jp',
        ]);

        $message->setBodyText('ってテーブルを作ってやってたらう');

        $result = $message->getHeadersString();
        $this->assertStringContainsString('Content-Type: text/plain; charset=ISO-2022-JP', $result);

        $result = implode('', $message->getBody());
        $this->assertStringContainsString(mb_convert_encoding('ってテーブルを作ってやってたらう', 'ISO-2022-JP'), $result);
    }

    /**
     * Test CakeMessage::_encode function
     */
    public function testEncode(): void
    {
        $this->message->setHeaderCharset('ISO-2022-JP');
        $result = $this->message->encode('日本語');
        $expected = '=?ISO-2022-JP?B?GyRCRnxLXDhsGyhC?=';
        $this->assertSame($expected, $result);

        $this->message->setHeaderCharset('ISO-2022-JP');
        $result = $this->message->encode('長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？');
        $expected = "=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
            " =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
            ' =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=';
        $this->assertSame($expected, $result);
    }

    /**
     * Test CakeMessage::_decode function
     */
    public function testDecode(): void
    {
        $this->message->setHeaderCharset('ISO-2022-JP');
        $result = $this->message->decode('=?ISO-2022-JP?B?GyRCRnxLXDhsGyhC?=');
        $expected = '日本語';
        $this->assertSame($expected, $result);

        $this->message->setHeaderCharset('ISO-2022-JP');
        $result = $this->message->decode("=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
            " =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
            ' =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=');
        $expected = '長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？';
        $this->assertSame($expected, $result);
    }

    /**
     * Tests charset setter/getter
     */
    public function testCharset(): void
    {
        $this->message->setCharset('UTF-8');
        $this->assertSame($this->message->getCharset(), 'UTF-8');

        $this->message->setCharset('ISO-2022-JP');
        $this->assertSame($this->message->getCharset(), 'ISO-2022-JP');

        $charset = $this->message->setCharset('Shift_JIS');
        $this->assertSame('Shift_JIS', $charset->getCharset());
    }

    /**
     * Tests headerCharset setter/getter
     */
    public function testHeaderCharset(): void
    {
        $this->message->setHeaderCharset('UTF-8');
        $this->assertSame($this->message->getHeaderCharset(), 'UTF-8');

        $this->message->setHeaderCharset('ISO-2022-JP');
        $this->assertSame($this->message->getHeaderCharset(), 'ISO-2022-JP');

        $charset = $this->message->setHeaderCharset('Shift_JIS');
        $this->assertSame('Shift_JIS', $charset->getHeaderCharset());
    }

    /**
     * Test transferEncoding
     */
    public function testTransferEncoding(): void
    {
        // Test new transfer encoding
        $expected = 'quoted-printable';
        $this->message->setTransferEncoding($expected);
        $this->assertSame($expected, $this->message->getTransferEncoding());
        $this->assertSame($expected, $this->message->getContentTransferEncoding());

        // Test default charset/encoding : utf8/8bit
        $expected = '8bit';
        $this->message->reset();
        $this->assertNull($this->message->getTransferEncoding());
        $this->assertSame($expected, $this->message->getContentTransferEncoding());

        //Test wrong encoding
        $this->expectException(InvalidArgumentException::class);
        $this->message->setTransferEncoding('invalid');
    }

    /**
     * Tests for compatible check.
     *          charset property and       charset() method.
     *    headerCharset property and headerCharset() method.
     */
    public function testCharsetsCompatible(): void
    {
        $checkHeaders = [
            'from' => true,
            'to' => true,
            'cc' => true,
            'subject' => true,
        ];

        // Header Charset : null (used by default UTF-8)
        //   Body Charset : ISO-2022-JP
        $oldStyleEmail = $this->_getEmailByOldStyleCharset('iso-2022-jp', null);
        $oldStyleHeaders = $oldStyleEmail->getHeaders($checkHeaders);

        $newStyleEmail = $this->_getEmailByNewStyleCharset('iso-2022-jp', null);
        $newStyleHeaders = $newStyleEmail->getHeaders($checkHeaders);

        $this->assertSame($oldStyleHeaders['From'], $newStyleHeaders['From']);
        $this->assertSame($oldStyleHeaders['To'], $newStyleHeaders['To']);
        $this->assertSame($oldStyleHeaders['Cc'], $newStyleHeaders['Cc']);
        $this->assertSame($oldStyleHeaders['Subject'], $newStyleHeaders['Subject']);

        // Header Charset : UTF-8
        //   Boby Charset : ISO-2022-JP
        $oldStyleEmail = $this->_getEmailByOldStyleCharset('iso-2022-jp', 'utf-8');
        $oldStyleHeaders = $oldStyleEmail->getHeaders($checkHeaders);

        $newStyleEmail = $this->_getEmailByNewStyleCharset('iso-2022-jp', 'utf-8');
        $newStyleHeaders = $newStyleEmail->getHeaders($checkHeaders);

        $this->assertSame($oldStyleHeaders['From'], $newStyleHeaders['From']);
        $this->assertSame($oldStyleHeaders['To'], $newStyleHeaders['To']);
        $this->assertSame($oldStyleHeaders['Cc'], $newStyleHeaders['Cc']);
        $this->assertSame($oldStyleHeaders['Subject'], $newStyleHeaders['Subject']);

        // Header Charset : ISO-2022-JP
        //   Boby Charset : UTF-8
        $oldStyleEmail = $this->_getEmailByOldStyleCharset('utf-8', 'iso-2022-jp');
        $oldStyleHeaders = $oldStyleEmail->getHeaders($checkHeaders);

        $newStyleEmail = $this->_getEmailByNewStyleCharset('utf-8', 'iso-2022-jp');
        $newStyleHeaders = $newStyleEmail->getHeaders($checkHeaders);

        $this->assertSame($oldStyleHeaders['From'], $newStyleHeaders['From']);
        $this->assertSame($oldStyleHeaders['To'], $newStyleHeaders['To']);
        $this->assertSame($oldStyleHeaders['Cc'], $newStyleHeaders['Cc']);
        $this->assertSame($oldStyleHeaders['Subject'], $newStyleHeaders['Subject']);
    }

    /**
     * @param mixed $charset
     * @param mixed $headerCharset
     */
    protected function _getEmailByOldStyleCharset($charset, $headerCharset): Message
    {
        $message = new Message(['transport' => 'debug']);

        if (!empty($charset)) {
            $message->setCharset($charset);
        }
        if (!empty($headerCharset)) {
            $message->setHeaderCharset($headerCharset);
        }

        $message->setFrom('someone@example.com', 'どこかの誰か');
        $message->setTo('someperson@example.jp', 'どこかのどなたか');
        $message->setCc('miku@example.net', 'ミク');
        $message->setSubject('テストメール');
        $message->setBodyText('テストメールの本文');

        return $message;
    }

    /**
     * @param mixed $charset
     * @param mixed $headerCharset
     */
    protected function _getEmailByNewStyleCharset($charset, $headerCharset): Message
    {
        $message = new Message();

        if (!empty($charset)) {
            $message->setCharset($charset);
        }
        if (!empty($headerCharset)) {
            $message->setHeaderCharset($headerCharset);
        }

        $message->setFrom('someone@example.com', 'どこかの誰か');
        $message->setTo('someperson@example.jp', 'どこかのどなたか');
        $message->setCc('miku@example.net', 'ミク');
        $message->setSubject('テストメール');
        $message->setBodyText('テストメールの本文');

        return $message;
    }

    /**
     * @param string $message
     */
    protected function assertLineLengths($message): void
    {
        $lines = explode("\r\n", $message);
        foreach ($lines as $line) {
            $this->assertTrue(
                strlen($line) <= Message::LINE_LENGTH_MUST,
                'Line length exceeds the max. limit of Message::LINE_LENGTH_MUST'
            );
        }
    }

    public function testSerialization(): void
    {
        $message = new Message();

        $message
            ->setSubject('I haz Cake')
            ->setEmailFormat(Message::MESSAGE_BOTH)
            ->setBody([
                Message::MESSAGE_TEXT => 'text message',
                Message::MESSAGE_HTML => '<strong>html message</strong>',
            ]);

        $string = serialize($message);
        $this->assertStringContainsString('text message', $string);

        /** @var \Cake\Mailer\Message $message */
        $message = unserialize($string);
        $this->assertSame('I haz Cake', $message->getSubject());
        $body = $message->getBodyString();
        $this->assertStringContainsString('text message', $body);
        $this->assertStringContainsString('<strong>html message</strong>', $body);
    }
}
