<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Log\Log;
use Cake\Mailer\Email;
use Cake\Mailer\Transport\DebugTransport;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Exception;
use SimpleXmlElement;

/**
 * Help to test Email
 *
 */
class TestEmail extends Email
{

    /**
     * Wrap to protected method
     *
     * @return array
     */
    public function formatAddress($address)
    {
        return parent::_formatAddress($address);
    }

    /**
     * Wrap to protected method
     *
     * @return array
     */
    public function wrap($text, $length = Email::LINE_LENGTH_MUST)
    {
        return parent::_wrap($text, $length);
    }

    /**
     * Get the boundary attribute
     *
     * @return string
     */
    public function getBoundary()
    {
        return $this->_boundary;
    }

    /**
     * Encode to protected method
     *
     * @return string
     */
    public function encode($text)
    {
        return $this->_encode($text);
    }

    /**
     * Decode to protected method
     *
     * @return string
     */
    public function decode($text)
    {
        return $this->_decode($text);
    }

    /**
     * Render to protected method
     *
     * @return array
     */
    public function render($content)
    {
        return $this->_render($content);
    }
}

/**
 * EmailTest class
 */
class EmailTest extends TestCase
{

    public $fixtures = ['core.users'];

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->CakeEmail = new TestEmail();

        $this->transports = [
            'debug' => [
                'className' => 'Debug'
            ]
        ];
        Email::configTransport($this->transports);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Log::drop('email');
        Email::drop('test');
        Email::dropTransport('debug');
        Email::dropTransport('test_smtp');
    }

    /**
     * testFrom method
     *
     * @return void
     */
    public function testFrom()
    {
        $this->assertSame([], $this->CakeEmail->from());

        $this->CakeEmail->from('cake@cakephp.org');
        $expected = ['cake@cakephp.org' => 'cake@cakephp.org'];
        $this->assertSame($expected, $this->CakeEmail->from());

        $this->CakeEmail->from(['cake@cakephp.org']);
        $this->assertSame($expected, $this->CakeEmail->from());

        $this->CakeEmail->from('cake@cakephp.org', 'CakePHP');
        $expected = ['cake@cakephp.org' => 'CakePHP'];
        $this->assertSame($expected, $this->CakeEmail->from());

        $result = $this->CakeEmail->from(['cake@cakephp.org' => 'CakePHP']);
        $this->assertSame($expected, $this->CakeEmail->from());
        $this->assertSame($this->CakeEmail, $result);

        $this->setExpectedException('InvalidArgumentException');
        $result = $this->CakeEmail->from(['cake@cakephp.org' => 'CakePHP', 'fail@cakephp.org' => 'From can only be one address']);
    }

    /**
     * Test that from addresses using colons work.
     *
     * @return void
     */
    public function testFromWithColonsAndQuotes()
    {
        $address = [
            'info@example.com' => '70:20:00 " Forum'
        ];
        $this->CakeEmail->from($address);
        $this->assertEquals($address, $this->CakeEmail->from());
        $this->CakeEmail->to('info@example.com')
            ->subject('Test email')
            ->transport('debug');

        $result = $this->CakeEmail->send();
        $this->assertContains('From: "70:20:00 \" Forum" <info@example.com>', $result['headers']);
    }

    /**
     * testSender method
     *
     * @return void
     */
    public function testSender()
    {
        $this->CakeEmail->reset();
        $this->assertSame([], $this->CakeEmail->sender());

        $this->CakeEmail->sender('cake@cakephp.org', 'Name');
        $expected = ['cake@cakephp.org' => 'Name'];
        $this->assertSame($expected, $this->CakeEmail->sender());

        $headers = $this->CakeEmail->getHeaders(['from' => true, 'sender' => true]);
        $this->assertFalse($headers['From']);
        $this->assertSame('Name <cake@cakephp.org>', $headers['Sender']);

        $this->CakeEmail->from('cake@cakephp.org', 'CakePHP');
        $headers = $this->CakeEmail->getHeaders(['from' => true, 'sender' => true]);
        $this->assertSame('CakePHP <cake@cakephp.org>', $headers['From']);
        $this->assertSame('', $headers['Sender']);
    }

    /**
     * testTo method
     *
     * @return void
     */
    public function testTo()
    {
        $this->assertSame([], $this->CakeEmail->to());

        $result = $this->CakeEmail->to('cake@cakephp.org');
        $expected = ['cake@cakephp.org' => 'cake@cakephp.org'];
        $this->assertSame($expected, $this->CakeEmail->to());
        $this->assertSame($this->CakeEmail, $result);

        $this->CakeEmail->to('cake@cakephp.org', 'CakePHP');
        $expected = ['cake@cakephp.org' => 'CakePHP'];
        $this->assertSame($expected, $this->CakeEmail->to());

        $list = [
            'root@localhost' => 'root',
            'bjørn@hammeröath.com' => 'Bjorn',
            'cake.php@cakephp.org' => 'Cake PHP',
            'cake-php@googlegroups.com' => 'Cake Groups',
            'root@cakephp.org'
        ];
        $this->CakeEmail->to($list);
        $expected = [
            'root@localhost' => 'root',
            'bjørn@hammeröath.com' => 'Bjorn',
            'cake.php@cakephp.org' => 'Cake PHP',
            'cake-php@googlegroups.com' => 'Cake Groups',
            'root@cakephp.org' => 'root@cakephp.org'
        ];
        $this->assertSame($expected, $this->CakeEmail->to());

        $this->CakeEmail->addTo('jrbasso@cakephp.org');
        $this->CakeEmail->addTo('mark_story@cakephp.org', 'Mark Story');
        $this->CakeEmail->addTo('foobar@ætdcadsl.dk');
        $result = $this->CakeEmail->addTo(['phpnut@cakephp.org' => 'PhpNut', 'jose_zap@cakephp.org']);
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
            'jose_zap@cakephp.org' => 'jose_zap@cakephp.org'
        ];
        $this->assertSame($expected, $this->CakeEmail->to());
        $this->assertSame($this->CakeEmail, $result);
    }

    /**
     * Data provider function for testBuildInvalidData
     *
     * @return array
     */
    public static function invalidEmails()
    {
        return [
            [1.0],
            [''],
            ['string'],
            ['<tag>'],
            [['ok@cakephp.org', 1.0, '', 'string']]
        ];
    }

    /**
     * testBuildInvalidData
     *
     * @dataProvider invalidEmails
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testInvalidEmail($value)
    {
        $this->CakeEmail->to($value);
    }

    /**
     * testBuildInvalidData
     *
     * @dataProvider invalidEmails
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testInvalidEmailAdd($value)
    {
        $this->CakeEmail->addTo($value);
    }

    /**
     * test emailPattern method
     *
     * @return void
     */
    public function testEmailPattern()
    {
        $regex = '/.+@.+\..+/i';
        $this->assertSame($regex, $this->CakeEmail->emailPattern($regex)->emailPattern());
    }

    /**
     * Tests that it is possible to set email regex configuration to a CakeEmail object
     *
     * @return void
     */
    public function testConfigEmailPattern()
    {
        $regex = '/.+@.+\..+/i';
        $email = new Email(['emailPattern' => $regex]);
        $this->assertSame($regex, $email->emailPattern());
    }

    /**
     * Tests that it is possible set custom email validation
     *
     * @return void
     */
    public function testCustomEmailValidation()
    {
        $regex = '/^[\.a-z0-9!#$%&\'*+\/=?^_`{|}~-]+@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]{2,6}$/i';

        $this->CakeEmail->emailPattern($regex)->to('pass.@example.com');
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
        ], $this->CakeEmail->to());

        $this->CakeEmail->addTo('pass..old.docomo@example.com');
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
            'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
        ], $this->CakeEmail->to());

        $this->CakeEmail->reset();
        $emails = [
            'pass.@example.com',
            'pass..old.docomo@example.com'
        ];
        $additionalEmails = [
            '.extend.@example.com',
            '.docomo@example.com'
        ];
        $this->CakeEmail->emailPattern($regex)->to($emails);
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
            'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
        ], $this->CakeEmail->to());

        $this->CakeEmail->addTo($additionalEmails);
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
            'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
            '.extend.@example.com' => '.extend.@example.com',
            '.docomo@example.com' => '.docomo@example.com',
        ], $this->CakeEmail->to());
    }

    /**
     * Tests that it is possible to unset the email pattern and make use of filter_var() instead.
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid email: "fail.@example.com"
     */
    public function testUnsetEmailPattern()
    {
        $email = new Email();
        $this->assertSame(Email::EMAIL_PATTERN, $email->emailPattern());

        $email->emailPattern(null);
        $this->assertNull($email->emailPattern());

        $email->to('pass@example.com');
        $email->to('fail.@example.com');
    }

    /**
     * testFormatAddress method
     *
     * @return void
     */
    public function testFormatAddress()
    {
        $result = $this->CakeEmail->formatAddress(['cake@cakephp.org' => 'cake@cakephp.org']);
        $expected = ['cake@cakephp.org'];
        $this->assertSame($expected, $result);

        $result = $this->CakeEmail->formatAddress(['cake@cakephp.org' => 'cake@cakephp.org', 'php@cakephp.org' => 'php@cakephp.org']);
        $expected = ['cake@cakephp.org', 'php@cakephp.org'];
        $this->assertSame($expected, $result);

        $result = $this->CakeEmail->formatAddress(['cake@cakephp.org' => 'CakePHP', 'php@cakephp.org' => 'Cake']);
        $expected = ['CakePHP <cake@cakephp.org>', 'Cake <php@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->CakeEmail->formatAddress(['me@example.com' => 'Last, First']);
        $expected = ['"Last, First" <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->CakeEmail->formatAddress(['me@example.com' => '"Last" First']);
        $expected = ['"\"Last\" First" <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->CakeEmail->formatAddress(['me@example.com' => 'Last First']);
        $expected = ['Last First <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->CakeEmail->formatAddress(['cake@cakephp.org' => 'ÄÖÜTest']);
        $expected = ['=?UTF-8?B?w4TDlsOcVGVzdA==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->CakeEmail->formatAddress(['cake@cakephp.org' => '日本語Test']);
        $expected = ['=?UTF-8?B?5pel5pys6KqeVGVzdA==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);
    }

    /**
     * testFormatAddressJapanese
     *
     * @return void
     */
    public function testFormatAddressJapanese()
    {
        $this->CakeEmail->headerCharset = 'ISO-2022-JP';
        $result = $this->CakeEmail->formatAddress(['cake@cakephp.org' => '日本語Test']);
        $expected = ['=?ISO-2022-JP?B?GyRCRnxLXDhsGyhCVGVzdA==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->CakeEmail->formatAddress(['cake@cakephp.org' => '寿限無寿限無五劫の擦り切れ海砂利水魚の水行末雲来末風来末食う寝る処に住む処やぶら小路の藪柑子パイポパイポパイポのシューリンガンシューリンガンのグーリンダイグーリンダイのポンポコピーのポンポコナーの長久命の長助']);
        $expected = ["=?ISO-2022-JP?B?GyRCPHc4Qkw1PHc4Qkw1OF45ZSROOyQkakBaJGwzJDo9TXg/ZTV7GyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCJE4/ZTlUS3YxQE1oS3ZJd01oS3Y/KSQmPzIkaz1oJEs9OyRgGyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCPWgkZCRWJGk+Lk8pJE5pLjQ7O1IlUSUkJV0lUSUkJV0lUSUkGyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCJV0kTiU3JWUhPCVqJXMlLCVzJTclZSE8JWolcyUsJXMkTiUwGyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCITwlaiVzJUAlJCUwITwlaiVzJUAlJCROJV0lcyVdJTMlVCE8GyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCJE4lXSVzJV0lMyVKITwkTkQ5NVdMPyRORDk9dRsoQg==?= <cake@cakephp.org>"];
        $this->assertSame($expected, $result);
    }

    /**
     * testAddresses method
     *
     * @return void
     */
    public function testAddresses()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->from('cake@cakephp.org', 'CakePHP');
        $this->CakeEmail->replyTo('replyto@cakephp.org', 'ReplyTo CakePHP');
        $this->CakeEmail->readReceipt('readreceipt@cakephp.org', 'ReadReceipt CakePHP');
        $this->CakeEmail->returnPath('returnpath@cakephp.org', 'ReturnPath CakePHP');
        $this->CakeEmail->to('to@cakephp.org', 'To, CakePHP');
        $this->CakeEmail->cc('cc@cakephp.org', 'Cc CakePHP');
        $this->CakeEmail->bcc('bcc@cakephp.org', 'Bcc CakePHP');
        $this->CakeEmail->addTo('to2@cakephp.org', 'To2 CakePHP');
        $this->CakeEmail->addCc('cc2@cakephp.org', 'Cc2 CakePHP');
        $this->CakeEmail->addBcc('bcc2@cakephp.org', 'Bcc2 CakePHP');

        $this->assertSame($this->CakeEmail->from(), ['cake@cakephp.org' => 'CakePHP']);
        $this->assertSame($this->CakeEmail->replyTo(), ['replyto@cakephp.org' => 'ReplyTo CakePHP']);
        $this->assertSame($this->CakeEmail->readReceipt(), ['readreceipt@cakephp.org' => 'ReadReceipt CakePHP']);
        $this->assertSame($this->CakeEmail->returnPath(), ['returnpath@cakephp.org' => 'ReturnPath CakePHP']);
        $this->assertSame($this->CakeEmail->to(), ['to@cakephp.org' => 'To, CakePHP', 'to2@cakephp.org' => 'To2 CakePHP']);
        $this->assertSame($this->CakeEmail->cc(), ['cc@cakephp.org' => 'Cc CakePHP', 'cc2@cakephp.org' => 'Cc2 CakePHP']);
        $this->assertSame($this->CakeEmail->bcc(), ['bcc@cakephp.org' => 'Bcc CakePHP', 'bcc2@cakephp.org' => 'Bcc2 CakePHP']);

        $headers = $this->CakeEmail->getHeaders(array_fill_keys(['from', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc'], true));
        $this->assertSame($headers['From'], 'CakePHP <cake@cakephp.org>');
        $this->assertSame($headers['Reply-To'], 'ReplyTo CakePHP <replyto@cakephp.org>');
        $this->assertSame($headers['Disposition-Notification-To'], 'ReadReceipt CakePHP <readreceipt@cakephp.org>');
        $this->assertSame($headers['Return-Path'], 'ReturnPath CakePHP <returnpath@cakephp.org>');
        $this->assertSame($headers['To'], '"To, CakePHP" <to@cakephp.org>, To2 CakePHP <to2@cakephp.org>');
        $this->assertSame($headers['Cc'], 'Cc CakePHP <cc@cakephp.org>, Cc2 CakePHP <cc2@cakephp.org>');
        $this->assertSame($headers['Bcc'], 'Bcc CakePHP <bcc@cakephp.org>, Bcc2 CakePHP <bcc2@cakephp.org>');
    }

    /**
     * testMessageId method
     *
     * @return void
     */
    public function testMessageId()
    {
        $this->CakeEmail->messageId(true);
        $result = $this->CakeEmail->getHeaders();
        $this->assertTrue(isset($result['Message-ID']));

        $this->CakeEmail->messageId(false);
        $result = $this->CakeEmail->getHeaders();
        $this->assertFalse(isset($result['Message-ID']));

        $result = $this->CakeEmail->messageId('<my-email@localhost>');
        $this->assertSame($this->CakeEmail, $result);
        $result = $this->CakeEmail->getHeaders();
        $this->assertSame('<my-email@localhost>', $result['Message-ID']);

        $result = $this->CakeEmail->messageId();
        $this->assertSame('<my-email@localhost>', $result);
    }

    /**
     * testMessageIdInvalid method
     *
     * @return void
     * @expectedException \InvalidArgumentException
     */
    public function testMessageIdInvalid()
    {
        $this->CakeEmail->messageId('my-email@localhost');
    }

    /**
     * testDomain method
     *
     * @return void
     */
    public function testDomain()
    {
        $result = $this->CakeEmail->domain();
        $expected = env('HTTP_HOST') ? env('HTTP_HOST') : php_uname('n');
        $this->assertSame($expected, $result);

        $this->CakeEmail->domain('example.org');
        $result = $this->CakeEmail->domain();
        $expected = 'example.org';
        $this->assertSame($expected, $result);
    }

    /**
     * testMessageIdWithDomain method
     *
     * @return void
     */
    public function testMessageIdWithDomain()
    {
        $this->CakeEmail->domain('example.org');
        $result = $this->CakeEmail->getHeaders();
        $expected = '@example.org>';
        $this->assertTextContains($expected, $result['Message-ID']);

        $_SERVER['HTTP_HOST'] = 'example.org';
        $result = $this->CakeEmail->getHeaders();
        $this->assertTextContains('example.org', $result['Message-ID']);

        $_SERVER['HTTP_HOST'] = 'example.org:81';
        $result = $this->CakeEmail->getHeaders();
        $this->assertTextNotContains(':81', $result['Message-ID']);
    }

    /**
     * testSubject method
     *
     * @return void
     */
    public function testSubject()
    {
        $this->CakeEmail->subject('You have a new message.');
        $this->assertSame('You have a new message.', $this->CakeEmail->subject());

        $this->CakeEmail->subject('You have a new message, I think.');
        $this->assertSame($this->CakeEmail->subject(), 'You have a new message, I think.');
        $this->CakeEmail->subject(1);
        $this->assertSame('1', $this->CakeEmail->subject());

        $input = 'هذه رسالة بعنوان طويل مرسل للمستلم';
        $this->CakeEmail->subject($input);
        $expected = '=?UTF-8?B?2YfYsNmHINix2LPYp9mE2Kkg2KjYudmG2YjYp9mGINi32YjZitmEINmF2LE=?=' . "\r\n" . ' =?UTF-8?B?2LPZhCDZhNmE2YXYs9iq2YTZhQ==?=';
        $this->assertSame($expected, $this->CakeEmail->subject());
        $this->assertSame($input, $this->CakeEmail->subject(null, true));
    }

    /**
     * testSubjectJapanese
     *
     * @return void
     */
    public function testSubjectJapanese()
    {
        mb_internal_encoding('UTF-8');

        $this->CakeEmail->headerCharset = 'ISO-2022-JP';
        $this->CakeEmail->subject('日本語のSubjectにも対応するよ');
        $expected = '=?ISO-2022-JP?B?GyRCRnxLXDhsJE4bKEJTdWJqZWN0GyRCJEskYkJQMX4kOSRrJGgbKEI=?=';
        $this->assertSame($expected, $this->CakeEmail->subject());

        $this->CakeEmail->subject('長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？');
        $expected = "=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
            " =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=";
        $this->assertSame($expected, $this->CakeEmail->subject());
    }

    /**
     * testHeaders method
     *
     * @return void
     */
    public function testHeaders()
    {
        $this->CakeEmail->messageId(false);
        $this->CakeEmail->setHeaders(['X-Something' => 'nice']);
        $expected = [
            'X-Something' => 'nice',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit'
        ];
        $this->assertSame($expected, $this->CakeEmail->getHeaders());

        $this->CakeEmail->addHeaders(['X-Something' => 'very nice', 'X-Other' => 'cool']);
        $expected = [
            'X-Something' => 'very nice',
            'X-Other' => 'cool',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit'
        ];
        $this->assertSame($expected, $this->CakeEmail->getHeaders());

        $this->CakeEmail->from('cake@cakephp.org');
        $this->assertSame($expected, $this->CakeEmail->getHeaders());

        $expected = [
            'From' => 'cake@cakephp.org',
            'X-Something' => 'very nice',
            'X-Other' => 'cool',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit'
        ];
        $this->assertSame($expected, $this->CakeEmail->getHeaders(['from' => true]));

        $this->CakeEmail->from('cake@cakephp.org', 'CakePHP');
        $expected['From'] = 'CakePHP <cake@cakephp.org>';
        $this->assertSame($expected, $this->CakeEmail->getHeaders(['from' => true]));

        $this->CakeEmail->to(['cake@cakephp.org', 'php@cakephp.org' => 'CakePHP']);
        $expected = [
            'From' => 'CakePHP <cake@cakephp.org>',
            'To' => 'cake@cakephp.org, CakePHP <php@cakephp.org>',
            'X-Something' => 'very nice',
            'X-Other' => 'cool',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit'
        ];
        $this->assertSame($expected, $this->CakeEmail->getHeaders(['from' => true, 'to' => true]));

        $this->CakeEmail->charset = 'ISO-2022-JP';
        $expected = [
            'From' => 'CakePHP <cake@cakephp.org>',
            'To' => 'cake@cakephp.org, CakePHP <php@cakephp.org>',
            'X-Something' => 'very nice',
            'X-Other' => 'cool',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=ISO-2022-JP',
            'Content-Transfer-Encoding' => '7bit'
        ];
        $this->assertSame($expected, $this->CakeEmail->getHeaders(['from' => true, 'to' => true]));

        $result = $this->CakeEmail->setHeaders([]);
        $this->assertInstanceOf('Cake\Mailer\Email', $result);
    }

    /**
     * testTemplate method
     *
     * @return void
     */
    public function testTemplate()
    {
        $this->CakeEmail->template('template', 'layout');
        $expected = ['template' => 'template', 'layout' => 'layout'];
        $this->assertSame($expected, $this->CakeEmail->template());

        $this->CakeEmail->template('new_template');
        $expected = ['template' => 'new_template', 'layout' => 'layout'];
        $this->assertSame($expected, $this->CakeEmail->template());

        $this->CakeEmail->template('template', null);
        $expected = ['template' => 'template', 'layout' => false];
        $this->assertSame($expected, $this->CakeEmail->template());

        $this->CakeEmail->template(null, null);
        $expected = ['template' => '', 'layout' => false];
        $this->assertSame($expected, $this->CakeEmail->template());
    }

    /**
     * testTheme method
     *
     * @return void
     */
    public function testTheme()
    {
        $this->assertNull($this->CakeEmail->theme());

        $this->CakeEmail->theme('default');
        $expected = 'default';
        $this->assertSame($expected, $this->CakeEmail->theme());
    }

    /**
     * testViewVars method
     *
     * @return void
     */
    public function testViewVars()
    {
        $this->assertSame([], $this->CakeEmail->viewVars());

        $this->CakeEmail->viewVars(['value' => 12345]);
        $this->assertSame(['value' => 12345], $this->CakeEmail->viewVars());

        $this->CakeEmail->viewVars(['name' => 'CakePHP']);
        $this->assertEquals(['value' => 12345, 'name' => 'CakePHP'], $this->CakeEmail->viewVars());

        $this->CakeEmail->viewVars(['value' => 4567]);
        $this->assertSame(['value' => 4567, 'name' => 'CakePHP'], $this->CakeEmail->viewVars());
    }

    /**
     * testAttachments method
     *
     * @return void
     */
    public function testAttachments()
    {
        $this->CakeEmail->attachments(CAKE . 'basics.php');
        $expected = [
            'basics.php' => [
                'file' => CAKE . 'basics.php',
                'mimetype' => 'application/octet-stream'
            ]
        ];
        $this->assertSame($expected, $this->CakeEmail->attachments());

        $this->CakeEmail->attachments([]);
        $this->assertSame([], $this->CakeEmail->attachments());

        $this->CakeEmail->attachments([
            ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain']
        ]);
        $this->CakeEmail->addAttachments(CORE_PATH . 'config' . DS . 'bootstrap.php');
        $this->CakeEmail->addAttachments([CORE_PATH . 'config' . DS . 'bootstrap.php']);
        $this->CakeEmail->addAttachments([
            'other.txt' => CORE_PATH . 'config' . DS . 'bootstrap.php',
            'license' => CORE_PATH . 'LICENSE.txt'
        ]);
        $expected = [
            'basics.php' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain'],
            'bootstrap.php' => ['file' => CORE_PATH . 'config' . DS . 'bootstrap.php', 'mimetype' => 'application/octet-stream'],
            'other.txt' => ['file' => CORE_PATH . 'config' . DS . 'bootstrap.php', 'mimetype' => 'application/octet-stream'],
            'license' => ['file' => CORE_PATH . 'LICENSE.txt', 'mimetype' => 'application/octet-stream']
        ];
        $this->assertSame($expected, $this->CakeEmail->attachments());

        $this->setExpectedException('InvalidArgumentException');
        $this->CakeEmail->attachments([['nofile' => CAKE . 'basics.php', 'mimetype' => 'text/plain']]);
    }

    /**
     * testTransport method
     *
     * @return void
     */
    public function testTransport()
    {
        $result = $this->CakeEmail->transport('debug');
        $this->assertSame($this->CakeEmail, $result);

        $result = $this->CakeEmail->transport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $instance = $this->getMock('Cake\Mailer\Transport\DebugTransport');
        $this->CakeEmail->transport($instance);
        $this->assertSame($instance, $this->CakeEmail->transport());
    }

    /**
     * Test that using unknown transports fails.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Transport config "Invalid" is missing.
     */
    public function testTransportInvalid()
    {
        $this->CakeEmail->transport('Invalid');
    }

    /**
     * Test that using classes with no send method fails.
     *
     * @expectedException \LogicException
     */
    public function testTransportInstanceInvalid()
    {
        $this->CakeEmail->transport(new \StdClass());
    }

    /**
     * Test that using unknown transports fails.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The value passed for the "$name" argument must be either a string, or an object, integer given.
     */
    public function testTransportTypeInvalid()
    {
        $this->CakeEmail->transport(123);
    }

    /**
     * Test that using misconfigured transports fails.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Transport config "debug" is invalid, the required `className` option is missing
     */
    public function testTransportMissingClassName()
    {
        Email::dropTransport('debug');
        Email::configTransport('debug', []);

        $this->CakeEmail->transport('debug');
    }

    /**
     * Test configuring a transport.
     *
     * @return void
     */
    public function testConfigTransport()
    {
        Email::dropTransport('debug');
        $settings = [
            'className' => 'Debug',
            'log' => true
        ];
        $result = Email::configTransport('debug', $settings);
        $this->assertNull($result, 'No return.');

        $result = Email::configTransport('debug');
        $this->assertEquals($settings, $result);
    }

    /**
     * Test configuring multiple transports.
     */
    public function testConfigTransportMultiple()
    {
        Email::dropTransport('debug');
        $settings = [
            'debug' => [
                'className' => 'Debug',
                'log' => true
            ],
            'test_smtp' => [
                'className' => 'Smtp',
                'username' => 'mark',
                'password' => 'password',
                'host' => 'example.com'
            ]
        ];
        Email::configTransport($settings);
        $this->assertEquals($settings['debug'], Email::configTransport('debug'));
        $this->assertEquals($settings['test_smtp'], Email::configTransport('test_smtp'));
    }

    /**
     * Test that exceptions are raised when duplicate transports are configured.
     *
     * @expectedException \BadMethodCallException
     */
    public function testConfigTransportErrorOnDuplicate()
    {
        Email::dropTransport('debug');
        $settings = [
            'className' => 'Debug',
            'log' => true
        ];
        Email::configTransport('debug', $settings);
        Email::configTransport('debug', $settings);
    }

    /**
     * Test configTransport with an instance.
     *
     * @return void
     */
    public function testConfigTransportInstance()
    {
        Email::dropTransport('debug');
        $instance = new DebugTransport();
        Email::configTransport('debug', $instance);
        $this->assertEquals(['className' => $instance], Email::configTransport('debug'));
    }

    /**
     * Test enumerating all transport configurations
     *
     * @return void
     */
    public function testConfiguredTransport()
    {
        $result = Email::configuredTransport();
        $this->assertInternalType('array', $result, 'Should have config keys');
        $this->assertEquals(
            array_keys($this->transports),
            $result,
            'Loaded transports should be present in enumeration.'
        );
    }

    /**
     * Test dropping a transport configuration
     *
     * @return void
     */
    public function testDropTransport()
    {
        $result = Email::configTransport('debug');
        $this->assertInternalType('array', $result, 'Should have config data');
        Email::dropTransport('debug');
        $this->assertNull(Email::configTransport('debug'), 'Should not exist.');
    }

    /**
     * Test reading/writing configuration profiles.
     *
     * @return void
     */
    public function testConfig()
    {
        $settings = [
            'to' => 'mark@example.com',
            'from' => 'noreply@example.com',
        ];
        Email::config('test', $settings);
        $this->assertEquals($settings, Email::config('test'), 'Should be the same.');

        $email = new Email('test');
        $this->assertContains($settings['to'], $email->to());
    }

    /**
     * Test that exceptions are raised on duplicate config set.
     *
     * @expectedException \BadMethodCallException
     * @return void
     */
    public function testConfigErrorOnDuplicate()
    {
        $settings = [
            'to' => 'mark@example.com',
            'from' => 'noreply@example.com',
        ];
        Email::config('test', $settings);
        Email::config('test', $settings);
    }

    /**
     * test profile method
     *
     * @return void
     */
    public function testProfile()
    {
        $config = ['test' => 'ok', 'test2' => true];
        $this->CakeEmail->profile($config);
        $this->assertSame($this->CakeEmail->profile(), $config);

        $config = ['test' => 'test@example.com'];
        $this->CakeEmail->profile($config);
        $expected = ['test' => 'test@example.com', 'test2' => true];
        $this->assertSame($expected, $this->CakeEmail->profile());
    }

    /**
     * test that default profile is used by constructor if available.
     *
     * @return void
     */
    public function testDefaultProfile()
    {
        $config = ['test' => 'ok', 'test2' => true];
        Configure::write('Email.default', $config);
        Email::config(Configure::consume('Email'));
        $Email = new Email();
        $this->assertSame($Email->profile(), $config);
        Configure::delete('Email');
        Email::drop('default');
    }

    /**
     * Test that using an invalid profile fails.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown email configuration "derp".
     */
    public function testProfileInvalid()
    {
        $email = new Email();
        $email->profile('derp');
    }

    /**
     * testConfigString method
     *
     * @return void
     */
    public function testUseConfigString()
    {
        $config = [
            'from' => ['some@example.com' => 'My website'],
            'to' => ['test@example.com' => 'Testname'],
            'subject' => 'Test mail subject',
            'transport' => 'debug',
            'theme' => 'TestTheme',
            'helpers' => ['Html', 'Form'],
        ];
        Email::config('test', $config);
        $this->CakeEmail->profile('test');

        $result = $this->CakeEmail->to();
        $this->assertEquals($config['to'], $result);

        $result = $this->CakeEmail->from();
        $this->assertEquals($config['from'], $result);

        $result = $this->CakeEmail->subject();
        $this->assertEquals($config['subject'], $result);

        $result = $this->CakeEmail->theme();
        $this->assertEquals($config['theme'], $result);

        $result = $this->CakeEmail->transport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $result = $this->CakeEmail->helpers();
        $this->assertEquals($config['helpers'], $result);
    }

    /**
     * Calling send() with no parameters should not overwrite the view variables.
     *
     * @return void
     */
    public function testSendWithNoContentDoesNotOverwriteViewVar()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('you@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->emailFormat('text');
        $this->CakeEmail->template('default');
        $this->CakeEmail->viewVars([
            'content' => 'A message to you',
        ]);

        $result = $this->CakeEmail->send();
        $this->assertContains('A message to you', $result['message']);
    }

    /**
     * testSendWithContent method
     *
     * @return void
     */
    public function testSendWithContent()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);

        $result = $this->CakeEmail->send("Here is my body, with multi lines.\nThis is the second line.\r\n\r\nAnd the last.");
        $expected = ['headers', 'message'];
        $this->assertEquals($expected, array_keys($result));
        $expected = "Here is my body, with multi lines.\r\nThis is the second line.\r\n\r\nAnd the last.\r\n\r\n";

        $this->assertEquals($expected, $result['message']);
        $this->assertTrue((bool)strpos($result['headers'], 'Date: '));
        $this->assertTrue((bool)strpos($result['headers'], 'Message-ID: '));
        $this->assertTrue((bool)strpos($result['headers'], 'To: '));

        $result = $this->CakeEmail->send("Other body");
        $expected = "Other body\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertTrue((bool)strpos($result['headers'], 'Message-ID: '));
        $this->assertTrue((bool)strpos($result['headers'], 'To: '));

        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $result = $this->CakeEmail->send(['Sending content', 'As array']);
        $expected = "Sending content\r\nAs array\r\n\r\n\r\n";
        $this->assertSame($expected, $result['message']);
    }

    /**
     * testSendWithoutFrom method
     *
     * @expectedException \BadMethodCallException
     * @return void
     */
    public function testSendWithoutFrom()
    {
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->send("Forgot to set From");
    }

    /**
     * testSendWithoutTo method
     *
     * @expectedException \BadMethodCallException
     * @return void
     */
    public function testSendWithoutTo()
    {
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->send("Forgot to set To");
    }

    /**
     * test send without a transport method
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Cannot send email, transport was not defined.
     * @return void
     */
    public function testSendWithoutTransport()
    {
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->send("Forgot to set To");
    }

    /**
     * Test send() with no template.
     *
     * @return void
     */
    public function testSendNoTemplateWithAttachments()
    {
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->emailFormat('text');
        $this->CakeEmail->attachments([CAKE . 'basics.php']);
        $result = $this->CakeEmail->send('Hello');

        $boundary = $this->CakeEmail->getBoundary();
        $this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "Hello" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--$boundary\r\n" .
            "Content-Disposition: attachment; filename=\"basics.php\"\r\n" .
            "Content-Type: application/octet-stream\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "\r\n";
        $this->assertContains($expected, $result['message']);
    }

    /**
     * Test send() with no template and data string attachment
     *
     * @return void
     */

    public function testSendNoTemplateWithDataStringAttachment()
    {
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->emailFormat('text');
        $data = file_get_contents(TEST_APP . 'webroot/img/cake.power.gif');
        $this->CakeEmail->attachments(['cake.icon.gif' => [
                'data' => $data,
                'mimetype' => 'image/gif'
        ]]);
        $result = $this->CakeEmail->send('Hello');

        $boundary = $this->CakeEmail->getBoundary();
        $this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
                "Content-Type: text/plain; charset=UTF-8\r\n" .
                "Content-Transfer-Encoding: 8bit\r\n" .
                "\r\n" .
                "Hello" .
                "\r\n" .
                "\r\n" .
                "\r\n" .
                "--$boundary\r\n" .
                "Content-Disposition: attachment; filename=\"cake.icon.gif\"\r\n" .
                "Content-Type: image/gif\r\n" .
                "Content-Transfer-Encoding: base64\r\n\r\n";
        $expected .= chunk_split(base64_encode($data), 76, "\r\n");
        $this->assertContains($expected, $result['message']);
    }

    /**
     * Test send() with no template as both
     *
     * @return void
     */
    public function testSendNoTemplateWithAttachmentsAsBoth()
    {
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->emailFormat('both');
        $this->CakeEmail->attachments([CORE_PATH . 'VERSION.txt']);
        $result = $this->CakeEmail->send('Hello');

        $boundary = $this->CakeEmail->getBoundary();
        $this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: multipart/alternative; boundary=\"alt-$boundary\"\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "Hello" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "Hello" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-{$boundary}--\r\n" .
            "\r\n" .
            "--$boundary\r\n" .
            "Content-Disposition: attachment; filename=\"VERSION.txt\"\r\n" .
            "Content-Type: application/octet-stream\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "\r\n";
        $this->assertContains($expected, $result['message']);
    }

    /**
     * Test setting inline attachments and messages.
     *
     * @return void
     */
    public function testSendWithInlineAttachments()
    {
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->emailFormat('both');
        $this->CakeEmail->attachments([
            'cake.png' => [
                'file' => CORE_PATH . 'VERSION.txt',
                'contentId' => 'abc123'
            ]
        ]);
        $result = $this->CakeEmail->send('Hello');

        $boundary = $this->CakeEmail->getBoundary();
        $this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: multipart/related; boundary=\"rel-$boundary\"\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Type: multipart/alternative; boundary=\"alt-$boundary\"\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "Hello" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "Hello" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-{$boundary}--\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Disposition: inline; filename=\"cake.png\"\r\n" .
            "Content-Type: application/octet-stream\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "Content-ID: <abc123>\r\n" .
            "\r\n";
        $this->assertContains($expected, $result['message']);
        $this->assertContains('--rel-' . $boundary . '--', $result['message']);
        $this->assertContains('--' . $boundary . '--', $result['message']);
    }

    /**
     * Test setting inline attachments and HTML only messages.
     *
     * @return void
     */
    public function testSendWithInlineAttachmentsHtmlOnly()
    {
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->emailFormat('html');
        $this->CakeEmail->attachments([
            'cake.png' => [
                'file' => CORE_PATH . 'VERSION.txt',
                'contentId' => 'abc123'
            ]
        ]);
        $result = $this->CakeEmail->send('Hello');

        $boundary = $this->CakeEmail->getBoundary();
        $this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: multipart/related; boundary=\"rel-$boundary\"\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "Hello" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Disposition: inline; filename=\"cake.png\"\r\n" .
            "Content-Type: application/octet-stream\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "Content-ID: <abc123>\r\n" .
            "\r\n";
        $this->assertContains($expected, $result['message']);
        $this->assertContains('--rel-' . $boundary . '--', $result['message']);
        $this->assertContains('--' . $boundary . '--', $result['message']);
    }

    /**
     * Test disabling content-disposition.
     *
     * @return void
     */
    public function testSendWithNoContentDispositionAttachments()
    {
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->emailFormat('text');
        $this->CakeEmail->attachments([
            'cake.png' => [
                'file' => CORE_PATH . 'VERSION.txt',
                'contentDisposition' => false
            ]
        ]);
        $result = $this->CakeEmail->send('Hello');

        $boundary = $this->CakeEmail->getBoundary();
        $this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "Hello" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--{$boundary}\r\n" .
            "Content-Type: application/octet-stream\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "\r\n";

        $this->assertContains($expected, $result['message']);
        $this->assertContains('--' . $boundary . '--', $result['message']);
    }
    /**
     * testSendWithLog method
     *
     * @return void
     */
    public function testSendWithLog()
    {
        $log = $this->getMock('Cake\Log\Engine\BaseLog', ['log'], [['scopes' => 'email']]);

        $message = 'Logging This';

        $log->expects($this->once())
            ->method('log')
            ->with(
                'debug',
                $this->logicalAnd(
                    $this->stringContains($message),
                    $this->stringContains('cake@cakephp.org'),
                    $this->stringContains('me@cakephp.org')
                )
            );

        Log::config('email', $log);

        $this->CakeEmail->transport('debug');
        $this->CakeEmail->to('me@cakephp.org');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['log' => 'debug']);
        $result = $this->CakeEmail->send($message);
    }

    /**
     * testSendWithLogAndScope method
     *
     * @return void
     */
    public function testSendWithLogAndScope()
    {
        $message = 'Logging This';

        $log = $this->getMock('Cake\Log\Engine\BaseLog', ['log'], ['scopes' => ['email']]);
        $log->expects($this->once())
            ->method('log')
            ->with(
                'debug',
                $this->logicalAnd(
                    $this->stringContains($message),
                    $this->stringContains('cake@cakephp.org'),
                    $this->stringContains('me@cakephp.org')
                )
            );

        Log::config('email', $log);

        $this->CakeEmail->transport('debug');
        $this->CakeEmail->to('me@cakephp.org');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['log' => ['scope' => 'email']]);
        $this->CakeEmail->send($message);
    }

    /**
     * testSendRender method
     *
     * @return void
     */
    public function testSendRender()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->template('default', 'default');
        $result = $this->CakeEmail->send();

        $this->assertContains('This email was sent using the CakePHP Framework', $result['message']);
        $this->assertContains('Message-ID: ', $result['headers']);
        $this->assertContains('To: ', $result['headers']);
    }

    /**
     * test sending and rendering with no layout
     *
     * @return void
     */
    public function testSendRenderNoLayout()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->config(['empty']);
        $this->CakeEmail->template('default', null);
        $result = $this->CakeEmail->send('message body.');

        $this->assertContains('message body.', $result['message']);
        $this->assertNotContains('This email was sent using the CakePHP Framework', $result['message']);
    }

    /**
     * testSendRender both method
     *
     * @return void
     */
    public function testSendRenderBoth()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->template('default', 'default');
        $this->CakeEmail->emailFormat('both');
        $result = $this->CakeEmail->send();

        $this->assertContains('Message-ID: ', $result['headers']);
        $this->assertContains('To: ', $result['headers']);

        $boundary = $this->CakeEmail->getBoundary();
        $this->assertContains('Content-Type: multipart/alternative; boundary="' . $boundary . '"', $result['headers']);

        $expected = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "This email was sent using the CakePHP Framework, http://cakephp.org." .
            "\r\n" .
            "\r\n" .
            "--$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "<!DOCTYPE html";
        $this->assertStringStartsWith($expected, $result['message']);

        $expected = "</html>\r\n" .
            "\r\n" .
            "\r\n" .
            "--$boundary--\r\n";
        $this->assertStringEndsWith($expected, $result['message']);
    }

    /**
     * testSendRender method for ISO-2022-JP
     *
     * @return void
     */
    public function testSendRenderJapanese()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->template('default', 'japanese');
        $this->CakeEmail->charset = 'ISO-2022-JP';
        $result = $this->CakeEmail->send();

        $expected = mb_convert_encoding('CakePHP Framework を使って送信したメールです。 http://cakephp.org.', 'ISO-2022-JP');
        $this->assertContains($expected, $result['message']);
        $this->assertContains('Message-ID: ', $result['headers']);
        $this->assertContains('To: ', $result['headers']);
    }

    /**
     * testSendRenderThemed method
     *
     * @return void
     */
    public function testSendRenderThemed()
    {
        Plugin::load('TestTheme');
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->theme('TestTheme');
        $this->CakeEmail->template('themed', 'default');
        $result = $this->CakeEmail->send();

        $this->assertContains('In TestTheme', $result['message']);
        $this->assertContains('/test_theme/img/test.jpg', $result['message']);
        $this->assertContains('Message-ID: ', $result['headers']);
        $this->assertContains('To: ', $result['headers']);
        $this->assertContains('/test_theme/img/test.jpg', $result['message']);
    }

    /**
     * testSendRenderWithHTML method and assert line length is kept below the required limit
     *
     * @return void
     */
    public function testSendRenderWithHTML()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->emailFormat('html');
        $this->CakeEmail->template('html', 'default');
        $result = $this->CakeEmail->send();

        $this->assertTextContains('<h1>HTML Ipsum Presents</h1>', $result['message']);
        $this->assertLineLengths($result['message']);
    }

    /**
     * testSendRenderWithVars method
     *
     * @return void
     */
    public function testSendRenderWithVars()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->template('custom', 'default');
        $this->CakeEmail->viewVars(['value' => 12345]);
        $result = $this->CakeEmail->send();

        $this->assertContains('Here is your value: 12345', $result['message']);
    }

    /**
     * testSendRenderWithVars method for ISO-2022-JP
     *
     * @return void
     */
    public function testSendRenderWithVarsJapanese()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->template('japanese', 'default');
        $this->CakeEmail->viewVars(['value' => '日本語の差し込み123']);
        $this->CakeEmail->charset = 'ISO-2022-JP';
        $result = $this->CakeEmail->send();

        $expected = mb_convert_encoding('ここにあなたの設定した値が入ります: 日本語の差し込み123', 'ISO-2022-JP');
        $this->assertTrue((bool)strpos($result['message'], $expected));
    }

    /**
     * testSendRenderWithHelpers method
     *
     * @return void
     */
    public function testSendRenderWithHelpers()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $timestamp = time();
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->template('custom_helper', 'default');
        $this->CakeEmail->viewVars(['time' => $timestamp]);

        $result = $this->CakeEmail->helpers(['Time']);
        $this->assertInstanceOf('Cake\Mailer\Email', $result);

        $result = $this->CakeEmail->send();
        $dateTime = new \DateTime;
        $dateTime->setTimestamp($timestamp);
        $this->assertTrue((bool)strpos($result['message'], 'Right now: ' . $dateTime->format($dateTime::ATOM)));

        $result = $this->CakeEmail->helpers();
        $this->assertEquals(['Time'], $result);
    }

    /**
     * testSendRenderWithImage method
     *
     * @return void
     */
    public function testSendRenderWithImage()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->template('image');
        $this->CakeEmail->emailFormat('html');
        $server = env('SERVER_NAME') ? env('SERVER_NAME') : 'localhost';

        if (env('SERVER_PORT') && env('SERVER_PORT') != 80) {
            $server .= ':' . env('SERVER_PORT');
        }

        $expected = '<img src="http://' . $server . '/img/image.gif" alt="cool image" width="100" height="100"';
        $result = $this->CakeEmail->send();
        $this->assertContains($expected, $result['message']);
    }

    /**
     * testSendRenderPlugin method
     *
     * @return void
     */
    public function testSendRenderPlugin()
    {
        Plugin::load(['TestPlugin', 'TestPluginTwo', 'TestTheme']);

        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);

        $result = $this->CakeEmail->template('TestPlugin.test_plugin_tpl', 'default')->send();
        $this->assertContains('Into TestPlugin.', $result['message']);
        $this->assertContains('This email was sent using the CakePHP Framework', $result['message']);

        $result = $this->CakeEmail->template('TestPlugin.test_plugin_tpl', 'TestPlugin.plug_default')->send();
        $this->assertContains('Into TestPlugin.', $result['message']);
        $this->assertContains('This email was sent using the TestPlugin.', $result['message']);

        $result = $this->CakeEmail->template('TestPlugin.test_plugin_tpl', 'plug_default')->send();
        $this->assertContains('Into TestPlugin.', $result['message']);
        $this->assertContains('This email was sent using the TestPlugin.', $result['message']);

        $this->CakeEmail->template(
            'TestPlugin.test_plugin_tpl',
            'TestPluginTwo.default'
        );
        $result = $this->CakeEmail->send();
        $this->assertContains('Into TestPlugin.', $result['message']);
        $this->assertContains('This email was sent using TestPluginTwo.', $result['message']);

        // test plugin template overridden by theme
        $this->CakeEmail->theme('TestTheme');
        $result = $this->CakeEmail->send();

        $this->assertContains('Into TestPlugin. (themed)', $result['message']);

        $this->CakeEmail->viewVars(['value' => 12345]);
        $result = $this->CakeEmail->template('custom', 'TestPlugin.plug_default')->send();
        $this->assertContains('Here is your value: 12345', $result['message']);
        $this->assertContains('This email was sent using the TestPlugin.', $result['message']);

        $this->setExpectedException('Cake\View\Exception\MissingTemplateException');
        $this->CakeEmail->template('test_plugin_tpl', 'plug_default')->send();
    }

    /**
     * testSendMultipleMIME method
     *
     * @return void
     */
    public function testSendMultipleMIME()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');

        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->template('custom', 'default');
        $this->CakeEmail->profile([]);
        $this->CakeEmail->viewVars(['value' => 12345]);
        $this->CakeEmail->emailFormat('both');
        $this->CakeEmail->send();

        $message = $this->CakeEmail->message();
        $boundary = $this->CakeEmail->getBoundary();
        $this->assertFalse(empty($boundary));
        $this->assertContains('--' . $boundary, $message);
        $this->assertContains('--' . $boundary . '--', $message);

        $this->CakeEmail->attachments(['fake.php' => __FILE__]);
        $this->CakeEmail->send();

        $message = $this->CakeEmail->message();
        $boundary = $this->CakeEmail->getBoundary();
        $this->assertFalse(empty($boundary));
        $this->assertContains('--' . $boundary, $message);
        $this->assertContains('--' . $boundary . '--', $message);
        $this->assertContains('--alt-' . $boundary, $message);
        $this->assertContains('--alt-' . $boundary . '--', $message);
    }

    /**
     * testSendAttachment method
     *
     * @return void
     */
    public function testSendAttachment()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile([]);
        $this->CakeEmail->attachments([CAKE . 'basics.php']);
        $result = $this->CakeEmail->send('body');
        $expected = "Content-Disposition: attachment; filename=\"basics.php\"\r\n" .
            "Content-Type: application/octet-stream\r\n" .
            "Content-Transfer-Encoding: base64\r\n";
        $this->assertContains($expected, $result['message']);

        $this->CakeEmail->attachments(['my.file.txt' => CAKE . 'basics.php']);
        $result = $this->CakeEmail->send('body');
        $expected = "Content-Disposition: attachment; filename=\"my.file.txt\"\r\n" .
            "Content-Type: application/octet-stream\r\n" .
            "Content-Transfer-Encoding: base64\r\n";
        $this->assertContains($expected, $result['message']);

        $this->CakeEmail->attachments(['file.txt' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain']]);
        $result = $this->CakeEmail->send('body');
        $expected = "Content-Disposition: attachment; filename=\"file.txt\"\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n";
        $this->assertContains($expected, $result['message']);

        $this->CakeEmail->attachments(['file2.txt' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain', 'contentId' => 'a1b1c1']]);
        $result = $this->CakeEmail->send('body');
        $expected = "Content-Disposition: inline; filename=\"file2.txt\"\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "Content-ID: <a1b1c1>\r\n";
        $this->assertContains($expected, $result['message']);
    }

    /**
     * testDeliver method
     *
     * @return void
     */
    public function testDeliver()
    {
        $instance = Email::deliver('all@cakephp.org', 'About', 'Everything ok', ['from' => 'root@cakephp.org'], false);
        $this->assertInstanceOf('Cake\Mailer\Email', $instance);
        $this->assertSame($instance->to(), ['all@cakephp.org' => 'all@cakephp.org']);
        $this->assertSame($instance->subject(), 'About');
        $this->assertSame($instance->from(), ['root@cakephp.org' => 'root@cakephp.org']);

        $config = [
            'from' => 'cake@cakephp.org',
            'to' => 'debug@cakephp.org',
            'subject' => 'Update ok',
            'template' => 'custom',
            'layout' => 'custom_layout',
            'viewVars' => ['value' => 123],
            'cc' => ['cake@cakephp.org' => 'Myself']
        ];
        $instance = Email::deliver(null, null, ['name' => 'CakePHP'], $config, false);
        $this->assertSame($instance->from(), ['cake@cakephp.org' => 'cake@cakephp.org']);
        $this->assertSame($instance->to(), ['debug@cakephp.org' => 'debug@cakephp.org']);
        $this->assertSame($instance->subject(), 'Update ok');
        $this->assertSame($instance->template(), ['template' => 'custom', 'layout' => 'custom_layout']);
        $this->assertEquals($instance->viewVars(), ['value' => 123, 'name' => 'CakePHP']);
        $this->assertSame($instance->cc(), ['cake@cakephp.org' => 'Myself']);

        $configs = ['from' => 'root@cakephp.org', 'message' => 'Message from configs', 'transport' => 'debug'];
        $instance = Email::deliver('all@cakephp.org', 'About', null, $configs, true);
        $message = $instance->message();
        $this->assertEquals($configs['message'], $message[0]);
    }

    /**
     * testMessage method
     *
     * @return void
     */
    public function testMessage()
    {
        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to(['you@cakephp.org' => 'You']);
        $this->CakeEmail->subject('My title');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->template('default', 'default');
        $this->CakeEmail->emailFormat('both');
        $this->CakeEmail->send();

        $expected = '<p>This email was sent using the <a href="http://cakephp.org">CakePHP Framework</a></p>';
        $this->assertContains($expected, $this->CakeEmail->message(Email::MESSAGE_HTML));

        $expected = 'This email was sent using the CakePHP Framework, http://cakephp.org.';
        $this->assertContains($expected, $this->CakeEmail->message(Email::MESSAGE_TEXT));

        $message = $this->CakeEmail->message();
        $this->assertContains('Content-Type: text/plain; charset=UTF-8', $message);
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $message);

        // UTF-8 is 8bit
        $this->assertTrue($this->_checkContentTransferEncoding($message, '8bit'));

        $this->CakeEmail->charset = 'ISO-2022-JP';
        $this->CakeEmail->send();
        $message = $this->CakeEmail->message();
        $this->assertContains('Content-Type: text/plain; charset=ISO-2022-JP', $message);
        $this->assertContains('Content-Type: text/html; charset=ISO-2022-JP', $message);

        // ISO-2022-JP is 7bit
        $this->assertTrue($this->_checkContentTransferEncoding($message, '7bit'));
    }

    /**
     * testReset method
     *
     * @return void
     */
    public function testReset()
    {
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->theme('TestTheme');
        $this->CakeEmail->emailPattern('/.+@.+\..+/i');
        $this->assertSame(['cake@cakephp.org' => 'cake@cakephp.org'], $this->CakeEmail->to());

        $this->CakeEmail->reset();
        $this->assertSame([], $this->CakeEmail->to());
        $this->assertFalse($this->CakeEmail->theme());
        $this->assertSame(Email::EMAIL_PATTERN, $this->CakeEmail->emailPattern());
    }

    /**
     * testReset with charset
     *
     * @return void
     */
    public function testResetWithCharset()
    {
        $this->CakeEmail->charset = 'ISO-2022-JP';
        $this->CakeEmail->reset();

        $this->assertSame('utf-8', $this->CakeEmail->charset, $this->CakeEmail->charset);
        $this->assertNull($this->CakeEmail->headerCharset, $this->CakeEmail->headerCharset);
    }

    /**
     * testWrap method
     *
     * @return void
     */
    public function testWrap()
    {
        $text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
        $result = $this->CakeEmail->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci,',
            'non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.',
            ''
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan amet.';
        $result = $this->CakeEmail->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis',
            'orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan',
            'amet.',
            ''
        ];
        $this->assertSame($expected, $result);

        $text = '<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula pellentesque accumsan amet.<hr></p>';
        $result = $this->CakeEmail->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            '<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac',
            'turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula',
            'pellentesque accumsan amet.<hr></p>',
            ''
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac <a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
        $result = $this->CakeEmail->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac',
            '<a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh',
            'nisi, vehicula pellentesque accumsan amet.',
            ''
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum <a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">ok</a>';
        $result = $this->CakeEmail->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum',
            '<a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">',
            'ok</a>',
            ''
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite ok.';
        $result = $this->CakeEmail->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum',
            'withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite',
            'ok.',
            ''
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * testRender method
     *
     * @return void
     */
    public function testRenderWithLayoutAndAttachment()
    {
        $this->CakeEmail->emailFormat('html');
        $this->CakeEmail->template('html', 'default');
        $this->CakeEmail->attachments([CAKE . 'basics.php']);
        $result = $this->CakeEmail->render([]);
        $this->assertNotEmpty($result);

        $result = $this->CakeEmail->getBoundary();
        $this->assertNotEmpty($result);
    }

    /**
     * testConstructWithConfigArray method
     *
     * @return void
     */
    public function testConstructWithConfigArray()
    {
        $configs = [
            'from' => ['some@example.com' => 'My website'],
            'to' => 'test@example.com',
            'subject' => 'Test mail subject',
            'transport' => 'debug',
        ];
        $this->CakeEmail = new Email($configs);

        $result = $this->CakeEmail->to();
        $this->assertEquals([$configs['to'] => $configs['to']], $result);

        $result = $this->CakeEmail->from();
        $this->assertEquals($configs['from'], $result);

        $result = $this->CakeEmail->subject();
        $this->assertEquals($configs['subject'], $result);

        $result = $this->CakeEmail->transport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $result = $this->CakeEmail->send('This is the message');

        $this->assertTrue((bool)strpos($result['headers'], 'Message-ID: '));
        $this->assertTrue((bool)strpos($result['headers'], 'To: '));
    }

    /**
     * testConfigArrayWithLayoutWithoutTemplate method
     *
     * @return void
     */
    public function testConfigArrayWithLayoutWithoutTemplate()
    {
        $configs = [
            'from' => ['some@example.com' => 'My website'],
            'to' => 'test@example.com',
            'subject' => 'Test mail subject',
            'transport' => 'debug',
            'layout' => 'custom'
        ];
        $this->CakeEmail = new Email($configs);

        $result = $this->CakeEmail->template();
        $this->assertEquals('', $result['template']);
        $this->assertEquals($configs['layout'], $result['layout']);
    }

    /**
     * testConstructWithConfigString method
     *
     * @return void
     */
    public function testConstructWithConfigString()
    {
        $configs = [
            'from' => ['some@example.com' => 'My website'],
            'to' => 'test@example.com',
            'subject' => 'Test mail subject',
            'transport' => 'debug',
        ];
        Email::config('test', $configs);

        $this->CakeEmail = new Email('test');

        $result = $this->CakeEmail->to();
        $this->assertEquals([$configs['to'] => $configs['to']], $result);

        $result = $this->CakeEmail->from();
        $this->assertEquals($configs['from'], $result);

        $result = $this->CakeEmail->subject();
        $this->assertEquals($configs['subject'], $result);

        $result = $this->CakeEmail->transport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $result = $this->CakeEmail->send('This is the message');

        $this->assertTrue((bool)strpos($result['headers'], 'Message-ID: '));
        $this->assertTrue((bool)strpos($result['headers'], 'To: '));
    }

    /**
     * testViewRender method
     *
     * @return void
     */
    public function testViewRender()
    {
        $result = $this->CakeEmail->viewRender();
        $this->assertEquals('Cake\View\View', $result);

        $result = $this->CakeEmail->viewRender('Cake\View\ThemeView');
        $this->assertInstanceOf('Cake\Mailer\Email', $result);

        $result = $this->CakeEmail->viewRender();
        $this->assertEquals('Cake\View\ThemeView', $result);
    }

    /**
     * testEmailFormat method
     *
     * @return void
     */
    public function testEmailFormat()
    {
        $result = $this->CakeEmail->emailFormat();
        $this->assertEquals('text', $result);

        $result = $this->CakeEmail->emailFormat('html');
        $this->assertInstanceOf('Cake\Mailer\Email', $result);

        $result = $this->CakeEmail->emailFormat();
        $this->assertEquals('html', $result);

        $this->setExpectedException('InvalidArgumentException');
        $result = $this->CakeEmail->emailFormat('invalid');
    }

    /**
     * Tests that it is possible to add charset configuration to a CakeEmail object
     *
     * @return void
     */
    public function testConfigCharset()
    {
        $email = new Email();
        $this->assertEquals(Configure::read('App.encoding'), $email->charset);
        $this->assertEquals(Configure::read('App.encoding'), $email->headerCharset);

        $email = new Email(['charset' => 'iso-2022-jp', 'headerCharset' => 'iso-2022-jp-ms']);
        $this->assertEquals('iso-2022-jp', $email->charset);
        $this->assertEquals('iso-2022-jp-ms', $email->headerCharset);

        $email = new Email(['charset' => 'iso-2022-jp']);
        $this->assertEquals('iso-2022-jp', $email->charset);
        $this->assertEquals('iso-2022-jp', $email->headerCharset);

        $email = new Email(['headerCharset' => 'iso-2022-jp-ms']);
        $this->assertEquals(Configure::read('App.encoding'), $email->charset);
        $this->assertEquals('iso-2022-jp-ms', $email->headerCharset);
    }

    /**
     * Tests that the header is encoded using the configured headerCharset
     *
     * @return void
     */
    public function testHeaderEncoding()
    {
        $email = new Email(['headerCharset' => 'iso-2022-jp-ms', 'transport' => 'debug']);
        $email->subject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = "?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=";
        $this->assertContains($expected, $headers['Subject']);

        $email->to('someone@example.com')->from('someone@example.com');
        $result = $email->send('ってテーブルを作ってやってたらう');
        $this->assertContains('ってテーブルを作ってやってたらう', $result['message']);
    }

    /**
     * Tests that the body is encoded using the configured charset
     *
     * @return void
     */
    public function testBodyEncoding()
    {
        $email = new Email([
            'charset' => 'iso-2022-jp',
            'headerCharset' => 'iso-2022-jp-ms',
            'transport' => 'debug'
        ]);
        $email->subject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = "?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=";
        $this->assertContains($expected, $headers['Subject']);

        $email->to('someone@example.com')->from('someone@example.com');
        $result = $email->send('ってテーブルを作ってやってたらう');
        $this->assertContains('Content-Type: text/plain; charset=ISO-2022-JP', $result['headers']);
        $this->assertContains(mb_convert_encoding('ってテーブルを作ってやってたらう', 'ISO-2022-JP'), $result['message']);
    }

    /**
     * Tests that the body is encoded using the configured charset (Japanese standard encoding)
     *
     * @return void
     */
    public function testBodyEncodingIso2022Jp()
    {
        $email = new Email([
            'charset' => 'iso-2022-jp',
            'headerCharset' => 'iso-2022-jp',
            'transport' => 'debug'
        ]);
        $email->subject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = "?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=";
        $this->assertContains($expected, $headers['Subject']);

        $email->to('someone@example.com')->from('someone@example.com');
        $result = $email->send('①㈱');
        $this->assertTextContains("Content-Type: text/plain; charset=ISO-2022-JP", $result['headers']);
        $this->assertTextNotContains("Content-Type: text/plain; charset=ISO-2022-JP-MS", $result['headers']); // not charset=iso-2022-jp-ms
        $this->assertTextNotContains(mb_convert_encoding('①㈱', 'ISO-2022-JP-MS'), $result['message']);
    }

    /**
     * Tests that the body is encoded using the configured charset (Japanese irregular encoding, but sometime use this)
     *
     * @return void
     */
    public function testBodyEncodingIso2022JpMs()
    {
        $email = new Email([
            'charset' => 'iso-2022-jp-ms',
            'headerCharset' => 'iso-2022-jp-ms',
            'transport' => 'debug'
        ]);
        $email->subject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = "?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=";
        $this->assertContains($expected, $headers['Subject']);

        $email->to('someone@example.com')->from('someone@example.com');
        $result = $email->send('①㈱');
        $this->assertTextContains("Content-Type: text/plain; charset=ISO-2022-JP", $result['headers']);
        $this->assertTextNotContains("Content-Type: text/plain; charset=iso-2022-jp-ms", $result['headers']); // not charset=iso-2022-jp-ms
        $this->assertContains(mb_convert_encoding('①㈱', 'ISO-2022-JP-MS'), $result['message']);
    }

    protected function _checkContentTransferEncoding($message, $charset)
    {
        $boundary = '--' . $this->CakeEmail->getBoundary();
        $result['text'] = false;
        $result['html'] = false;
        $length = count($message);
        for ($i = 0; $i < $length; ++$i) {
            if ($message[$i] === $boundary) {
                $flag = false;
                $type = '';
                while (!preg_match('/^$/', $message[$i])) {
                    if (preg_match('/^Content-Type: text\/plain/', $message[$i])) {
                        $type = 'text';
                    }
                    if (preg_match('/^Content-Type: text\/html/', $message[$i])) {
                        $type = 'html';
                    }
                    if ($message[$i] === 'Content-Transfer-Encoding: ' . $charset) {
                        $flag = true;
                    }
                    ++$i;
                }
                $result[$type] = $flag;
            }
        }
        return $result['text'] && $result['html'];
    }

    /**
     * Test CakeEmail::_encode function
     *
     * @return void
     */
    public function testEncode()
    {
        $this->CakeEmail->headerCharset = 'ISO-2022-JP';
        $result = $this->CakeEmail->encode('日本語');
        $expected = '=?ISO-2022-JP?B?GyRCRnxLXDhsGyhC?=';
        $this->assertSame($expected, $result);

        $this->CakeEmail->headerCharset = 'ISO-2022-JP';
        $result = $this->CakeEmail->encode('長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？');
        $expected = "=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
            " =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=";
        $this->assertSame($expected, $result);
    }

    /**
     * Test CakeEmail::_decode function
     *
     * @return void
     */
    public function testDecode()
    {
        $this->CakeEmail->headerCharset = 'ISO-2022-JP';
        $result = $this->CakeEmail->decode('=?ISO-2022-JP?B?GyRCRnxLXDhsGyhC?=');
        $expected = '日本語';
        $this->assertSame($expected, $result);

        $this->CakeEmail->headerCharset = 'ISO-2022-JP';
        $result = $this->CakeEmail->decode("=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
            " =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
            " =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=");
        $expected = '長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？';
        $this->assertSame($expected, $result);
    }

    /**
     * Tests charset setter/getter
     *
     * @return void
     */
    public function testCharset()
    {
        $this->CakeEmail->charset('UTF-8');
        $this->assertSame($this->CakeEmail->charset(), 'UTF-8');

        $this->CakeEmail->charset('ISO-2022-JP');
        $this->assertSame($this->CakeEmail->charset(), 'ISO-2022-JP');

        $charset = $this->CakeEmail->charset('Shift_JIS');
        $this->assertSame($charset, 'Shift_JIS');
    }

    /**
     * Tests headerCharset setter/getter
     *
     * @return void
     */
    public function testHeaderCharset()
    {
        $this->CakeEmail->headerCharset('UTF-8');
        $this->assertSame($this->CakeEmail->headerCharset(), 'UTF-8');

        $this->CakeEmail->headerCharset('ISO-2022-JP');
        $this->assertSame($this->CakeEmail->headerCharset(), 'ISO-2022-JP');

        $charset = $this->CakeEmail->headerCharset('Shift_JIS');
        $this->assertSame($charset, 'Shift_JIS');
    }

    /**
     * Tests for compatible check.
     *          charset property and       charset() method.
     *    headerCharset property and headerCharset() method.
     *
     * @return void
     */
    public function testCharsetsCompatible()
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
     * @return CakeEmail
     */
    protected function _getEmailByOldStyleCharset($charset, $headerCharset)
    {
        $email = new Email(['transport' => 'debug']);

        if (! empty($charset)) {
            $email->charset = $charset;
        }
        if (! empty($headerCharset)) {
            $email->headerCharset = $headerCharset;
        }

        $email->from('someone@example.com', 'どこかの誰か');
        $email->to('someperson@example.jp', 'どこかのどなたか');
        $email->cc('miku@example.net', 'ミク');
        $email->subject('テストメール');
        $email->send('テストメールの本文');

        return $email;
    }

    /**
     * @param mixed $charset
     * @param mixed $headerCharset
     * @return CakeEmail
     */
    protected function _getEmailByNewStyleCharset($charset, $headerCharset)
    {
        $email = new Email(['transport' => 'debug']);

        if (! empty($charset)) {
            $email->charset($charset);
        }
        if (! empty($headerCharset)) {
            $email->headerCharset($headerCharset);
        }

        $email->from('someone@example.com', 'どこかの誰か');
        $email->to('someperson@example.jp', 'どこかのどなたか');
        $email->cc('miku@example.net', 'ミク');
        $email->subject('テストメール');
        $email->send('テストメールの本文');

        return $email;
    }

    /**
     * testWrapLongLine()
     *
     * @return void
     */
    public function testWrapLongLine()
    {
        $message = '<a href="http://cakephp.org">' . str_repeat('x', Email::LINE_LENGTH_MUST) . "</a>";

        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('Wordwrap Test');
        $this->CakeEmail->profile(['empty']);
        $result = $this->CakeEmail->send($message);
        $expected = "<a\r\n" . 'href="http://cakephp.org">' . str_repeat('x', Email::LINE_LENGTH_MUST - 26) . "\r\n" .
            str_repeat('x', 26) . "\r\n</a>\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $str1 = "a ";
        $str2 = " b";
        $length = strlen($str1) + strlen($str2);
        $message = $str1 . str_repeat('x', Email::LINE_LENGTH_MUST - $length - 1) . $str2;

        $result = $this->CakeEmail->send($message);
        $expected = "{$message}\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $message = $str1 . str_repeat('x', Email::LINE_LENGTH_MUST - $length) . $str2;

        $result = $this->CakeEmail->send($message);
        $expected = "{$message}\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $message = $str1 . str_repeat('x', Email::LINE_LENGTH_MUST - $length + 1) . $str2;

        $result = $this->CakeEmail->send($message);
        $expected = $str1 . str_repeat('x', Email::LINE_LENGTH_MUST - $length + 1) . sprintf("\r\n%s\r\n\r\n", trim($str2));
        $this->assertEquals($expected, $result['message']);
        $this->assertLineLengths($result['message']);
    }

    /**
     * testWrapWithTagsAcrossLines()
     *
     * @return void
     */
    public function testWrapWithTagsAcrossLines()
    {
        $str = <<<HTML
<table>
<th align="right" valign="top"
        style="font-weight: bold">The tag is across multiple lines</th>
</table>
HTML;
        $length = strlen($str);
        $message = $str . str_repeat('x', Email::LINE_LENGTH_MUST + 1);

        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('Wordwrap Test');
        $this->CakeEmail->profile(['empty']);
        $result = $this->CakeEmail->send($message);
        $message = str_replace("\r\n", "\n", substr($message, 0, -9));
        $message = str_replace("\n", "\r\n", $message);
        $expected = "{$message}\r\nxxxxxxxxx\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
        $this->assertLineLengths($result['message']);
    }

    /**
     * CakeEmailTest::testWrapIncludeLessThanSign()
     *
     * @return void
     */
    public function testWrapIncludeLessThanSign()
    {
        $str = 'foo<bar';
        $length = strlen($str);
        $message = $str . str_repeat('x', Email::LINE_LENGTH_MUST - $length + 1);

        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('Wordwrap Test');
        $this->CakeEmail->profile(['empty']);
        $result = $this->CakeEmail->send($message);
        $message = substr($message, 0, -1);
        $expected = "{$message}\r\nx\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
        $this->assertLineLengths($result['message']);
    }

    /**
     * CakeEmailTest::testWrapForJapaneseEncoding()
     *
     * @return void
     */
    public function testWrapForJapaneseEncoding()
    {
        $this->skipIf(!function_exists('mb_convert_encoding'));

        $message = mb_convert_encoding('受け付けました', 'iso-2022-jp', 'UTF-8');

        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('Wordwrap Test');
        $this->CakeEmail->profile(['empty']);
        $this->CakeEmail->charset('iso-2022-jp');
        $this->CakeEmail->headerCharset('iso-2022-jp');
        $result = $this->CakeEmail->send($message);
        $expected = "{$message}\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
    }

    /**
     * CakeEmailTest::testMockTransport()
     */
    public function testMockTransport()
    {
        $mock = $this->getMock('\Cake\Mailer\AbstractTransport');
        $config = ['from' => 'tester@example.org', 'transport' => 'default'];

        Email::config('default', $config);
        Email::configTransport('default', $mock);

        $em = new Email('default');

        $this->assertSame($mock, $em->transport());
    }

    /**
     * testZeroOnlyLinesNotBeingEmptied()
     *
     * @return void
     */
    public function testZeroOnlyLinesNotBeingEmptied()
    {
        $message = "Lorem\r\n0\r\n0\r\nipsum";

        $this->CakeEmail->reset();
        $this->CakeEmail->transport('debug');
        $this->CakeEmail->from('cake@cakephp.org');
        $this->CakeEmail->to('cake@cakephp.org');
        $this->CakeEmail->subject('Wordwrap Test');
        $result = $this->CakeEmail->send($message);
        $expected = "{$message}\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
    }

    /**
     * testJsonSerialize()
     *
     * @return void
     */
    public function testJsonSerialize()
    {
        $xmlstr = <<<XML
<?xml version='1.0' standalone='yes'?>
<framework>
    <name>CakePHP</name>
    <url>http://cakephp.org</url>
</framework>
XML;

        $this->CakeEmail->reset()
            ->to(['cakephp@cakephp.org' => 'CakePHP'])
            ->from('noreply@cakephp.org')
            ->replyTo('cakephp@cakephp.org')
            ->cc(['mark@cakephp.org', 'juan@cakephp.org' => 'Juan Basso'])
            ->bcc('phpnut@cakephp.org')
            ->subject('Test Serialize')
            ->messageId('<uuid@server.com>')
            ->domain('foo.bar')
            ->viewVars([
                'users' => TableRegistry::get('Users')->get(1, ['fields' => ['id', 'username']]),
                'xml' => new SimpleXmlElement($xmlstr),
                'exception' => new Exception('test')
            ])
            ->attachments([
                'test.txt' => TEST_APP . 'config' . DS . 'empty.ini',
                'image' => [
                    'data' => file_get_contents(TEST_APP . 'webroot' . DS . 'img' . DS . 'cake.icon.png'),
                    'mimetype' => 'image/png'
                ]
            ]);

        $this->CakeEmail->viewBuilder()
            ->template('default')
            ->layout('test');

        $result = json_decode(json_encode($this->CakeEmail), true);
        $this->assertContains('test', $result['viewVars']['exception']);
        unset($result['viewVars']['exception']);

        $encode = function ($path) {
            return chunk_split(base64_encode(file_get_contents($path)), 76, "\r\n");
        };

        $expected = [
            '_to' => ['cakephp@cakephp.org' => 'CakePHP'],
            '_from' => ['noreply@cakephp.org' => 'noreply@cakephp.org'],
            '_replyTo' => ['cakephp@cakephp.org' => 'cakephp@cakephp.org'],
            '_cc' => ['mark@cakephp.org' => 'mark@cakephp.org', 'juan@cakephp.org' => 'Juan Basso'],
            '_bcc' => ['phpnut@cakephp.org' => 'phpnut@cakephp.org'],
            '_subject' => 'Test Serialize',
            '_emailFormat' => 'text',
            '_messageId' => '<uuid@server.com>',
            '_domain' => 'foo.bar',
            '_appCharset' => 'UTF-8',
            'charset' => 'utf-8',
            'headerCharset' => 'utf-8',
            'viewConfig' => [
                '_template' => 'default',
                '_layout' => 'test',
                '_helpers' => ['Html'],
                '_className' => 'Cake\View\View',
            ],
            'viewVars' => [
                'users' => [
                    'id' => 1,
                    'username' => 'mariano'
                ],
                'xml' => [
                    'name' => 'CakePHP',
                    'url' => 'http://cakephp.org'
                ],
            ],
            '_attachments' => [
                'test.txt' => [
                    'data' => $encode(TEST_APP . 'config' . DS . 'empty.ini'),
                    'mimetype' => 'application/octet-stream'
                ],
                'image' => [
                    'data' => $encode(TEST_APP . 'webroot' . DS . 'img' . DS . 'cake.icon.png'),
                    'mimetype' => 'image/png'
                ]
            ],
            '_emailPattern' => '/^((?:[\p{L}0-9.!#$%&\'*+\/=?^_`{|}~-]+)*@[\p{L}0-9-.]+)$/ui'
        ];
        $this->assertEquals($expected, $result);

        $result = json_decode(json_encode(unserialize(serialize($this->CakeEmail))), true);
        $this->assertContains('test', $result['viewVars']['exception']);
        unset($result['viewVars']['exception']);
        $this->assertEquals($expected, $result);
    }

    /**
     * CakeEmailTest::assertLineLengths()
     *
     * @param string $message
     * @return void
     */
    public function assertLineLengths($message)
    {
        $lines = explode("\r\n", $message);
        foreach ($lines as $line) {
            $this->assertTrue(
                strlen($line) <= Email::LINE_LENGTH_MUST,
                'Line length exceeds the max. limit of Email::LINE_LENGTH_MUST'
            );
        }
    }
}
