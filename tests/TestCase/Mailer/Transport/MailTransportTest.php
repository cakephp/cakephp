<?php
declare(strict_types=1);

/**
 * MailTransportTest file
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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer\Transport;

use Cake\Core\Exception\CakeException;
use Cake\Mailer\Message;
use Cake\TestSuite\TestCase;

/**
 * Test case
 */
class MailTransportTest extends TestCase
{
    /**
     * @var \Cake\Mailer\Transport\MailTransport|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $MailTransport;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->MailTransport = $this->getMockBuilder('Cake\Mailer\Transport\MailTransport')
            ->onlyMethods(['_mail'])
            ->getMock();
        $this->MailTransport->setConfig(['additionalParameters' => '-f']);
    }

    /**
     * testSendWithoutRecipient method
     *
     * @return void
     */
    public function testSendWithoutRecipient()
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('You must specify at least one recipient. Use one of `setTo`, `setCc` or `setBcc` to define a recipient.');

        $message = new Message();
        $this->MailTransport->send($message);
    }

    /**
     * testSend method
     *
     * @return void
     */
    public function testSendData()
    {
        $message = new Message();
        $message->setFrom('noreply@cakephp.org', 'CakePHP Test');
        $message->setReturnPath('pleasereply@cakephp.org', 'CakePHP Return');
        $message->setTo('cake@cakephp.org', 'CakePHP');
        $message->setReplyTo(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
        $message->setCc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
        $message->setBcc('phpnut@cakephp.org');
        $message->setMessageId('<4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>');
        $longNonAscii = 'Foø Bår Béz Foø Bår Béz Foø Bår Béz Foø Bår Béz';
        $message->setSubject($longNonAscii);
        $date = date(DATE_RFC2822);
        $message->setHeaders([
            'X-Mailer' => 'CakePHP Email',
            'Date' => $date,
            'X-add' => mb_encode_mimeheader($longNonAscii, 'utf8', 'B'),
        ]);
        $message->setBody(['text' => "First Line\nSecond Line\n.Third Line"]);

        $encoded = '=?UTF-8?B?Rm/DuCBCw6VyIELDqXogRm/DuCBCw6VyIELDqXogRm/DuCBCw6VyIELDqXog?=';
        $encoded .= ' =?UTF-8?B?Rm/DuCBCw6VyIELDqXo=?=';

        $data = 'From: CakePHP Test <noreply@cakephp.org>' . PHP_EOL;
        $data .= 'Reply-To: Mark Story <mark@cakephp.org>, Juan Basso <juan@cakephp.org>' . PHP_EOL;
        $data .= 'Return-Path: CakePHP Return <pleasereply@cakephp.org>' . PHP_EOL;
        $data .= 'Cc: Mark Story <mark@cakephp.org>, Juan Basso <juan@cakephp.org>' . PHP_EOL;
        $data .= 'Bcc: phpnut@cakephp.org' . PHP_EOL;
        $data .= 'X-Mailer: CakePHP Email' . PHP_EOL;
        $data .= 'Date: ' . $date . PHP_EOL;
        $data .= 'X-add: ' . $encoded . PHP_EOL;
        $data .= 'Message-ID: <4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>' . PHP_EOL;
        $data .= 'MIME-Version: 1.0' . PHP_EOL;
        $data .= 'Content-Type: text/plain; charset=UTF-8' . PHP_EOL;
        $data .= 'Content-Transfer-Encoding: 8bit';

        $this->MailTransport->expects($this->once())->method('_mail')
            ->with(
                'CakePHP <cake@cakephp.org>',
                $encoded,
                implode(PHP_EOL, ['First Line', 'Second Line', '.Third Line', '', '']),
                $data,
                '-f'
            );

        $result = $this->MailTransport->send($message);

        $this->assertStringContainsString('Subject: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
    }
}
