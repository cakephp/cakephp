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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Mailer\Email;
use Cake\Mailer\Mailer;
use Cake\Mailer\Message;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\MissingTemplateException;
use Exception;
use SimpleXmlElement;
use TestApp\Mailer\TestEmail;

/**
 * EmailTest class
 */
class EmailTest extends TestCase
{
    protected $fixtures = ['core.Users'];

    /**
     * @var \TestApp\Mailer\TestEmail
     */
    protected $Email;

    /**
     * @var array
     */
    protected $transports = [];

    /**
     * setUp
     */
    public function setUp(): void
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

        TransportFactory::setConfig($this->transports);
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        Log::drop('email');
        Email::drop('default');
        Email::drop('test');
        TransportFactory::drop('debug');
        TransportFactory::drop('badClassName');
        TransportFactory::drop('test_smtp');
    }

    /**
     * testFrom method
     */
    public function testFrom(): void
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
     */
    public function testFromWithColonsAndQuotes(): void
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
        $this->assertStringContainsString('From: "70:20:00 \" Forum" <info@example.com>', $result['headers']);
    }

    /**
     * testSender method
     */
    public function testSender(): void
    {
        $this->Email->reset();
        $this->assertSame([], $this->Email->getSender());

        $this->Email->setSender('cake@cakephp.org', 'Name');
        $expected = ['cake@cakephp.org' => 'Name'];
        $this->assertSame($expected, $this->Email->getSender());

        $headers = $this->Email->getHeaders(['from' => true, 'sender' => true]);
        $this->assertSame('', $headers['From']);
        $this->assertSame('Name <cake@cakephp.org>', $headers['Sender']);

        $this->Email->setFrom('cake@cakephp.org', 'CakePHP');
        $headers = $this->Email->getHeaders(['from' => true, 'sender' => true]);
        $this->assertSame('CakePHP <cake@cakephp.org>', $headers['From']);
        $this->assertSame('', $headers['Sender']);
    }

    /**
     * testTo method
     */
    public function testTo(): void
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
     */
    public function testToUnderscoreDomain(): void
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
        $this->expectException(\InvalidArgumentException::class);
        $this->Email->setTo($value);
    }

    /**
     * testBuildInvalidData
     *
     * @dataProvider invalidEmails
     * @param array|string $value
     */
    public function testInvalidEmailAdd($value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->Email->addTo($value);
    }

    /**
     * test emailPattern method
     */
    public function testEmailPattern(): void
    {
        $regex = '/.+@.+\..+/i';
        $this->assertSame($regex, $this->Email->setEmailPattern($regex)->getEmailPattern());
    }

    /**
     * Tests that it is possible to set email regex configuration to a CakeEmail object
     */
    public function testConfigEmailPattern(): void
    {
        $regex = '/.+@.+\..+/i';
        $email = new Email(['emailPattern' => $regex]);
        $this->assertSame($regex, $email->getEmailPattern());
    }

    /**
     * Tests that it is possible set custom email validation
     */
    public function testCustomEmailValidation(): void
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
     */
    public function testClassNameException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Mailer transport TestFalse is not available.');
        $email = new Email();
        $email->setTransport('badClassName');
    }

    /**
     * Tests that it is possible to unset the email pattern and make use of filter_var() instead.
     */
    public function testUnsetEmailPattern(): void
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
     */
    public function testEmptyTo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The email set for "to" is empty.');
        $email = new Email();
        $email->setTo('');
    }

    /**
     * testFormatAddress method
     */
    public function testFormatAddress(): void
    {
        $result = $this->Email->getMessage()->fmtAddress(['cake@cakephp.org' => 'cake@cakephp.org']);
        $expected = ['cake@cakephp.org'];
        $this->assertSame($expected, $result);

        $result = $this->Email->getMessage()->fmtAddress([
            'cake@cakephp.org' => 'cake@cakephp.org',
            'php@cakephp.org' => 'php@cakephp.org',
        ]);
        $expected = ['cake@cakephp.org', 'php@cakephp.org'];
        $this->assertSame($expected, $result);

        $result = $this->Email->getMessage()->fmtAddress([
            'cake@cakephp.org' => 'CakePHP',
            'php@cakephp.org' => 'Cake',
        ]);
        $expected = ['CakePHP <cake@cakephp.org>', 'Cake <php@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->getMessage()->fmtAddress(['me@example.com' => 'Last, First']);
        $expected = ['"Last, First" <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->getMessage()->fmtAddress(['me@example.com' => '"Last" First']);
        $expected = ['"\"Last\" First" <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->getMessage()->fmtAddress(['me@example.com' => 'Last First']);
        $expected = ['Last First <me@example.com>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->getMessage()->fmtAddress(['cake@cakephp.org' => 'ÄÖÜTest']);
        $expected = ['=?UTF-8?B?w4TDlsOcVGVzdA==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->getMessage()->fmtAddress(['cake@cakephp.org' => '日本語Test']);
        $expected = ['=?UTF-8?B?5pel5pys6KqeVGVzdA==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);
    }

    /**
     * testFormatAddressJapanese
     */
    public function testFormatAddressJapanese(): void
    {
        $this->Email->setHeaderCharset('ISO-2022-JP');
        $result = $this->Email->getMessage()->fmtAddress(['cake@cakephp.org' => '日本語Test']);
        $expected = ['=?ISO-2022-JP?B?GyRCRnxLXDhsGyhCVGVzdA==?= <cake@cakephp.org>'];
        $this->assertSame($expected, $result);

        $result = $this->Email->getMessage()->fmtAddress(['cake@cakephp.org' => '寿限無寿限無五劫の擦り切れ海砂利水魚の水行末雲来末風来末食う寝る処に住む処やぶら小路の藪柑子パイポパイポパイポのシューリンガンシューリンガンのグーリンダイグーリンダイのポンポコピーのポンポコナーの長久命の長助']);
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
        $this->Email->addReplyTo('replyto2@cakephp.org', 'ReplyTo2 CakePHP');

        $this->assertSame($this->Email->getFrom(), ['cake@cakephp.org' => 'CakePHP']);
        $this->assertSame($this->Email->getReplyTo(), ['replyto@cakephp.org' => 'ReplyTo CakePHP', 'replyto2@cakephp.org' => 'ReplyTo2 CakePHP']);
        $this->assertSame($this->Email->getReadReceipt(), ['readreceipt@cakephp.org' => 'ReadReceipt CakePHP']);
        $this->assertSame($this->Email->getReturnPath(), ['returnpath@cakephp.org' => 'ReturnPath CakePHP']);
        $this->assertSame($this->Email->getTo(), ['to@cakephp.org' => 'To, CakePHP', 'to2@cakephp.org' => 'To2 CakePHP']);
        $this->assertSame($this->Email->getCc(), ['cc@cakephp.org' => 'Cc CakePHP', 'cc2@cakephp.org' => 'Cc2 CakePHP']);
        $this->assertSame($this->Email->getBcc(), ['bcc@cakephp.org' => 'Bcc CakePHP', 'bcc2@cakephp.org' => 'Bcc2 CakePHP']);

        $headers = $this->Email->getHeaders(array_fill_keys(['from', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc'], true));
        $this->assertSame($headers['From'], 'CakePHP <cake@cakephp.org>');
        $this->assertSame($headers['Reply-To'], 'ReplyTo CakePHP <replyto@cakephp.org>, ReplyTo2 CakePHP <replyto2@cakephp.org>');
        $this->assertSame($headers['Disposition-Notification-To'], 'ReadReceipt CakePHP <readreceipt@cakephp.org>');
        $this->assertSame($headers['Return-Path'], 'ReturnPath CakePHP <returnpath@cakephp.org>');
        $this->assertSame($headers['To'], '"To, CakePHP" <to@cakephp.org>, To2 CakePHP <to2@cakephp.org>');
        $this->assertSame($headers['Cc'], 'Cc CakePHP <cc@cakephp.org>, Cc2 CakePHP <cc2@cakephp.org>');
        $this->assertSame($headers['Bcc'], 'Bcc CakePHP <bcc@cakephp.org>, Bcc2 CakePHP <bcc2@cakephp.org>');

        $this->Email->setReplyTo(['replyto@cakephp.org' => 'ReplyTo CakePHP', 'replyto2@cakephp.org' => 'ReplyTo2 CakePHP']);
        $this->assertSame($this->Email->getReplyTo(), ['replyto@cakephp.org' => 'ReplyTo CakePHP', 'replyto2@cakephp.org' => 'ReplyTo2 CakePHP']);
        $headers = $this->Email->getHeaders(array_fill_keys(['replyTo'], true));
        $this->assertSame($headers['Reply-To'], 'ReplyTo CakePHP <replyto@cakephp.org>, ReplyTo2 CakePHP <replyto2@cakephp.org>');
    }

    /**
     * test reset addresses method
     */
    public function testResetAddresses(): void
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
     */
    public function testMessageId(): void
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

    public function testAutoMessageIdIsIdempotent(): void
    {
        $headers = $this->Email->getHeaders();
        $this->assertArrayHasKey('Message-ID', $headers);

        $regeneratedHeaders = $this->Email->getHeaders();
        $this->assertSame($headers['Message-ID'], $regeneratedHeaders['Message-ID']);
    }

    public function testPriority(): void
    {
        $this->Email->setPriority(4);

        $this->assertSame(4, $this->Email->getPriority());

        $result = $this->Email->getHeaders();
        $this->assertArrayHasKey('X-Priority', $result);
    }

    /**
     * testMessageIdInvalid method
     */
    public function testMessageIdInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->Email->setMessageId('my-email@localhost');
    }

    /**
     * testDomain method
     */
    public function testDomain(): void
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
     */
    public function testMessageIdWithDomain(): void
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
     */
    public function testSubject(): void
    {
        $this->Email->setSubject('You have a new message.');
        $this->assertSame('You have a new message.', $this->Email->getSubject());

        $this->Email->setSubject('You have a new message, I think.');
        $this->assertSame($this->Email->getSubject(), 'You have a new message, I think.');
        $this->Email->setSubject('1');
        $this->assertSame('1', $this->Email->getSubject());

        $input = 'هذه رسالة بعنوان طويل مرسل للمستلم';
        $this->Email->setSubject($input);
        $expected = '=?UTF-8?B?2YfYsNmHINix2LPYp9mE2Kkg2KjYudmG2YjYp9mGINi32YjZitmEINmF2LE=?=' . "\r\n" . ' =?UTF-8?B?2LPZhCDZhNmE2YXYs9iq2YTZhQ==?=';
        $this->assertSame($expected, $this->Email->getSubject());
        $this->assertSame($input, $this->Email->getOriginalSubject());
    }

    /**
     * testSubjectJapanese
     */
    public function testSubjectJapanese(): void
    {
        mb_internal_encoding('UTF-8');

        $this->Email->setHeaderCharset('ISO-2022-JP');
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
     */
    public function testHeaders(): void
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

        $this->Email->setCharset('ISO-2022-JP');
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
     */
    public function testTemplate(): void
    {
        $this->Email->viewBuilder()->setTemplate('template');
        $this->assertSame('template', $this->Email->viewBuilder()->getTemplate());
    }

    /**
     * testLayout method
     */
    public function testLayout(): void
    {
        $this->Email->viewBuilder()->setLayout('layout');
        $this->assertSame('layout', $this->Email->viewBuilder()->getLayout());
    }

    /**
     * testTheme method
     */
    public function testTheme(): void
    {
        $this->assertNull($this->Email->viewBuilder()->getTheme());

        $this->Email->viewBuilder()->setTheme('default');
        $expected = 'default';
        $this->assertSame($expected, $this->Email->viewBuilder()->getTheme());
    }

    /**
     * testViewVars method
     */
    public function testViewVars(): void
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
     */
    public function testSetAttachments(): void
    {
        $this->Email->setAttachments([CAKE . 'basics.php']);
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
        $this->Email->addAttachments([CORE_PATH . 'config' . DS . 'bootstrap.php']);
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
     */
    public function testSetAttachmentDataNoMimetype(): void
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
     */
    public function testTransport(): void
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
    public function testTransportInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "Invalid" transport configuration does not exist');
        $this->Email->setTransport('Invalid');
    }

    /**
     * Test that using classes with no send method fails.
     */
    public function testTransportInstanceInvalid(): void
    {
        $this->expectException(\LogicException::class);
        $this->Email->setTransport(new \stdClass());
    }

    /**
     * Test that using unknown transports fails.
     */
    public function testTransportTypeInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The value passed for the "$name" argument must be either a string, or an object, integer given.');
        $this->Email->setTransport(123);
    }

    /**
     * Test reading/writing configuration profiles.
     */
    public function testConfig(): void
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
     */
    public function testConfigErrorOnDuplicate(): void
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
     */
    public function testProfile(): void
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
     */
    public function testDefaultProfile(): void
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
    public function testProfileInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown email configuration "derp".');
        $email = new Email();
        $email->setProfile('derp');
    }

    /**
     * testConfigString method
     */
    public function testUseConfigString(): void
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
        $this->assertSame($config['subject'], $result);

        $result = $this->Email->viewBuilder()->getTheme();
        $this->assertSame($config['theme'], $result);

        $result = $this->Email->getTransport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $result = $this->Email->viewBuilder()->getHelpers();
        $this->assertEquals($config['helpers'], $result);
    }

    /**
     * Calling send() with no parameters should not overwrite the view variables.
     */
    public function testSendWithNoContentDoesNotOverwriteViewVar(): void
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
        $this->assertStringContainsString('A message to you', $result['message']);
    }

    /**
     * testSendWithContent method
     */
    public function testSendWithContent(): void
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

        $this->assertSame($expected, $result['message']);
        $this->assertStringContainsString('Date: ', $result['headers']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);

        $result = $this->Email->send('Other body');
        $expected = "Other body\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);

        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $result = $this->Email->send(['Sending content', 'As array']);
        $expected = "Sending content\r\nAs array\r\n\r\n";
        $this->assertSame($expected, $result['message']);
    }

    /**
     * test send without a transport method
     */
    public function testSendWithoutTransport(): void
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
     */
    public function testSendNoTemplateWithAttachments(): void
    {
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setEmailFormat('text');
        $this->Email->setAttachments([CAKE . 'basics.php']);
        $result = $this->Email->send('Hello');

        $boundary = $this->Email->getBoundary();
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
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
        $this->assertStringContainsString($expected, $result['message']);
    }

    /**
     * Test send() with no template and data string attachment
     */
    public function testSendNoTemplateWithDataStringAttachment(): void
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
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
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
        $this->assertStringContainsString($expected, $result['message']);
    }

    /**
     * Test send() with no template as both
     */
    public function testSendNoTemplateWithAttachmentsAsBoth(): void
    {
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setEmailFormat('both');
        $this->Email->setAttachments([CORE_PATH . 'VERSION.txt']);
        $result = $this->Email->send('Hello');

        $boundary = $this->Email->getBoundary();
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
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
        $this->assertStringContainsString($expected, $result['message']);
    }

    /**
     * Test setting inline attachments and messages.
     */
    public function testSendWithInlineAttachments(): void
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
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
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
        $this->assertStringContainsString($expected, $result['message']);
        $this->assertStringContainsString('--rel-' . $boundary . '--', $result['message']);
        $this->assertStringContainsString('--' . $boundary . '--', $result['message']);
    }

    /**
     * Test setting inline attachments and HTML only messages.
     */
    public function testSendWithInlineAttachmentsHtmlOnly(): void
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
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
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
        $this->assertStringContainsString($expected, $result['message']);
        $this->assertStringContainsString('--rel-' . $boundary . '--', $result['message']);
        $this->assertStringContainsString('--' . $boundary . '--', $result['message']);
    }

    /**
     * Test disabling content-disposition.
     */
    public function testSendWithNoContentDispositionAttachments(): void
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
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
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

        $this->assertStringContainsString($expected, $result['message']);
        $this->assertStringContainsString('--' . $boundary . '--', $result['message']);
    }

    /**
     * Test an attachment filename with non-ASCII characters.
     */
    public function testSendWithNonAsciiFilenameAttachments(): void
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
        $this->assertStringContainsString(
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
            $result['headers']
        );
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
        $this->assertStringContainsString($expected, $result['message']);
        $this->assertStringContainsString('--rel-' . $boundary . '--', $result['message']);
        $this->assertStringContainsString('--' . $boundary . '--', $result['message']);
    }

    /**
     * testSendWithLog method
     */
    public function testSendWithLog(): void
    {
        Log::setConfig('email', [
            'className' => 'Array',
        ]);

        $this->Email->setTransport('debug');
        $this->Email->setTo('me@cakephp.org');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['log' => 'debug']);

        $text = 'Logging This';
        $result = $this->Email->send($text);
        $this->assertNotEmpty($result);

        $messages = Log::engine('email')->read();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString($text, $messages[0]);
        $this->assertStringContainsString('cake@cakephp.org', $messages[0]);
        $this->assertStringContainsString('me@cakephp.org', $messages[0]);
    }

    /**
     * testSendWithLogAndScope method
     */
    public function testSendWithLogAndScope(): void
    {
        Log::setConfig('email', [
            'className' => 'Array',
            'scopes' => ['email'],
        ]);

        $this->Email->setTransport('debug');
        $this->Email->setTo('me@cakephp.org');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['log' => ['scope' => 'email']]);

        $text = 'Logging This';
        $this->Email->send($text);

        $messages = Log::engine('email')->read();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString($text, $messages[0]);
        $this->assertStringContainsString('cake@cakephp.org', $messages[0]);
        $this->assertStringContainsString('me@cakephp.org', $messages[0]);
    }

    /**
     * testSendRender method
     */
    public function testSendRender(): void
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()->setTemplate('default', 'default');
        $result = $this->Email->send();

        $this->assertStringContainsString('This email was sent using the CakePHP Framework', $result['message']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
    }

    /**
     * test sending and rendering with no layout
     */
    public function testSendRenderNoLayout(): void
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setConfig(['empty']);
        $this->Email->viewBuilder()
            ->setTemplate('default')
            ->disableAutoLayout();
        $result = $this->Email->send('message body.');

        $this->assertStringContainsString('message body.', $result['message']);
        $this->assertStringNotContainsString('This email was sent using the CakePHP Framework', $result['message']);
    }

    /**
     * testSendRender both method
     */
    public function testSendRenderBoth(): void
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

        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);

        $boundary = $this->Email->getBoundary();
        $this->assertStringContainsString('Content-Type: multipart/alternative; boundary="' . $boundary . '"', $result['headers']);

        $expected = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            'This email was sent using the CakePHP Framework, https://cakephp.org.' .
            "\r\n" .
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
            "\r\n" .
            "--$boundary--\r\n";
        $this->assertStringEndsWith($expected, $result['message']);
    }

    /**
     * testSendRender method for ISO-2022-JP
     */
    public function testSendRenderJapanese(): void
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()->setTemplate('default');
        $this->Email->viewBuilder()->setLayout('japanese');
        $this->Email->setCharset('ISO-2022-JP');
        $result = $this->Email->send();

        $expected = mb_convert_encoding('CakePHP Framework を使って送信したメールです。 https://cakephp.org.', 'ISO-2022-JP');
        $this->assertStringContainsString($expected, $result['message']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
    }

    /**
     * testSendRenderThemed method
     */
    public function testSendRenderThemed(): void
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

        $this->assertStringContainsString('In TestTheme', $result['message']);
        $this->assertStringContainsString('/test_theme/img/test.jpg', $result['message']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
        $this->assertStringContainsString('/test_theme/img/test.jpg', $result['message']);
        $this->clearPlugins();
    }

    /**
     * testSendRenderWithHTML method and assert line length is kept below the required limit
     */
    public function testSendRenderWithHTML(): void
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
     */
    public function testSendRenderWithVars(): void
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

        $this->assertStringContainsString('Here is your value: 12345', $result['message']);
    }

    /**
     * testSendRenderWithVars method for ISO-2022-JP
     */
    public function testSendRenderWithVarsJapanese(): void
    {
        $this->Email->reset();
        $this->Email->setTransport('debug');

        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo(['you@cakephp.org' => 'You']);
        $this->Email->setSubject('My title');
        $this->Email->setProfile(['empty']);
        $this->Email->viewBuilder()->setTemplate('japanese', 'default');
        $this->Email->setViewVars(['value' => '日本語の差し込み123']);
        $this->Email->setCharset('ISO-2022-JP');
        $result = $this->Email->send();

        $expected = mb_convert_encoding('ここにあなたの設定した値が入ります: 日本語の差し込み123', 'ISO-2022-JP');
        $this->assertStringContainsString($expected, $result['message']);
    }

    /**
     * testSendRenderWithHelpers method
     */
    public function testSendRenderWithHelpers(): void
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
        $this->assertStringContainsString('Right now: ' . $dateTime->format($dateTime::ATOM), $result['message']);

        $result = $this->Email->viewBuilder()->getHelpers();
        $this->assertEquals(['Time'], $result);
    }

    /**
     * testSendRenderWithImage method
     */
    public function testSendRenderWithImage(): void
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

        if (env('SERVER_PORT') && env('SERVER_PORT') !== 80) {
            $server .= ':' . env('SERVER_PORT');
        }

        $expected = '<img src="http://' . $server . '/img/image.gif" alt="cool image" width="100" height="100"';
        $result = $this->Email->send();
        $this->assertStringContainsString($expected, $result['message']);
    }

    /**
     * testSendRenderPlugin method
     */
    public function testSendRenderPlugin(): void
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
        $this->assertStringContainsString('Into TestPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using the CakePHP Framework', $result['message']);

        $this->Email->viewBuilder()
            ->setTemplate('TestPlugin.test_plugin_tpl')
            ->setLayout('TestPlugin.plug_default');
        $result = $this->Email->send();
        $this->assertStringContainsString('Into TestPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using the TestPlugin.', $result['message']);

        $this->Email->viewBuilder()
            ->setTemplate('TestPlugin.test_plugin_tpl')
            ->setLayout('plug_default');
        $result = $this->Email->send();
        $this->assertStringContainsString('Into TestPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using the TestPlugin.', $result['message']);

        $this->Email->viewBuilder()
            ->setTemplate('TestPlugin.test_plugin_tpl')
            ->setLayout('TestPluginTwo.default');
        $result = $this->Email->send();
        $this->assertStringContainsString('Into TestPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using TestPluginTwo.', $result['message']);

        // test plugin template overridden by theme
        $this->Email->viewBuilder()->setTheme('TestTheme');
        $result = $this->Email->send();

        $this->assertStringContainsString('Into TestPlugin. (themed)', $result['message']);

        $this->Email->setViewVars(['value' => 12345]);
        $this->Email->viewBuilder()
            ->setTemplate('custom')
            ->setLayout('TestPlugin.plug_default');
        $result = $this->Email->send();
        $this->assertStringContainsString('Here is your value: 12345', $result['message']);
        $this->assertStringContainsString('This email was sent using the TestPlugin.', $result['message']);
        $this->clearPlugins();
    }

    /**
     * Test that a MissingTemplateException is thrown
     */
    public function testMissingTemplateException(): void
    {
        $this->expectException(MissingTemplateException::class);

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
     */
    public function testSendMultipleMIME(): void
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
     */
    public function testSendAttachment(): void
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
        $this->assertStringContainsString($expected, $result['message']);

        $this->Email->setAttachments(['my.file.txt' => CAKE . 'basics.php']);
        $result = $this->Email->send('body');
        $expected = "Content-Disposition: attachment; filename=\"my.file.txt\"\r\n" .
            "Content-Type: text/x-php\r\n" .
            "Content-Transfer-Encoding: base64\r\n";
        $this->assertStringContainsString($expected, $result['message']);

        $this->Email->setAttachments(['file.txt' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain']]);
        $result = $this->Email->send('body');
        $expected = "Content-Disposition: attachment; filename=\"file.txt\"\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n";
        $this->assertStringContainsString($expected, $result['message']);

        $this->Email->setAttachments(['file2.txt' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain', 'contentId' => 'a1b1c1']]);
        $result = $this->Email->send('body');
        $expected = "Content-Disposition: inline; filename=\"file2.txt\"\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "Content-ID: <a1b1c1>\r\n";
        $this->assertStringContainsString($expected, $result['message']);
    }

    /**
     * testDeliver method
     */
    public function testDeliver(): void
    {
        TransportFactory::drop('default');
        TransportFactory::setConfig('default', ['className' => 'Debug']);

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
        $this->assertSame($configs['message'], $message[0]);
    }

    /**
     * testMessage method
     */
    public function testMessage(): void
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
        $this->assertStringContainsString($expected, $this->Email->message(Email::MESSAGE_HTML));

        $expected = 'This email was sent using the CakePHP Framework, https://cakephp.org.';
        $this->assertStringContainsString($expected, $this->Email->message(Email::MESSAGE_TEXT));

        $message = $this->Email->message();
        $this->assertContains('Content-Type: text/plain; charset=UTF-8', $message);
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $message);

        // UTF-8 is 8bit
        $this->assertTrue($this->_checkContentTransferEncoding($message, '8bit'));

        $this->Email->setCharset('ISO-2022-JP');
        $this->Email->send();
        $message = $this->Email->message();
        $this->assertContains('Content-Type: text/plain; charset=ISO-2022-JP', $message);
        $this->assertContains('Content-Type: text/html; charset=ISO-2022-JP', $message);

        // ISO-2022-JP is 7bit
        $this->assertTrue($this->_checkContentTransferEncoding($message, '7bit'));
    }

    /**
     * testReset method
     */
    public function testReset(): void
    {
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->viewBuilder()->setTheme('TestTheme');
        $this->Email->setEmailPattern('/.+@.+\..+/i');
        $this->assertSame(['cake@cakephp.org' => 'cake@cakephp.org'], $this->Email->getTo());

        $this->Email->reset();
        $this->assertSame([], $this->Email->getTo());
        $this->assertNull($this->Email->viewBuilder()->getTheme());
        $this->assertSame(Email::EMAIL_PATTERN, $this->Email->getEmailPattern());
    }

    /**
     * testReset with charset
     */
    public function testResetWithCharset(): void
    {
        $this->Email->setCharset('ISO-2022-JP');
        $this->Email->reset();

        $this->assertSame('utf-8', $this->Email->getCharset());
        $this->assertSame('utf-8', $this->Email->getHeaderCharset());
    }

    /**
     * testRender method
     */
    public function testRenderWithLayoutAndAttachment(): void
    {
        $this->Email->setEmailFormat('html');
        $this->Email->viewBuilder()->setTemplate('html', 'default');
        $this->Email->setAttachments([CAKE . 'basics.php']);
        $this->Email->render();
        $result = $this->Email->message();
        $this->assertNotEmpty($result);

        $result = $this->Email->getBoundary();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result);
    }

    /**
     * testConstructWithConfigArray method
     */
    public function testConstructWithConfigArray(): void
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
        $this->assertSame($configs['subject'], $result);

        $result = $this->Email->getTransport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $result = $this->Email->send('This is the message');

        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
    }

    /**
     * testConfigArrayWithLayoutWithoutTemplate method
     */
    public function testConfigArrayWithLayoutWithoutTemplate(): void
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
        $this->assertNull($template);
        $this->assertSame($configs['layout'], $layout);
    }

    /**
     * testConstructWithConfigString method
     */
    public function testConstructWithConfigString(): void
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
        $this->assertSame($configs['subject'], $result);

        $result = $this->Email->getTransport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $result = $this->Email->send('This is the message');

        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
    }

    /**
     * testViewRender method
     */
    public function testViewRender(): void
    {
        $result = $this->Email->getViewRenderer();
        $this->assertSame('Cake\View\View', $result);

        $result = $this->Email->setViewRenderer('Cake\View\ThemeView');
        $this->assertInstanceOf('Cake\Mailer\Email', $result);

        $result = $this->Email->getViewRenderer();
        $this->assertSame('Cake\View\ThemeView', $result);
    }

    /**
     * testEmailFormat method
     */
    public function testEmailFormat(): void
    {
        $result = $this->Email->getEmailFormat();
        $this->assertSame('text', $result);

        $result = $this->Email->setEmailFormat('html');
        $this->assertInstanceOf('Cake\Mailer\Email', $result);

        $result = $this->Email->getEmailFormat();
        $this->assertSame('html', $result);

        $this->expectException(\InvalidArgumentException::class);
        $this->Email->setEmailFormat('invalid');
    }

    /**
     * Tests that it is possible to add charset configuration to a CakeEmail object
     */
    public function testConfigCharset(): void
    {
        $email = new Email();
        $this->assertEquals(Configure::read('App.encoding'), $email->getCharset());
        $this->assertEquals(Configure::read('App.encoding'), $email->getHeaderCharset());

        $email = new Email(['charset' => 'iso-2022-jp', 'headerCharset' => 'iso-2022-jp-ms']);
        $this->assertSame('iso-2022-jp', $email->getCharset());
        $this->assertSame('iso-2022-jp-ms', $email->getHeaderCharset());

        $email = new Email(['charset' => 'iso-2022-jp']);
        $this->assertSame('iso-2022-jp', $email->getCharset());
        $this->assertSame('iso-2022-jp', $email->getHeaderCharset());

        $email = new Email(['headerCharset' => 'iso-2022-jp-ms']);
        $this->assertEquals(Configure::read('App.encoding'), $email->getCharset());
        $this->assertSame('iso-2022-jp-ms', $email->getHeaderCharset());
    }

    /**
     * Tests that the header is encoded using the configured headerCharset
     */
    public function testHeaderEncoding(): void
    {
        $email = new Email(['headerCharset' => 'iso-2022-jp-ms', 'transport' => 'debug']);
        $email->setSubject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = '?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=';
        $this->assertStringContainsString($expected, $headers['Subject']);

        $email->setTo('someone@example.com')->setFrom('someone@example.com');
        $result = $email->send('ってテーブルを作ってやってたらう');
        $this->assertStringContainsString('ってテーブルを作ってやってたらう', $result['message']);
    }

    /**
     * Tests that the body is encoded using the configured charset
     */
    public function testBodyEncoding(): void
    {
        $email = new Email([
            'charset' => 'iso-2022-jp',
            'headerCharset' => 'iso-2022-jp-ms',
            'transport' => 'debug',
        ]);
        $email->setSubject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = '?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=';
        $this->assertStringContainsString($expected, $headers['Subject']);

        $email->setTo('someone@example.com')->setFrom('someone@example.com');
        $result = $email->send('ってテーブルを作ってやってたらう');
        $this->assertStringContainsString('Content-Type: text/plain; charset=ISO-2022-JP', $result['headers']);
        $this->assertStringContainsString(mb_convert_encoding('ってテーブルを作ってやってたらう', 'ISO-2022-JP'), $result['message']);
    }

    /**
     * Tests that the body is encoded using the configured charset (Japanese standard encoding)
     */
    public function testBodyEncodingIso2022Jp(): void
    {
        $email = new Email([
            'charset' => 'iso-2022-jp',
            'headerCharset' => 'iso-2022-jp',
            'transport' => 'debug',
        ]);
        $email->setSubject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = '?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=';
        $this->assertStringContainsString($expected, $headers['Subject']);

        $email->setTo('someone@example.com')->setFrom('someone@example.com');
        $result = $email->send('①㈱');
        $this->assertTextContains('Content-Type: text/plain; charset=ISO-2022-JP', $result['headers']);
        $this->assertTextNotContains('Content-Type: text/plain; charset=ISO-2022-JP-MS', $result['headers']); // not charset=iso-2022-jp-ms
        $this->assertTextNotContains(mb_convert_encoding('①㈱', 'ISO-2022-JP-MS'), $result['message']);
    }

    /**
     * Tests that the body is encoded using the configured charset (Japanese irregular encoding, but sometime use this)
     */
    public function testBodyEncodingIso2022JpMs(): void
    {
        $email = new Email([
            'charset' => 'iso-2022-jp-ms',
            'headerCharset' => 'iso-2022-jp-ms',
            'transport' => 'debug',
        ]);
        $email->setSubject('あれ？もしかしての前と');
        $headers = $email->getHeaders(['subject']);
        $expected = '?ISO-2022-JP?B?GyRCJCIkbCEpJGIkNyQrJDckRiROQTAkSBsoQg==?=';
        $this->assertStringContainsString($expected, $headers['Subject']);

        $email->setTo('someone@example.com')->setFrom('someone@example.com');
        $result = $email->send('①㈱');
        $this->assertTextContains('Content-Type: text/plain; charset=ISO-2022-JP', $result['headers']);
        $this->assertTextNotContains('Content-Type: text/plain; charset=iso-2022-jp-ms', $result['headers']); // not charset=iso-2022-jp-ms
        $this->assertStringContainsString(mb_convert_encoding('①㈱', 'ISO-2022-JP-MS'), $result['message']);
    }

    /**
     * @param array|string $message
     */
    protected function _checkContentTransferEncoding($message, string $charset): bool
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
     */
    public function testEncode(): void
    {
        $this->Email->setHeaderCharset('ISO-2022-JP');
        $result = $this->Email->getMessage()->encode('日本語');
        $expected = '=?ISO-2022-JP?B?GyRCRnxLXDhsGyhC?=';
        $this->assertSame($expected, $result);

        $this->Email->setHeaderCharset('ISO-2022-JP');
        $result = $this->Email->getMessage()->encode('長い長い長いSubjectの場合はfoldingするのが正しいんだけどいったいどうなるんだろう？');
        $expected = "=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
            " =?ISO-2022-JP?B?ZGluZxskQiQ5JGskTiQsQDUkNyQkJHMkQCQxJEkkJCRDJD8kJCRJGyhC?=\r\n" .
            ' =?ISO-2022-JP?B?GyRCJCYkSiRrJHMkQCRtJCYhKRsoQg==?=';
        $this->assertSame($expected, $result);
    }

    /**
     * Test CakeEmail::_decode function
     */
    public function testDecode(): void
    {
        $this->Email->setHeaderCharset('ISO-2022-JP');
        $result = $this->Email->getMessage()->decode('=?ISO-2022-JP?B?GyRCRnxLXDhsGyhC?=');
        $expected = '日本語';
        $this->assertSame($expected, $result);

        $this->Email->setHeaderCharset('ISO-2022-JP');
        $result = $this->Email->getMessage()->decode("=?ISO-2022-JP?B?GyRCRDkkJEQ5JCREOSQkGyhCU3ViamVjdBskQiROPmw5ZyRPGyhCZm9s?=\r\n" .
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
        $this->Email->setCharset('UTF-8');
        $this->assertSame($this->Email->getCharset(), 'UTF-8');

        $this->Email->setCharset('ISO-2022-JP');
        $this->assertSame($this->Email->getCharset(), 'ISO-2022-JP');

        $charset = $this->Email->setCharset('Shift_JIS');
        $this->assertSame('Shift_JIS', $charset->getCharset());
    }

    /**
     * Tests headerCharset setter/getter
     */
    public function testHeaderCharset(): void
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
     */
    public function testHeaderCharsetReset(): void
    {
        $email = new Email(['headerCharset' => 'ISO-2022-JP']);
        $email->reset();

        $this->assertSame('utf-8', $email->getCharset());
        $this->assertSame('utf-8', $email->getHeaderCharset());
    }

    /**
     * Test transferEncoding
     */
    public function testTransferEncoding(): void
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
    protected function _getEmailByOldStyleCharset($charset, $headerCharset): Email
    {
        $email = new Email(['transport' => 'debug']);

        if (!empty($charset)) {
            $email->setCharset($charset);
        }
        if (!empty($headerCharset)) {
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
     * @param mixed $charset
     * @param mixed $headerCharset
     */
    protected function _getEmailByNewStyleCharset($charset, $headerCharset): Email
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
     */
    public function testWrapLongLine(): void
    {
        $message = '<a href="http://cakephp.org">' . str_repeat('x', Message::LINE_LENGTH_MUST) . '</a>';

        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('Wordwrap Test');
        $this->Email->setProfile(['empty']);
        $result = $this->Email->send($message);
        $expected = "<a\r\n" . 'href="http://cakephp.org">' . str_repeat('x', Message::LINE_LENGTH_MUST - 26) . "\r\n" .
            str_repeat('x', 26) . "\r\n</a>\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $str1 = 'a ';
        $str2 = ' b';
        $length = strlen($str1) + strlen($str2);
        $message = $str1 . str_repeat('x', Message::LINE_LENGTH_MUST - $length - 1) . $str2;

        $result = $this->Email->send($message);
        $expected = "{$message}\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $message = $str1 . str_repeat('x', Message::LINE_LENGTH_MUST - $length) . $str2;

        $result = $this->Email->send($message);
        $expected = "{$message}\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertLineLengths($result['message']);

        $message = $str1 . str_repeat('x', Message::LINE_LENGTH_MUST - $length + 1) . $str2;

        $result = $this->Email->send($message);
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

        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('Wordwrap Test');
        $this->Email->setProfile(['empty']);
        $result = $this->Email->send($message);
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
        $this->assertSame($expected, $result['message']);
    }

    /**
     * CakeEmailTest::testMockTransport()
     */
    public function testMockTransport(): void
    {
        TransportFactory::drop('default');

        $mock = $this->getMockBuilder('Cake\Mailer\AbstractTransport')->getMock();
        $config = ['from' => 'tester@example.org', 'transport' => 'default'];

        Email::setConfig('default', $config);
        TransportFactory::setConfig('default', $mock);

        $em = new Email('default');

        $this->assertSame($mock, $em->getTransport());
    }

    /**
     * testZeroOnlyLinesNotBeingEmptied()
     */
    public function testZeroOnlyLinesNotBeingEmptied(): void
    {
        $message = "Lorem\r\n0\r\n0\r\nipsum";

        $this->Email->reset();
        $this->Email->setTransport('debug');
        $this->Email->setFrom('cake@cakephp.org');
        $this->Email->setTo('cake@cakephp.org');
        $this->Email->setSubject('Wordwrap Test');
        $result = $this->Email->send($message);
        $expected = "{$message}\r\n\r\n";
        $this->assertSame($expected, $result['message']);
    }

    /**
     * testJsonSerialize()
     */
    public function testJsonSerialize(): void
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
        $this->assertStringContainsString('test', $result['viewConfig']['_vars']['exception']);
        unset($result['viewConfig']['_vars']['exception']);

        $encode = function ($path) {
            return chunk_split(base64_encode(file_get_contents($path)), 76, "\r\n");
        };

        $expected = [
            'to' => ['cakephp@cakephp.org' => 'CakePHP'],
            'from' => ['noreply@cakephp.org' => 'noreply@cakephp.org'],
            'replyTo' => ['cakephp@cakephp.org' => 'cakephp@cakephp.org'],
            'cc' => ['mark@cakephp.org' => 'mark@cakephp.org', 'juan@cakephp.org' => 'Juan Basso'],
            'bcc' => ['phpnut@cakephp.org' => 'phpnut@cakephp.org'],
            'subject' => 'Test Serialize',
            'emailFormat' => 'text',
            'messageId' => '<uuid@server.com>',
            'domain' => 'foo.bar',
            'appCharset' => 'UTF-8',
            'charset' => 'utf-8',
            'viewConfig' => [
                '_template' => 'default',
                '_layout' => 'test',
                '_helpers' => ['Html'],
                '_className' => 'Cake\View\View',
                '_autoLayout' => true,
                '_vars' => [
                    'users' => [
                        'id' => 1,
                        'username' => 'mariano',
                    ],
                    'xml' => [
                        'name' => 'CakePHP',
                        'url' => 'http://cakephp.org',
                    ],
                ],
            ],
            'attachments' => [
                'test.txt' => [
                    'data' => $encode(TEST_APP . 'config' . DS . 'empty.ini'),
                    'mimetype' => 'text/plain',
                ],
                'image' => [
                    'data' => $encode(TEST_APP . 'webroot' . DS . 'img' . DS . 'cake.icon.png'),
                    'mimetype' => 'image/png',
                ],
            ],
            'emailPattern' => '/^((?:[\p{L}0-9.!#$%&\'*+\/=?^_`{|}~-]+)*@[\p{L}0-9-._]+)$/ui',
        ];
        $this->assertEquals($expected, $result);

        $result = json_decode(json_encode(unserialize(serialize($this->Email))), true);
        $this->assertStringContainsString('test', $result['viewConfig']['_vars']['exception']);
        unset($result['viewConfig']['_vars']['exception']);
        $this->assertEquals($expected, $result);
    }

    /**
     * testStaticMethodProxy
     */
    public function testStaticMethodProxy(): void
    {
        Email::setConfig('proxy_test', ['yay']);
        $this->assertEquals(['yay'], Mailer::getConfig('proxy_test'));

        Email::drop('proxy_test');
        $this->assertSame([], Mailer::configured());
    }

    /**
     * CakeEmailTest::assertLineLengths()
     *
     * @param string $message
     */
    public function assertLineLengths($message): void
    {
        $lines = explode("\r\n", $message);
        foreach ($lines as $line) {
            $this->assertTrue(
                strlen($line) <= Message::LINE_LENGTH_MUST,
                'Line length exceeds the max. limit of Message::LINE_LENGTH_MUST'
            );
        }
    }
}
