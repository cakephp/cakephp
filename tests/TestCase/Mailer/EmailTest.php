<?php
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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Log\Log;
use Cake\Mailer\Email;
use Cake\Mailer\Transport\DebugTransport;
use Cake\TestSuite\TestCase;
use Exception;
use SimpleXmlElement;

/**
 * Help to test Email
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

    /**
     * GetContentTransferEncoding to protected method
     *
     * @return string
     */
    public function getContentTransferEncoding()
    {
        return $this->_getContentTransferEncoding();
    }
}

/**
 * EmailTest class
 */
class EmailTest extends TestCase
{
    public $fixtures = ['core.Users'];

    /**
     * @var \Cake\Mailer\Email
     */
    protected $Email;

    /**
     * @var array
     */
    protected $transports = [];

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Email = new TestEmail();

        $this->transports = [
            'debug' => [
                'className' => 'Debug',
            ],
            'badClassName' => [
                'className' => 'TestFalse',
            ],
        ];

        $this->deprecated(function () {
            Email::setConfigTransport($this->transports);
        });
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
        $this->deprecated(function () {
            Email::dropTransport('debug');
            Email::dropTransport('badClassName');
            Email::dropTransport('test_smtp');
        });
    }

    /**
     * testFrom method
     *
     * @return void
     */
    public function testFrom()
    {
        $this->assertSame([], $this->Email->getFrom());

        $this->Email->setFrom('cake@cakephp.org');
        $expected = ['cake@cakephp.org' => 'cake@cakephp.org'];
        $this->assertSame($expected, $this->Email->getFrom());

        $this->Email->setFrom(['cake@cakephp.org']);
        $this->assertSame($expected, $this->Email->getFrom());

        $this->Email->setFrom('cake@cakephp.org', 'CakePHP');
        $expected = ['cake@cakephp.org' => 'CakePHP'];
        $this->assertSame($expected, $this->Email->getFrom());

        $result = $this->Email->setFrom(['cake@cakephp.org' => 'CakePHP']);
        $this->assertSame($expected, $this->Email->getFrom());
        $this->assertSame($this->Email, $result);

        $this->expectException(\InvalidArgumentException::class);
        $result = $this->Email->setFrom(['cake@cakephp.org' => 'CakePHP', 'fail@cakephp.org' => 'From can only be one address']);
    }

    /**
     * Test that from addresses using colons work.
     *
     * @return void
     */
    public function testFromWithColonsAndQuotes()
    {
        $address = [
            'info@example.com' => '70:20:00 " Forum',
        ];
        $this->Email->setFrom($address);
        $this->assertEquals($address, $this->Email->getFrom());
        $this->Email->setTo('info@example.com')
            ->setSubject('Test email')
            ->setTransport('debug');

        $result = $this->Email->send();
        $this->assertContains('From: "70:20:00 \" Forum" <info@example.com>', $result['headers']);
    }

    /**
     * testSender method
     *
     * @return void
     */
    public function testSender()
    {
        $this->Email->reset();
        $this->assertSame([], $this->Email->getSender());

        $this->Email->setSender('cake@cakephp.org', 'Name');
        $expected = ['cake@cakephp.org' => 'Name'];
        $this->assertSame($expected, $this->Email->getSender());

        $headers = $this->Email->getHeaders(['from' => true, 'sender' => true]);
        $this->assertFalse($headers['From']);
        $this->assertSame('Name <cake@cakephp.org>', $headers['Sender']);

        $this->Email->setFrom('cake@cakephp.org', 'CakePHP');
        $headers = $this->Email->getHeaders(['from' => true, 'sender' => true]);
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
        $this->assertSame([], $this->Email->getTo());

        $result = $this->Email->setTo('cake@cakephp.org');
        $expected = ['cake@cakephp.org' => 'cake@cakephp.org'];
        $this->assertSame($expected, $this->Email->getTo());
        $this->assertSame($this->Email, $result);

        $this->Email->setTo('cake@cakephp.org', 'CakePHP');
        $expected = ['cake@cakephp.org' => 'CakePHP'];
        $this->assertSame($expected, $this->Email->getTo());

        $list = [
            'root@localhost' => 'root',
            'bjørn@hammeröath.com' => 'Bjorn',
            'cake.php@cakephp.org' => 'Cake PHP',
            'cake-php@googlegroups.com' => 'Cake Groups',
            'root@cakephp.org',
        ];
        $this->Email->setTo($list);
        $expected = [
            'root@localhost' => 'root',
            'bjørn@hammeröath.com' => 'Bjorn',
            'cake.php@cakephp.org' => 'Cake PHP',
            'cake-php@googlegroups.com' => 'Cake Groups',
            'root@cakephp.org' => 'root@cakephp.org',
        ];
        $this->assertSame($expected, $this->Email->getTo());

        $this->Email->addTo('jrbasso@cakephp.org');
        $this->Email->addTo('mark_story@cakephp.org', 'Mark Story');
        $this->Email->addTo('foobar@ætdcadsl.dk');
        $result = $this->Email->addTo(['phpnut@cakephp.org' => 'PhpNut', 'jose_zap@cakephp.org']);
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
        $this->assertSame($expected, $this->Email->getTo());
        $this->assertSame($this->Email, $result);
    }

    /**
     * test to address with _ in domain name
     *
     * @return void
     */
    public function testToUnderscoreDomain()
    {
        $result = $this->Email->setTo('cake@cake_php.org');
        $expected = ['cake@cake_php.org' => 'cake@cake_php.org'];
        $this->assertSame($expected, $this->Email->getTo());
        $this->assertSame($this->Email, $result);
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
            [['ok@cakephp.org', 1.0, '', 'string']],
        ];
    }

    /**
     * testBuildInvalidData
     *
     * @dataProvider invalidEmails
     * @return void
     */
    public function testInvalidEmail($value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->Email->setTo($value);
    }

    /**
     * testBuildInvalidData
     *
     * @dataProvider invalidEmails
     * @return void
     */
    public function testInvalidEmailAdd($value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->Email->addTo($value);
    }

    /**
     * test emailPattern method
     *
     * @return void
     */
    public function testEmailPattern()
    {
        $regex = '/.+@.+\..+/i';
        $this->assertSame($regex, $this->Email->setEmailPattern($regex)->getEmailPattern());
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
        $this->assertSame($regex, $email->getEmailPattern());
    }

    /**
     * Tests that it is possible set custom email validation
     *
     * @return void
     */
    public function testCustomEmailValidation()
    {
        $regex = '/^[\.a-z0-9!#$%&\'*+\/=?^_`{|}~-]+@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]{2,6}$/i';

        $this->Email->setEmailPattern($regex)->setTo('pass.@example.com');
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
        ], $this->Email->getTo());

        $this->Email->addTo('pass..old.docomo@example.com');
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
            'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
        ], $this->Email->getTo());

        $this->Email->reset();
        $emails = [
            'pass.@example.com',
            'pass..old.docomo@example.com',
        ];
        $additionalEmails = [
            '.extend.@example.com',
            '.docomo@example.com',
        ];
        $this->Email->setEmailPattern($regex)->setTo($emails);
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
            'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
        ], $this->Email->getTo());

        $this->Email->addTo($additionalEmails);
        $this->assertSame([
            'pass.@example.com' => 'pass.@example.com',
            'pass..old.docomo@example.com' => 'pass..old.docomo@example.com',
            '.extend.@example.com' => '.extend.@example.com',
            '.docomo@example.com' => '.docomo@example.com',
        ], $this->Email->getTo());
    }

    /**
     * Tests not found transport class name exception
     *
     * @return void
     */
    public function testClassNameException()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Mailer transport TestFalse is not available.');
        $email = new Email();
        $email->setTransport('badClassName');
    }

    /**
     * Tests that it is possible to unset the email pattern and make use of filter_var() instead.
     *
     * @return void
     */
    public function testUnsetEmailPattern()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email set for "to". You passed "fail.@example.com".');
        $email = new Email();
        $this->assertSame(Email::EMAIL_PATTERN, $email->getEmailPattern());

        $email->setEmailPattern(null);
        $this->assertNull($email->getEmailPattern());

        $email->setTo('pass@example.com');
        $email->setTo('fail.@example.com');
    }

    /**
     * Tests that passing an empty string throws an InvalidArgumentException.
     *
     * @return void
     */
    public function testEmptyTo()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The email set for "to" is empty.');
        $email = new Email();
        $email->setTo('');
    }

    /**
     * testFormatAddress method
     *
     * @return void
     */
    public function testFormatAddress()
    {
        $result = $this->Email->formatAddress(['cake@cakephp.org' => 'cake@cakephp.org']);
        $expected = ['cake@cakephp.org'];
        $this->assertSame($expected, $result);

        $result = $this->Email->formatAddress(['cake@cakephp.org' => 'cake@cakephp.org', 'php@cakephp.org' => 'php@cakephp.org']);
        $expected = ['cake@cakephp.org', 'php@cakephp.org'];
        $this->assertSame($expected, $result);

        $result = $this->Email->formatAddress(['cake@cakephp.org' => 'CakePHP', 'php@cakephp.org' => 'Cake']);
        $expected = ['CakePHP <cake@cakephp.org>', 'Cake <php@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->formatAddress(['me@example.com' => 'Last, First']);
        $expected = ['"Last, First" <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->formatAddress(['me@example.com' => '"Last" First']);
        $expected = ['"\"Last\" First" <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->formatAddress(['me@example.com' => 'Last First']);
        $expected = ['Last First <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->formatAddress(['cake@cakephp.org' => 'ÄÖÜTest']);
        $expected = ['=?UTF-8?B?w4TDlsOcVGVzdA==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->formatAddress(['cake@cakephp.org' => '日本語Test']);
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
        $this->Email->headerCharset = 'ISO-2022-JP';
        $result = $this->Email->formatAddress(['cake@cakephp.org' => '日本語Test']);
        $expected = ['=?ISO-2022-JP?B?GyRCRnxLXDhsGyhCVGVzdA==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->formatAddress(['cake@cakephp.org' => '寿限無寿限無五劫の擦り切れ海砂利水魚の水行末雲来末風来末食う寝る処に住む処やぶら小路の藪柑子パイポパイポパイポのシューリンガンシューリンガンのグーリンダイグーリンダイのポンポコピーのポンポコナーの長久命の長助']);
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
     *
     * @return void
     */
    public function testAddresses()
    {
        $this->Email->reset();
        $this->Email->setFrom('cake@cakephp.org', 'CakePHP');
        $this->Email->setReplyTo('replyto@cakephp.org', 'ReplyTo CakePHP');
        $this->Email->setReadReceipt('readreceipt@cakephp.org', 'ReadReceipt CakePHP');
        $this->Email->setReturnPath('returnpath@cakephp.org', 'ReturnPath CakePHP');
        $this->Email->setTo('to@cakephp.org', 'To, CakePHP');
        $this->Email->setCc('cc@cakephp.org', 'Cc CakePHP');
        $this->Email->setBcc('bcc@cakephp.org', 'Bcc CakePHP');
        $this->Email->addTo('to2@cakephp.org', 'To2 CakePHP');
        $this->Email->addCc('cc2@cakephp.org', 'Cc2 CakePHP');
        $this->Email->addBcc('bcc2@cakephp.org', 'Bcc2 CakePHP');

        $this->assertSame($this->Email->getFrom(), ['cake@cakephp.org' => 'CakePHP']);
        $this->assertSame($this->Email->getReplyTo(), ['replyto@cakephp.org' => 'ReplyTo CakePHP']);
        $this->assertSame($this->Email->getReadReceipt(), ['readreceipt@cakephp.org' => 'ReadReceipt CakePHP']);
        $this->assertSame($this->Email->getReturnPath(), ['returnpath@cakephp.org' => 'ReturnPath CakePHP']);
        $this->assertSame($this->Email->getTo(), ['to@cakephp.org' => 'To, CakePHP', 'to2@cakephp.org' => 'To2 CakePHP']);
        $this->assertSame($this->Email->getCc(), ['cc@cakephp.org' => 'Cc CakePHP', 'cc2@cakephp.org' => 'Cc2 CakePHP']);
        $this->assertSame($this->Email->getBcc(), ['bcc@cakephp.org' => 'Bcc CakePHP', 'bcc2@cakephp.org' => 'Bcc2 CakePHP']);

        $headers = $this->Email->getHeaders(array_fill_keys(['from', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc'], true));
        $this->assertSame($headers['From'], 'CakePHP <cake@cakephp.org>');
        $this->assertSame($headers['Reply-To'], 'ReplyTo CakePHP <replyto@cakephp.org>');
        $this->assertSame($headers['Disposition-Notification-To'], 'ReadReceipt CakePHP <readreceipt@cakephp.org>');
        $this->assertSame($headers['Return-Path'], 'ReturnPath CakePHP <returnpath@cakephp.org>');
        $this->assertSame($headers['To'], '"To, CakePHP" <to@cakephp.org>, To2 CakePHP <to2@cakephp.org>');
        $this->assertSame($headers['Cc'], 'Cc CakePHP <cc@cakephp.org>, Cc2 CakePHP <cc2@cakephp.org>');
        $this->assertSame($headers['Bcc'], 'Bcc CakePHP <bcc@cakephp.org>, Bcc2 CakePHP <bcc2@cakephp.org>');
    }

    /**
     * test reset addresses method
     *
     * @return void
     */
    public function testResetAddresses()
    {
        $this->Email->reset();
        $this->Email
            ->setFrom('cake@cakephp.org', 'CakePHP')
            ->setReplyTo('replyto@cakephp.org', 'ReplyTo CakePHP')
            ->setReadReceipt('readreceipt@cakephp.org', 'ReadReceipt CakePHP')
            ->setReturnPath('returnpath@cakephp.org', 'ReturnPath CakePHP')
            ->setTo('to@cakephp.org', 'To, CakePHP')
            ->setCc('cc@cakephp.org', 'Cc CakePHP')
            ->setBcc('bcc@cakephp.org', 'Bcc CakePHP');

        $this->assertNotEmpty($this->Email->getFrom());
        $this->assertNotEmpty($this->Email->getReplyTo());
        $this->assertNotEmpty($this->Email->getReadReceipt());
        $this->assertNotEmpty($this->Email->getReturnPath());
        $this->assertNotEmpty($this->Email->getTo());
        $this->assertNotEmpty($this->Email->getCc());
        $this->assertNotEmpty($this->Email->getBcc());

        $this->Email
            ->setFrom([])
            ->setReplyTo([])
            ->setReadReceipt([])
            ->setReturnPath([])
            ->setTo([])
            ->setCc([])
            ->setBcc([]);

        $this->assertEmpty($this->Email->getFrom());
        $this->assertEmpty($this->Email->getReplyTo());
        $this->assertEmpty($this->Email->getReadReceipt());
        $this->assertEmpty($this->Email->getReturnPath());
        $this->assertEmpty($this->Email->getTo());
        $this->assertEmpty($this->Email->getCc());
        $this->assertEmpty($this->Email->getBcc());
    }

    /**
     * testMessageId method
     *
     * @return void
     */
    public function testMessageId()
    {
        $this->Email->setMessageId(true);
        $result = $this->Email->getHeaders();
        $this->assertArrayHasKey('Message-ID', $result);

        $this->Email->setMessageId(false);
        $result = $this->Email->getHeaders();
        $this->assertArrayNotHasKey('Message-ID', $result);

        $result = $this->Email->setMessageId('<my-email@localhost>');
        $this->assertSame($this->Email, $result);
        $result = $this->Email->getHeaders();
        $this->assertSame('<my-email@localhost>', $result['Message-ID']);

        $result = $this->Email->getMessageId();
        $this->assertSame('<my-email@localhost>', $result);
    }

    public function testAutoMessageIdIsIdempotent()
    {
        $headers = $this->Email->getHeaders();
        $this->assertArrayHasKey('Message-ID', $headers);

        $regeneratedHeaders = $this->Email->getHeaders();
        $this->assertSame($headers['Message-ID'], $regeneratedHeaders['Message-ID']);
    }

    /**
     * @return void
     */
    public function testPriority()
    {
        $this->Email->setPriority(4);

        $this->assertSame(4, $this->Email->getPriority());

        $result = $this->Email->getHeaders();
        $this->assertArrayHasKey('X-Priority', $result);
    }

    /**
     * testMessageIdInvalid method
     *
     * @return void
     */
    public function testMessageIdInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->Email->setMessageId('my-email@localhost');
    }

    /**
     * testDomain method
     *
     * @return void
     */
    public function testDomain()
    {
        $result = $this->Email->getDomain();
        $expected = env('HTTP_HOST') ? env('HTTP_HOST') : php_uname('n');
        $this->assertSame($expected, $result);

        $this->Email->setDomain('example.org');
        $result = $this->Email->getDomain();
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
        $this->Email->setDomain('example.org');
        $result = $this->Email->getHeaders();
        $expected = '@example.org>';
        $this->assertTextContains($expected, $result['Message-ID']);

        $_SERVER['HTTP_HOST'] = 'example.org';
        $result = $this->Email->getHeaders();
        $this->assertTextContains('example.org', $result['Message-ID']);

        $_SERVER['HTTP_HOST'] = 'example.org:81';
        $result = $this->Email->getHeaders();
        $this->assertTextNotContains(':81', $result['Message-ID']);
    }

    /**
     * testSubject method
     *
     * @return void
     */
    public function testSubject()
    {
        $this->Email->setSubject('You have a new message.');
        $this->assertSame('You have a new message.', $this->Email->getSubject());

        $this->Email->setSubject('You have a new message, I think.');
        $this->assertSame($this->Email->getSubject(), 'You have a new message, I think.');
        $this->Email->setSubject(1);
        $this->assertSame('1', $this->Email->getSubject());

        $input = 'هذه رسالة بعنوان طويل مرسل للمستلم';
        $this->Email->setSubject($input);
        $expected = '=?UTF-8?B?2YfYsNmHINix2LPYp9mE2Kkg2KjYudmG2YjYp9mGINi32YjZitmEINmF2LE=?=' . "\r\n" . ' =?UTF-8?B?2LPZhCDZhNmE2YXYs9iq2YTZhQ==?=';
        $this->assertSame($expected, $this->Email->getSubject());
        $this->assertSame($input, $this->Email->getOriginalSubject());
    }

    /**
     * testSubjectJapanese
     *
     * @return void
     */
    public function testSubjectJapanese()
    {
        mb_internal_encoding('UTF-8');

        $this->Email->headerCharset = 'ISO-2022-JP';
        $this->Email->setSubject('日本語のSubjectにも対応するよ');
        $expected = '=?ISO-2022-JP?B?GyRCRnxLXDhsJE4bKEJTdWJqZWN0GyRCJEskYkJQMX4kOSRrJGgbKEI=?=';
        $this->assertSame($expected, $this->Email->getSubject());

        $this->Email->setSubject('長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？');
        $expected = "=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
            " =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
            ' =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=';
        $this->assertSame($expected, $this->Email->getSubject());
    }

    /**
     * testHeaders method
     *
     * @return void
     */
    public function testHeaders()
    {
        $this->Email->setMessageId(false);
        $this->Email->setHeaders(['X-Something' => 'nice']);
        $expected = [
            'X-Something' => 'nice',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
        ];
        $this->assertSame($expected, $this->Email->getHeaders());

        $this->Email->addHeaders(['X-Something' => 'very nice', 'X-Other' => 'cool']);
        $expected = [
            'X-Something' => 'very nice',
            'X-Other' => 'cool',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
        ];
        $this->assertSame($expected, $this->Email->getHeaders());

        $this->Email->setFrom('cake@cakephp.org');
        $this->assertSame($expected, $this->Email->getHeaders());

        $expected = [
            'From' => 'cake@cakephp.org',
            'X-Something' => 'very nice',
            'X-Other' => 'cool',
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
        ];
        $this->assertSame($expected, $this->Email->getHeaders(['from' => true]));

        $this->Email->setFrom('cake@cakephp.org', 'CakePHP');
        $expected['From'] = 'CakePHP <cake@cakephp.org>';
        $this->assertSame($expected, $this->Email->getHeaders(['from' => true]));

        $this->Email->setTo(['cake@cakephp.org', 'php@cakephp.org' => 'CakePHP']);
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
        $this->assertSame($expected, $this->Email->getHeaders(['from' => true, 'to' => true]));

        $this->Email->charset = 'ISO-2022-JP';
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
        $this->assertSame($expected, $this->Email->getHeaders(['from' => true, 'to' => true]));

        $result = $this->Email->setHeaders([]);
        $this->assertInstanceOf('Cake\Mailer\Email', $result);

        $this->Email->setHeaders(['o:tag' => ['foo']]);
        $this->Email->addHeaders(['o:tag' => ['bar']]);
        $result = $this->Email->getHeaders();
        $this->assertEquals(['foo', 'bar'], $result['o:tag']);
    }

    /**
     * testTemplate method
     *
     * @return void
     */
    public function testTemplate()
    {
        $this->Email->viewBuilder()->setTemplate('template');
        $this->assertSame('template', $this->Email->viewBuilder()->getTemplate());
    }

    /**
     * testLayout method
     *
     * @return void
     */
    public function testLayout()
    {
        $this->Email->viewBuilder()->setLayout('layout');
        $this->assertSame('layout', $this->Email->viewBuilder()->getLayout());
    }

    /**
     * testTheme method
     *
     * @return void
     */
    public function testTheme()
    {
        $this->assertNull($this->Email->viewBuilder()->getTheme());

        $this->Email->viewBuilder()->setTheme('default');
        $expected = 'default';
        $this->assertSame($expected, $this->Email->viewBuilder()->getTheme());
    }

    /**
     * testViewVars method
     *
     * @return void
     */
    public function testViewVars()
    {
        $this->assertSame([], $this->Email->getViewVars());

        $this->Email->setViewVars(['value' => 12345]);
        $this->assertSame(['value' => 12345], $this->Email->getViewVars());

        $this->Email->setViewVars(['name' => 'CakePHP']);
        $this->assertEquals(['value' => 12345, 'name' => 'CakePHP'], $this->Email->getViewVars());

        $this->Email->setViewVars(['value' => 4567]);
        $this->assertSame(['value' => 4567, 'name' => 'CakePHP'], $this->Email->getViewVars());
    }

    /**
     * testAttachments method
     *
     * @return void
     */
    public function testSetAttachments()
    {
        $this->Email->setAttachments(CAKE . 'basics.php');
        $expected = [
            'basics.php' => [
                'file' => CAKE . 'basics.php',
                'mimetype' => 'text/x-php',
            ],
        ];
        $this->assertSame($expected, $this->Email->getAttachments());

        $this->Email->setAttachments([]);
        $this->assertSame([], $this->Email->getAttachments());

        $this->Email->setAttachments([
            ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain'],
        ]);
        $this->Email->addAttachments(CORE_PATH . 'config' . DS . 'bootstrap.php');
        $this->Email->addAttachments([CORE_PATH . 'config' . DS . 'bootstrap.php']);
        $this->Email->addAttachments([
            'other.txt' => CORE_PATH . 'config' . DS . 'bootstrap.php',
            'license' => CORE_PATH . 'LICENSE',
        ]);
        $expected = [
            'basics.php' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain'],
            'bootstrap.php' => ['file' => CORE_PATH . 'config' . DS . 'bootstrap.php', 'mimetype' => 'text/x-php'],
            'other.txt' => ['file' => CORE_PATH . 'config' . DS . 'bootstrap.php', 'mimetype' => 'text/x-php'],
            'license' => ['file' => CORE_PATH . 'LICENSE', 'mimetype' => 'text/plain'],
        ];
        $this->assertSame($expected, $this->Email->getAttachments());
        $this->expectException(\InvalidArgumentException::class);
        $this->Email->setAttachments([['nofile' => CAKE . 'basics.php', 'mimetype' => 'text/plain']]);
    }

    /**
     * Test send() with no template and data string attachment and no mimetype
     *
     * @return void
     */
    public function testSetAttachmentDataNoMimetype()
    {
        $this->Email->setAttachments(['cake.icon.gif' => [
            'data' => 'test',
        ]]);
        $result = $this->Email->getAttachments();
        $expected = [
            'cake.icon.gif' => [
                'data' => base64_encode('test') . "\r\n",
                'mimetype' => 'application/octet-stream',
            ],
        ];
        $this->assertSame($expected, $this->Email->getAttachments());
    }

    /**
     * testTransport method
     *
     * @return void
     */
    public function testTransport()
    {
        $result = $this->Email->setTransport('debug');
        $this->assertSame($this->Email, $result);

        $result = $this->Email->getTransport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $instance = $this->getMockBuilder('Cake\Mailer\Transport\DebugTransport')->getMock();
        $this->Email->setTransport($instance);
        $this->assertSame($instance, $this->Email->getTransport());
    }

    /**
     * Test that using unknown transports fails.
     */
    public function testTransportInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "Invalid" transport configuration does not exist');
        $this->Email->setTransport('Invalid');
    }

    /**
     * Test that using classes with no send method fails.
     */
    public function testTransportInstanceInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->Email->setTransport(new \StdClass());
    }

    /**
     * Test that using unknown transports fails.
     */
    public function testTransportTypeInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The value passed for the "$name" argument must be either a string, or an object, integer given.');
        $this->Email->setTransport(123);
    }

    /**
     * Test that using misconfigured transports fails.
     */
    public function testTransportMissingClassName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transport config "debug" is invalid, the required `className` option is missing');
        $this->deprecated(function () {
            Email::dropTransport('debug');
            Email::setConfigTransport('debug', []);
        });

        $this->Email->setTransport('debug');
    }

    /**
     * Test configuring a transport.
     *
     * @return void
     */
    public function testConfigTransport()
    {
        $settings = [
            'className' => 'Debug',
            'log' => true,
        ];
        $this->deprecated(function () use ($settings) {
            Email::dropTransport('debug');
            $result = Email::setConfigTransport('debug', $settings);
            $this->assertNull($result, 'No return.');

            $result = Email::getConfigTransport('debug');
            $this->assertEquals($settings, $result);
        });
    }

    /**
     * Test configuring multiple transports.
     */
    public function testConfigTransportMultiple()
    {
        $settings = [
            'debug' => [
                'className' => 'Debug',
                'log' => true,
            ],
            'test_smtp' => [
                'className' => 'Smtp',
                'username' => 'mark',
                'password' => 'password',
                'host' => 'example.com',
            ],
        ];
        $this->deprecated(function () use ($settings) {
            Email::dropTransport('debug');
            Email::setConfigTransport($settings);
            $this->assertEquals($settings['debug'], Email::getConfigTransport('debug'));
            $this->assertEquals($settings['test_smtp'], Email::getConfigTransport('test_smtp'));
        });
    }

    /**
     * Test that exceptions are raised when duplicate transports are configured.
     */
    public function testConfigTransportErrorOnDuplicate()
    {
        $this->expectException(\BadMethodCallException::class);
        $settings = [
            'className' => 'Debug',
            'log' => true,
        ];
        $this->deprecated(function () use ($settings) {
            Email::setConfigTransport('debug', $settings);
            Email::setConfigTransport('debug', $settings);
            Email::dropTransport('debug');
        });
    }

    /**
     * Test configTransport with an instance.
     *
     * @return void
     */
    public function testConfigTransportInstance()
    {
        $this->deprecated(function () {
            Email::dropTransport('debug');
            $instance = new DebugTransport();
            Email::setConfigTransport('debug', $instance);
            $this->assertEquals(['className' => $instance], Email::getConfigTransport('debug'));
        });
    }

    /**
     * Test enumerating all transport configurations
     *
     * @return void
     */
    public function testConfiguredTransport()
    {
        $this->deprecated(function () {
            $result = Email::configuredTransport();
            $this->assertInternalType('array', $result, 'Should have config keys');
            $this->assertEquals(
                array_keys($this->transports),
                $result,
                'Loaded transports should be present in enumeration.'
            );
        });
    }

    /**
     * Test dropping a transport configuration
     *
     * @return void
     */
    public function testDropTransport()
    {
        $this->deprecated(function () {
            $result = Email::getConfigTransport('debug');
            $this->assertInternalType('array', $result, 'Should have config data');
            Email::dropTransport('debug');
            $this->assertNull(Email::getConfigTransport('debug'), 'Should not exist.');
        });
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
        Email::setConfig('test', $settings);
        $this->assertEquals($settings, Email::getConfig('test'), 'Should be the same.');

        $email = new Email('test');
        $this->assertContains($settings['to'], $email->getTo());
    }

    /**
     * Test that exceptions are raised on duplicate config set.
     *
     * @return void
     */
    public function testConfigErrorOnDuplicate()
    {
        $this->expectException(\BadMethodCallException::class);
        $settings = [
            'to' => 'mark@example.com',
            'from' => 'noreply@example.com',
        ];
        Email::setConfig('test', $settings);
        Email::setConfig('test', $settings);
    }

    /**
     * test profile method
     *
     * @return void
     */
    public function testProfile()
    {
        $config = ['test' => 'ok', 'test2' => true];
        $this->Email->setProfile($config);
        $this->assertSame($this->Email->getProfile(), $config);

        $config = ['test' => 'test@example.com'];
        $this->Email->setProfile($config);
        $expected = ['test' => 'test@example.com', 'test2' => true];
        $this->assertSame($expected, $this->Email->getProfile());
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
        Email::setConfig(Configure::consume('Email'));
        $Email = new Email();
        $this->assertSame($Email->getProfile(), $config);
        Configure::delete('Email');
        Email::drop('default');
    }

    /**
     * Test that using an invalid profile fails.
     */
    public function testProfileInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown email configuration "derp".');
        $email = new Email();
        $email->setProfile('derp');
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
        Email::setConfig('test', $config);
        $this->Email->setProfile('test');

        $result = $this->Email->getTo();
        $this->assertEquals($config['to'], $result);

        $result = $this->Email->getFrom();
        $this->assertEquals($config['from'], $result);

        $result = $this->Email->getSubject();
        $this->assertEquals($config['subject'], $result);

        $result = $this->Email->viewBuilder()->getTheme();
        $this->assertEquals($config['theme'], $result);

        $result = $this->Email->getTransport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $result = $this->Email->viewBuilder()->getHelpers();
        $this->assertEquals($config['helpers'], $result);
    }

    /**
     * Calling send() with no parameters should not overwrite the view variables.
     *
     * @return void
     */
    public function testSendWithNoContentDoesNotOverwriteViewVar()
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('you@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setEmailFormat('text');
        $this->Email->viewBuilder()->setTemplate('default');
        $this->Email->setViewVars([
            'content' => 'A message to you',
        ]);

        $result = $this->Email->send();
        $this->assertContains('A message to you', $result['message']);
    }

    /**
     * testSendWithContent method
     *
     * @return void
     */
    public function testSendWithContent()
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);

        $result = $this->Email->send("Here is my body, with multi lines.\nThis is the second line.\r\n\r\nAnd the last.");
        $expected = ['headers', 'message'];
        $this->assertEquals($expected, array_keys($result));
        $expected = "Here is my body, with multi lines.\r\nThis is the second line.\r\n\r\nAnd the last.\r\n\r\n";

        $this->assertEquals($expected, $result['message']);
        $this->assertContains('Date: ', $result['headers']);
        $this->assertContains('Message-ID: ', $result['headers']);
        $this->assertContains('To: ', $result['headers']);

        $result = $this->Email->send('Other body');
        $expected = "Other body\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertContains('Message-ID: ', $result['headers']);
        $this->assertContains('To: ', $result['headers']);

        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $result = $this->Email->send(['Sending content', 'As array']);
        $expected = "Sending content\r\nAs array\r\n\r\n\r\n";
        $this->assertSame($expected, $result['message']);
    }

    /**
     * testSendWithoutFrom method
     *
     * @return void
     */
    public function testSendWithoutFrom()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->Email->setTransport('debug');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->send('Forgot to set From');
    }

    /**
     * testSendWithoutTo method
     *
     * @return void
     */
    public function testSendWithoutTo()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->send('Forgot to set To');
    }

    /**
     * test send without a transport method
     *
     * @return void
     */
    public function testSendWithoutTransport()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot send email, transport was not defined.');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->send('Forgot to set To');
    }

    /**
     * Test send() with no template.
     *
     * @return void
     */
    public function testSendNoTemplateWithAttachments()
    {
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setEmailFormat('text');
        $this->Email->setAttachments([CAKE . 'basics.php']);
        $result = $this->Email->send('Hello');

        $boundary = $this->Email->getBoundary();
        $this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--$boundary\r\n" .
            "Content-Disposition: attachment; filename=\"basics.php\"\r\n" .
            "Content-Type: text/x-php\r\n" .
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
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setEmailFormat('text');
        $data = file_get_contents(TEST_APP . 'webroot/img/cake.power.gif');
        $this->Email->setAttachments(['cake.icon.gif' => [
                'data' => $data,
                'mimetype' => 'image/gif',
        ]]);
        $result = $this->Email->send('Hello');

        $boundary = $this->Email->getBoundary();
        $this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
                "Content-Type: text/plain; charset=UTF-8\r\n" .
                "Content-Transfer-Encoding: 8bit\r\n" .
                "\r\n" .
                'Hello' .
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
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setEmailFormat('both');
        $this->Email->setAttachments([CORE_PATH . 'VERSION.txt']);
        $result = $this->Email->send('Hello');

        $boundary = $this->Email->getBoundary();
        $this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: multipart/alternative; boundary=\"alt-$boundary\"\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-{$boundary}--\r\n" .
            "\r\n" .
            "--$boundary\r\n" .
            "Content-Disposition: attachment; filename=\"VERSION.txt\"\r\n" .
            "Content-Type: text/plain\r\n" .
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
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setEmailFormat('both');
        $this->Email->setAttachments([
            'cake.png' => [
                'file' => CORE_PATH . 'VERSION.txt',
                'contentId' => 'abc123',
            ],
        ]);
        $result = $this->Email->send('Hello');

        $boundary = $this->Email->getBoundary();
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
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-{$boundary}--\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Disposition: inline; filename=\"cake.png\"\r\n" .
            "Content-Type: text/plain\r\n" .
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
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setEmailFormat('html');
        $this->Email->setAttachments([
            'cake.png' => [
                'file' => CORE_PATH . 'VERSION.txt',
                'contentId' => 'abc123',
            ],
        ]);
        $result = $this->Email->send('Hello');

        $boundary = $this->Email->getBoundary();
        $this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: multipart/related; boundary=\"rel-$boundary\"\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Disposition: inline; filename=\"cake.png\"\r\n" .
            "Content-Type: text/plain\r\n" .
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
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setEmailFormat('text');
        $this->Email->setAttachments([
            'cake.png' => [
                'file' => CORE_PATH . 'VERSION.txt',
                'contentDisposition' => false,
            ],
        ]);
        $result = $this->Email->send('Hello');

        $boundary = $this->Email->getBoundary();
        $this->assertContains('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--{$boundary}\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "\r\n";

        $this->assertContains($expected, $result['message']);
        $this->assertContains('--' . $boundary . '--', $result['message']);
    }

    /**
     * Test an attachment filename with non-ASCII characters.
     *
     * @return void
     */
    public function testSendWithNonAsciiFilenameAttachments()
    {
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setEmailFormat('both');
        $this->Email->setAttachments([
            'gâteau.png' => [
                'file' => CORE_PATH . 'VERSION.txt',
                'contentId' => 'abc123',
            ],
        ]);
        $result = $this->Email->send('Hello');

        $boundary = $this->Email->getBoundary();
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
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-{$boundary}--\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Disposition: inline; filename=\"gateau.png\"; filename*=utf-8''g%C3%A2teau.png\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "Content-ID: <abc123>\r\n" .
            "\r\n";
        $this->assertContains($expected, $result['message']);
        $this->assertContains('--rel-' . $boundary . '--', $result['message']);
        $this->assertContains('--' . $boundary . '--', $result['message']);
    }

    /**
     * testSendWithLog method
     *
     * @return void
     */
    public function testSendWithLog()
    {
        $log = $this->getMockBuilder('Cake\Log\Engine\BaseLog')
            ->setMethods(['log'])
            ->setConstructorArgs([['scopes' => 'email']])
            ->getMock();

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

        Log::setConfig('email', $log);

        $this->Email->setTransport('debug');
        $this->Email->setTo('me@cakephp.org');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['log' => 'debug']);
        $result = $this->Email->send($message);
    }

    /**
     * testSendWithLogAndScope method
     *
     * @return void
     */
    public function testSendWithLogAndScope()
    {
        $message = 'Logging This';

        $log = $this->getMockBuilder('Cake\Log\Engine\BaseLog')
            ->setMethods(['log'])
            ->setConstructorArgs(['scopes' => ['email']])
            ->getMock();
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

        Log::setConfig('email', $log);

        $this->Email->setTransport('debug');
        $this->Email->setTo('me@cakephp.org');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['log' => ['scope' => 'email']]);
        $this->Email->send($message);
    }

    /**
     * testSendRender method
     *
     * @return void
     */
    public function testSendRender()
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()->setTemplate('default', 'default');
        $result = $this->Email->send();

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
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setConfig(['empty']);
        $this->Email->viewBuilder()
            ->setTemplate('default')
            ->setLayout(false);
        $result = $this->Email->send('message body.');

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
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()->setTemplate('default', 'default');
        $this->Email->setEmailFormat('both');
        $result = $this->Email->send();

        $this->assertContains('Message-ID: ', $result['headers']);
        $this->assertContains('To: ', $result['headers']);

        $boundary = $this->Email->getBoundary();
        $this->assertContains('Content-Type: multipart/alternative; boundary="' . $boundary . '"', $result['headers']);

        $expected = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            'This email was sent using the CakePHP Framework, https://cakephp.org.' .
            "\r\n" .
            "\r\n" .
            "--$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            '<!DOCTYPE html';
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
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()->setTemplate('default');
        $this->Email->viewBuilder()->setLayout('japanese');
        $this->Email->charset = 'ISO-2022-JP';
        $result = $this->Email->send();

        $expected = mb_convert_encoding('CakePHP Framework を使って送信したメールです。 https://cakephp.org.', 'ISO-2022-JP');
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
        $this->loadPlugins(['TestTheme']);
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()->setTheme('TestTheme');
        $this->Email->viewBuilder()->setTemplate('themed', 'default');
        $result = $this->Email->send();

        $this->assertContains('In TestTheme', $result['message']);
        $this->assertContains('/test_theme/img/test.jpg', $result['message']);
        $this->assertContains('Message-ID: ', $result['headers']);
        $this->assertContains('To: ', $result['headers']);
        $this->assertContains('/test_theme/img/test.jpg', $result['message']);
        $this->clearPlugins();
    }

    /**
     * testSendRenderWithHTML method and assert line length is kept below the required limit
     *
     * @return void
     */
    public function testSendRenderWithHTML()
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->setEmailFormat('html');
        $this->Email->viewBuilder()->setTemplate('html', 'default');
        $result = $this->Email->send();

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
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()->setTemplate('custom', 'default');
        $this->Email->setViewVars(['value' => 12345]);
        $result = $this->Email->send();

        $this->assertContains('Here is your value: 12345', $result['message']);
    }

    /**
     * testSendRenderWithVars method for ISO-2022-JP
     *
     * @return void
     */
    public function testSendRenderWithVarsJapanese()
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()->setTemplate('japanese', 'default');
        $this->Email->setViewVars(['value' => '日本語の差し込み123']);
        $this->Email->charset = 'ISO-2022-JP';
        $result = $this->Email->send();

        $expected = mb_convert_encoding('ここにあなたの設定した値が入ります: 日本語の差し込み123', 'ISO-2022-JP');
        $this->assertContains($expected, $result['message']);
    }

    /**
     * testSendRenderWithHelpers method
     *
     * @return void
     */
    public function testSendRenderWithHelpers()
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $timestamp = time();
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()
            ->setTemplate('custom_helper')
            ->setLayout('default')
            ->setHelpers(['Time'], false);
        $this->Email->setViewVars(['time' => $timestamp]);

        $result = $this->Email->send();
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($timestamp);
        $this->assertContains('Right now: ' . $dateTime->format($dateTime::ATOM), $result['message']);

        $result = $this->Email->viewBuilder()->getHelpers();
        $this->assertEquals(['Time'], $result);
    }

    /**
     * testSendRenderWithImage method
     *
     * @return void
     */
    public function testSendRenderWithImage()
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()->setTemplate('image');
        $this->Email->setEmailFormat('html');
        $server = env('SERVER_NAME') ? env('SERVER_NAME') : 'localhost';

        if (env('SERVER_PORT') && env('SERVER_PORT') != 80) {
            $server .= ':' . env('SERVER_PORT');
        }

        $expected = '<img src="http://' . $server . '/img/image.gif" alt="cool image" width="100" height="100"';
        $result = $this->Email->send();
        $this->assertContains($expected, $result['message']);
    }

    /**
     * testSendRenderPlugin method
     *
     * @return void
     */
    public function testSendRenderPlugin()
    {
        $this->loadPlugins(['TestPlugin', 'TestPluginTwo', 'TestTheme']);

        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);

        $this->Email->viewBuilder()
            ->setTemplate('TestPlugin.test_plugin_tpl')
            ->setLayout('default');
        $result = $this->Email->send();
        $this->assertContains('Into TestPlugin.', $result['message']);
        $this->assertContains('This email was sent using the CakePHP Framework', $result['message']);

        $this->Email->viewBuilder()
            ->setTemplate('TestPlugin.test_plugin_tpl')
            ->setLayout('TestPlugin.plug_default');
        $result = $this->Email->send();
        $this->assertContains('Into TestPlugin.', $result['message']);
        $this->assertContains('This email was sent using the TestPlugin.', $result['message']);

        $this->Email->viewBuilder()
            ->setTemplate('TestPlugin.test_plugin_tpl')
            ->setLayout('plug_default');
        $result = $this->Email->send();
        $this->assertContains('Into TestPlugin.', $result['message']);
        $this->assertContains('This email was sent using the TestPlugin.', $result['message']);

        $this->Email->viewBuilder()
            ->setTemplate('TestPlugin.test_plugin_tpl')
            ->setLayout('TestPluginTwo.default');
        $result = $this->Email->send();
        $this->assertContains('Into TestPlugin.', $result['message']);
        $this->assertContains('This email was sent using TestPluginTwo.', $result['message']);

        // test plugin template overridden by theme
        $this->Email->viewBuilder()->setTheme('TestTheme');
        $result = $this->Email->send();

        $this->assertContains('Into TestPlugin. (themed)', $result['message']);

        $this->Email->setViewVars(['value' => 12345]);
        $this->Email->viewBuilder()
            ->setTemplate('custom')
            ->setLayout('TestPlugin.plug_default');
        $result = $this->Email->send();
        $this->assertContains('Here is your value: 12345', $result['message']);
        $this->assertContains('This email was sent using the TestPlugin.', $result['message']);
        $this->clearPlugins();
    }

    /**
     * Test that a MissingTemplateException is thrown
     *
     * @return void
     * @expectedException \Cake\View\Exception\MissingTemplateException
     */
    public function testMissingTemplateException()
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->viewBuilder()->setTemplate('fooo');
        $this->Email->send();
    }

    /**
     * testSendMultipleMIME method
     *
     * @return void
     */
    public function testSendMultipleMIME()
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->viewBuilder()->setTemplate('custom', 'default');
        $this->Email->setProfile([]);
        $this->Email->setViewVars(['value' => 12345]);
        $this->Email->setEmailFormat('both');
        $this->Email->send();

        $message = $this->Email->message();
        $boundary = $this->Email->getBoundary();
        $this->assertNotEmpty($boundary);
        $this->assertContains('--' . $boundary, $message);
        $this->assertContains('--' . $boundary . '--', $message);

        $this->Email->setAttachments(['fake.php' => __FILE__]);
        $this->Email->send();

        $message = $this->Email->message();
        $boundary = $this->Email->getBoundary();
        $this->assertNotEmpty($boundary);
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
        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile([]);
        $this->Email->setAttachments([CAKE . 'basics.php']);
        $result = $this->Email->send('body');
        $expected = "Content-Disposition: attachment; filename=\"basics.php\"\r\n" .
            "Content-Type: text/x-php\r\n" .
            "Content-Transfer-Encoding: base64\r\n";
        $this->assertContains($expected, $result['message']);

        $this->Email->setAttachments(['my.file.txt' => CAKE . 'basics.php']);
        $result = $this->Email->send('body');
        $expected = "Content-Disposition: attachment; filename=\"my.file.txt\"\r\n" .
            "Content-Type: text/x-php\r\n" .
            "Content-Transfer-Encoding: base64\r\n";
        $this->assertContains($expected, $result['message']);

        $this->Email->setAttachments(['file.txt' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain']]);
        $result = $this->Email->send('body');
        $expected = "Content-Disposition: attachment; filename=\"file.txt\"\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n";
        $this->assertContains($expected, $result['message']);

        $this->Email->setAttachments(['file2.txt' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain', 'contentId' => 'a1b1c1']]);
        $result = $this->Email->send('body');
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
        $this->deprecated(function () {
            Email::dropTransport('default');
            Email::setConfigTransport('default', ['className' => 'Debug']);
        });

        $instance = Email::deliver('all@cakephp.org', 'About', 'Everything ok', ['from' => 'root@cakephp.org'], false);
        $this->assertInstanceOf('Cake\Mailer\Email', $instance);
        $this->assertSame($instance->getTo(), ['all@cakephp.org' => 'all@cakephp.org']);
        $this->assertSame($instance->getSubject(), 'About');
        $this->assertSame($instance->getFrom(), ['root@cakephp.org' => 'root@cakephp.org']);
        $this->assertInstanceOf('Cake\Mailer\AbstractTransport', $instance->getTransport());

        $config = [
            'from' => 'cake@cakephp.org',
            'to' => 'debug@cakephp.org',
            'subject' => 'Update ok',
            'template' => 'custom',
            'layout' => 'custom_layout',
            'viewVars' => ['value' => 123],
            'cc' => ['cake@cakephp.org' => 'Myself'],
        ];
        $instance = Email::deliver(null, null, ['name' => 'CakePHP'], $config, false);
        $this->assertSame($instance->getFrom(), ['cake@cakephp.org' => 'cake@cakephp.org']);
        $this->assertSame($instance->getTo(), ['debug@cakephp.org' => 'debug@cakephp.org']);
        $this->assertSame($instance->getSubject(), 'Update ok');
        $this->assertSame($instance->viewBuilder()->getTemplate(), 'custom');
        $this->assertSame($instance->viewBuilder()->getLayout(), 'custom_layout');
        $this->assertEquals($instance->getViewVars(), ['value' => 123, 'name' => 'CakePHP']);
        $this->assertSame($instance->getCc(), ['cake@cakephp.org' => 'Myself']);

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
        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()->setTemplate('default', 'default');
        $this->Email->setEmailFormat('both');
        $this->Email->send();

        $expected = '<p>This email was sent using the <a href="https://cakephp.org">CakePHP Framework</a></p>';
        $this->assertContains($expected, $this->Email->message(Email::MESSAGE_HTML));

        $expected = 'This email was sent using the CakePHP Framework, https://cakephp.org.';
        $this->assertContains($expected, $this->Email->message(Email::MESSAGE_TEXT));

        $message = $this->Email->message();
        $this->assertContains('Content-Type: text/plain; charset=UTF-8', $message);
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $message);

        // UTF-8 is 8bit
        $this->assertTrue($this->_checkContentTransferEncoding($message, '8bit'));

        $this->Email->charset = 'ISO-2022-JP';
        $this->Email->send();
        $message = $this->Email->message();
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
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->viewBuilder()->setTheme('TestTheme');
        $this->Email->setEmailPattern('/.+@.+\..+/i');
        $this->assertSame(['cake@cakephp.org' => 'cake@cakephp.org'], $this->Email->getTo());

        $this->Email->reset();
        $this->assertSame([], $this->Email->getTo());
        $this->assertFalse($this->Email->viewBuilder()->getTheme());
        $this->assertSame(Email::EMAIL_PATTERN, $this->Email->getEmailPattern());
    }

    /**
     * testReset with charset
     *
     * @return void
     */
    public function testResetWithCharset()
    {
        $this->Email->charset = 'ISO-2022-JP';
        $this->Email->reset();

        $this->assertSame('utf-8', $this->Email->charset, $this->Email->charset);
        $this->assertNull($this->Email->headerCharset, $this->Email->headerCharset);
    }

    /**
     * testWrap method
     *
     * @return void
     */
    public function testWrap()
    {
        $text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
        $result = $this->Email->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac turpis orci,',
            'non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan amet.';
        $result = $this->Email->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur < adipiscing elit. Donec ac turpis',
            'orci, non commodo odio. Morbi nibh nisi, vehicula > pellentesque accumsan',
            'amet.',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = '<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula pellentesque accumsan amet.<hr></p>';
        $result = $this->Email->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            '<p>Lorem ipsum dolor sit amet,<br> consectetur adipiscing elit.<br> Donec ac',
            'turpis orci, non <b>commodo</b> odio. <br /> Morbi nibh nisi, vehicula',
            'pellentesque accumsan amet.<hr></p>',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac <a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh nisi, vehicula pellentesque accumsan amet.';
        $result = $this->Email->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ac',
            '<a href="http://cakephp.org">turpis</a> orci, non commodo odio. Morbi nibh',
            'nisi, vehicula pellentesque accumsan amet.',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum <a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">ok</a>';
        $result = $this->Email->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum',
            '<a href="http://www.cakephp.org/controller/action/param1/param2" class="nice cool fine amazing awesome">',
            'ok</a>',
            '',
        ];
        $this->assertSame($expected, $result);

        $text = 'Lorem ipsum withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite ok.';
        $result = $this->Email->wrap($text, Email::LINE_LENGTH_SHOULD);
        $expected = [
            'Lorem ipsum',
            'withonewordverybigMorethanthelineshouldsizeofrfcspecificationbyieeeavailableonieeesite',
            'ok.',
            '',
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
        $this->Email->setEmailFormat('html');
        $this->Email->viewBuilder()->setTemplate('html', 'default');
        $this->Email->setAttachments([CAKE . 'basics.php']);
        $result = $this->Email->render([]);
        $this->assertNotEmpty($result);

        $result = $this->Email->getBoundary();
        $this->assertRegExp('/^[0-9a-f]{32}$/', $result);
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
        $this->Email = new Email($configs);

        $result = $this->Email->getTo();
        $this->assertEquals([$configs['to'] => $configs['to']], $result);

        $result = $this->Email->getFrom();
        $this->assertEquals($configs['from'], $result);

        $result = $this->Email->getSubject();
        $this->assertEquals($configs['subject'], $result);

        $result = $this->Email->getTransport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $result = $this->Email->send('This is the message');

        $this->assertContains('Message-ID: ', $result['headers']);
        $this->assertContains('To: ', $result['headers']);
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
            'layout' => 'custom',
        ];
        $this->Email = new Email($configs);

        $template = $this->Email->viewBuilder()->getTemplate();
        $layout = $this->Email->viewBuilder()->getLayout();
        $this->assertEquals('', $template);
        $this->assertEquals($configs['layout'], $layout);
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
        Email::setConfig('test', $configs);

        $this->Email = new Email('test');

        $result = $this->Email->getTo();
        $this->assertEquals([$configs['to'] => $configs['to']], $result);

        $result = $this->Email->getFrom();
        $this->assertEquals($configs['from'], $result);

        $result = $this->Email->getSubject();
        $this->assertEquals($configs['subject'], $result);

        $result = $this->Email->getTransport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $result = $this->Email->send('This is the message');

        $this->assertContains('Message-ID: ', $result['headers']);
        $this->assertContains('To: ', $result['headers']);
    }

    /**
     * testViewRender method
     *
     * @return void
     */
    public function testViewRender()
    {
        $result = $this->Email->getViewRenderer();
        $this->assertEquals('Cake\View\View', $result);

        $result = $this->Email->setViewRenderer('Cake\View\ThemeView');
        $this->assertInstanceOf('Cake\Mailer\Email', $result);

        $result = $this->Email->getViewRenderer();
        $this->assertEquals('Cake\View\ThemeView', $result);
    }

    /**
     * testEmailFormat method
     *
     * @return void
     */
    public function testEmailFormat()
    {
        $result = $this->Email->getEmailFormat();
        $this->assertEquals('text', $result);

        $result = $this->Email->setEmailFormat('html');
        $this->assertInstanceOf('Cake\Mailer\Email', $result);

        $result = $this->Email->getEmailFormat();
        $this->assertEquals('html', $result);

        $this->expectException(\InvalidArgumentException::class);
        $result = $this->Email->setEmailFormat('invalid');
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
        $email->setSubject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = '?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=';
        $this->assertContains($expected, $headers['Subject']);

        $email->setTo('someone@example.com')->setFrom('someone@example.com');
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
            'transport' => 'debug',
        ]);
        $email->setSubject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = '?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=';
        $this->assertContains($expected, $headers['Subject']);

        $email->setTo('someone@example.com')->setFrom('someone@example.com');
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
            'transport' => 'debug',
        ]);
        $email->setSubject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = '?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=';
        $this->assertContains($expected, $headers['Subject']);

        $email->setTo('someone@example.com')->setFrom('someone@example.com');
        $result = $email->send('①㈱');
        $this->assertTextContains('Content-Type: text/plain; charset=ISO-2022-JP', $result['headers']);
        $this->assertTextNotContains('Content-Type: text/plain; charset=ISO-2022-JP-MS', $result['headers']); // not charset=iso-2022-jp-ms
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
            'transport' => 'debug',
        ]);
        $email->setSubject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = '?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=';
        $this->assertContains($expected, $headers['Subject']);

        $email->setTo('someone@example.com')->setFrom('someone@example.com');
        $result = $email->send('①㈱');
        $this->assertTextContains('Content-Type: text/plain; charset=ISO-2022-JP', $result['headers']);
        $this->assertTextNotContains('Content-Type: text/plain; charset=iso-2022-jp-ms', $result['headers']); // not charset=iso-2022-jp-ms
        $this->assertContains(mb_convert_encoding('①㈱', 'ISO-2022-JP-MS'), $result['message']);
    }

    protected function _checkContentTransferEncoding($message, $charset)
    {
        $boundary = '--' . $this->Email->getBoundary();
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
        $this->Email->headerCharset = 'ISO-2022-JP';
        $result = $this->Email->encode('日本語');
        $expected = '=?ISO-2022-JP?B?GyRCRnxLXDhsGyhC?=';
        $this->assertSame($expected, $result);

        $this->Email->headerCharset = 'ISO-2022-JP';
        $result = $this->Email->encode('長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？');
        $expected = "=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
            " =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
            ' =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=';
        $this->assertSame($expected, $result);
    }

    /**
     * Test CakeEmail::_decode function
     *
     * @return void
     */
    public function testDecode()
    {
        $this->Email->headerCharset = 'ISO-2022-JP';
        $result = $this->Email->decode('=?ISO-2022-JP?B?GyRCRnxLXDhsGyhC?=');
        $expected = '日本語';
        $this->assertSame($expected, $result);

        $this->Email->headerCharset = 'ISO-2022-JP';
        $result = $this->Email->decode("=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
            " =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
            ' =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=');
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
        $this->Email->setCharset('UTF-8');
        $this->assertSame($this->Email->getCharset(), 'UTF-8');

        $this->Email->setCharset('ISO-2022-JP');
        $this->assertSame($this->Email->getCharset(), 'ISO-2022-JP');

        $charset = $this->Email->setCharset('Shift_JIS');
        $this->assertSame('Shift_JIS', $charset->getCharset());
    }

    /**
     * Tests headerCharset setter/getter
     *
     * @return void
     */
    public function testHeaderCharset()
    {
        $this->Email->setHeaderCharset('UTF-8');
        $this->assertSame($this->Email->getHeaderCharset(), 'UTF-8');

        $this->Email->setHeaderCharset('ISO-2022-JP');
        $this->assertSame($this->Email->getHeaderCharset(), 'ISO-2022-JP');

        $charset = $this->Email->setHeaderCharset('Shift_JIS');
        $this->assertSame('Shift_JIS', $charset->getHeaderCharset());
    }

    /**
     * Tests headerCharset on reset
     *
     * @return void
     */
    public function testHeaderCharsetReset()
    {
        $email = new Email(['headerCharset' => 'ISO-2022-JP']);
        $email->reset();

        $this->assertSame('utf-8', $email->getCharset());
        $this->assertSame('utf-8', $email->getHeaderCharset());
    }

    /**
     * Test transferEncoding
     *
     * @return void
     */
    public function testTransferEncoding()
    {
        // Test new transfer encoding
        $expected = 'quoted-printable';
        $this->Email->setTransferEncoding($expected);
        $this->assertSame($expected, $this->Email->getTransferEncoding());
        $this->assertSame($expected, $this->Email->getContentTransferEncoding());

        // Test default charset/encoding : utf8/8bit
        $expected = '8bit';
        $this->Email->reset();
        $this->assertNull($this->Email->getTransferEncoding());
        $this->assertSame($expected, $this->Email->getContentTransferEncoding());

        //Test wrong encoding
        $this->expectException(\InvalidArgumentException::class);
        $this->Email->setTransferEncoding('invalid');
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
     * @return \Cake\Mailer\Email
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

        $email->setFrom('someone@example.com', 'どこかの誰か');
        $email->setTo('someperson@example.jp', 'どこかのどなたか');
        $email->setCc('miku@example.net', 'ミク');
        $email->setSubject('テストメール');
        $email->send('テストメールの本文');

        return $email;
    }

    /**
     * @param mixed $charset
     * @param mixed $headerCharset
     * @return \Cake\Mailer\Email
     */
    protected function _getEmailByNewStyleCharset($charset, $headerCharset)
    {
        $email = new Email(['transport' => 'debug']);

        if (! empty($charset)) {
            $email->setCharset($charset);
        }
        if (! empty($headerCharset)) {
            $email->setHeaderCharset($headerCharset);
        }

        $email->setFrom('someone@example.com', 'どこかの誰か');
        $email->setTo('someperson@example.jp', 'どこかのどなたか');
        $email->setCc('miku@example.net', 'ミク');
        $email->setSubject('テストメール');
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
        $message = '<a href="http://cakephp.org">' . str_repeat('x', Email::LINE_LENGTH_MUST) . '</a>';

        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('Wordwrap Test');
        $this->Email->setProfile(['empty']);
        $result = $this->Email->send($message);
        $expected = "<a\r\n" . 'href="http://cakephp.org">' . str_repeat('x', Email::LINE_LENGTH_MUST - 26) . "\r\n" .
            str_repeat('x', 26) . "\r\n</a>\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $str1 = 'a ';
        $str2 = ' b';
        $length = strlen($str1) + strlen($str2);
        $message = $str1 . str_repeat('x', Email::LINE_LENGTH_MUST - $length - 1) . $str2;

        $result = $this->Email->send($message);
        $expected = "{$message}\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $message = $str1 . str_repeat('x', Email::LINE_LENGTH_MUST - $length) . $str2;

        $result = $this->Email->send($message);
        $expected = "{$message}\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $message = $str1 . str_repeat('x', Email::LINE_LENGTH_MUST - $length + 1) . $str2;

        $result = $this->Email->send($message);
        $expected = $str1 . str_repeat('x', Email::LINE_LENGTH_MUST - $length + 1) . sprintf("\r\n%s\r\n\r\n", trim($str2));
        $this->assertEquals($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $line = 'some text <b>with html</b>';
        $trailing = str_repeat('X', Email::LINE_LENGTH_MUST - strlen($line));
        $result = $this->Email->send($line . $trailing);
        $expected = 'some text <b>with' . "\r\nhtml</b>" . $trailing . "\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
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

        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('Wordwrap Test');
        $this->Email->setProfile(['empty']);
        $result = $this->Email->send($message);
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

        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('Wordwrap Test');
        $this->Email->setProfile(['empty']);
        $result = $this->Email->send($message);
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

        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('Wordwrap Test');
        $this->Email->setProfile(['empty']);
        $this->Email->setCharset('iso-2022-jp');
        $this->Email->setHeaderCharset('iso-2022-jp');
        $result = $this->Email->send($message);
        $expected = "{$message}\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
    }

    /**
     * CakeEmailTest::testMockTransport()
     */
    public function testMockTransport()
    {
        $this->deprecated(function () {
            Email::dropTransport('default');
        });

        $mock = $this->getMockBuilder('\Cake\Mailer\AbstractTransport')->getMock();
        $config = ['from' => 'tester@example.org', 'transport' => 'default'];

        Email::setConfig('default', $config);
        $this->deprecated(function () use ($mock) {
            Email::setConfigTransport('default', $mock);
        });

        $em = new Email('default');

        $this->assertSame($mock, $em->getTransport());
    }

    /**
     * testZeroOnlyLinesNotBeingEmptied()
     *
     * @return void
     */
    public function testZeroOnlyLinesNotBeingEmptied()
    {
        $message = "Lorem\r\n0\r\n0\r\nipsum";

        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('Wordwrap Test');
        $result = $this->Email->send($message);
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

        $this->Email->reset()
            ->setTo(['cakephp@cakephp.org' => 'CakePHP'])
            ->setFrom('noreply@cakephp.org')
            ->setReplyTo('cakephp@cakephp.org')
            ->setCc(['mark@cakephp.org', 'juan@cakephp.org' => 'Juan Basso'])
            ->setBcc('phpnut@cakephp.org')
            ->setSubject('Test Serialize')
            ->setMessageId('<uuid@server.com>')
            ->setDomain('foo.bar')
            ->setViewVars([
                'users' => $this->getTableLocator()->get('Users')->get(1, ['fields' => ['id', 'username']]),
                'xml' => new SimpleXmlElement($xmlstr),
                'exception' => new Exception('test'),
            ])
            ->setAttachments([
                'test.txt' => TEST_APP . 'config' . DS . 'empty.ini',
                'image' => [
                    'data' => file_get_contents(TEST_APP . 'webroot' . DS . 'img' . DS . 'cake.icon.png'),
                    'mimetype' => 'image/png',
                ],
            ]);

        $this->Email->viewBuilder()
            ->setTemplate('default')
            ->setLayout('test');

        $result = json_decode(json_encode($this->Email), true);
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
                    'username' => 'mariano',
                ],
                'xml' => [
                    'name' => 'CakePHP',
                    'url' => 'http://cakephp.org',
                ],
            ],
            '_attachments' => [
                'test.txt' => [
                    'data' => $encode(TEST_APP . 'config' . DS . 'empty.ini'),
                    'mimetype' => 'text/plain',
                ],
                'image' => [
                    'data' => $encode(TEST_APP . 'webroot' . DS . 'img' . DS . 'cake.icon.png'),
                    'mimetype' => 'image/png',
                ],
            ],
            '_emailPattern' => '/^((?:[\p{L}0-9.!#$%&\'*+\/=?^_`{|}~-]+)*@[\p{L}0-9-._]+)$/ui',
        ];
        $this->assertEquals($expected, $result);

        $result = json_decode(json_encode(unserialize(serialize($this->Email))), true);
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

    /**
     * Test deprecated methods
     *
     * @return void
     */
    public function testDeprecatedMethods()
    {
        $this->deprecated(function () {
            $this->Email
                ->setTemplate('foo')
                ->setLayout('bar')
                ->setTheme('baz')
                ->setHelpers(['A', 'B']);

            $this->assertSame('foo', $this->Email->getTemplate());
            $this->assertSame('bar', $this->Email->getLayout());
            $this->assertSame('baz', $this->Email->getTheme());
            $this->assertSame(['A', 'B'], $this->Email->getHelpers());

            $this->Email->setLayout('');
            $this->assertFalse($this->Email->getLayout());
        });
    }
}
