<?php
/**
 * MailTransportTest file
 *
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
namespace Cake\Test\TestCase\Mailer\Transport;

use Cake\TestSuite\TestCase;

/**
 * Test case
 *
 */
class MailTransportTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->MailTransport = $this->getMockBuilder('Cake\Mailer\Transport\MailTransport')
            ->setMethods(['_mail'])
            ->getMock();
        $this->MailTransport->config(['additionalParameters' => '-f']);
    }

    /**
     * testSend method
     *
     * @return void
     */
    public function testSendData()
    {
        $email = $this->getMockBuilder('Cake\Mailer\Email')
            ->setMethods(['message'])
            ->getMock();
        $email->from('noreply@cakephp.org', 'CakePHP Test');
        $email->returnPath('pleasereply@cakephp.org', 'CakePHP Return');
        $email->to('cake@cakephp.org', 'CakePHP');
        $email->cc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
        $email->bcc('phpnut@cakephp.org');
        $email->messageID('<4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>');
        $longNonAscii = 'Foø Bår Béz Foø Bår Béz Foø Bår Béz Foø Bår Béz';
        $email->subject($longNonAscii);
        $date = date(DATE_RFC2822);
        $email->setHeaders([
            'X-Mailer' => 'CakePHP Email',
            'Date' => $date,
            'X-add' => mb_encode_mimeheader($longNonAscii, 'utf8', 'B'),
        ]);
        $email->expects($this->any())->method('message')
            ->will($this->returnValue(['First Line', 'Second Line', '.Third Line', '']));

        $encoded = '=?UTF-8?B?Rm/DuCBCw6VyIELDqXogRm/DuCBCw6VyIELDqXogRm/DuCBCw6VyIELDqXog?=';
        $encoded .= ' =?UTF-8?B?Rm/DuCBCw6VyIELDqXo=?=';

        $data = "From: CakePHP Test <noreply@cakephp.org>" . PHP_EOL;
        $data .= "Return-Path: CakePHP Return <pleasereply@cakephp.org>" . PHP_EOL;
        $data .= "Cc: Mark Story <mark@cakephp.org>, Juan Basso <juan@cakephp.org>" . PHP_EOL;
        $data .= "Bcc: phpnut@cakephp.org" . PHP_EOL;
        $data .= "X-Mailer: CakePHP Email" . PHP_EOL;
        $data .= "Date: " . $date . PHP_EOL;
        $data .= "X-add: " . $encoded . PHP_EOL;
        $data .= "Message-ID: <4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>" . PHP_EOL;
        $data .= "MIME-Version: 1.0" . PHP_EOL;
        $data .= "Content-Type: text/plain; charset=UTF-8" . PHP_EOL;
        $data .= "Content-Transfer-Encoding: 8bit";

        $this->MailTransport->expects($this->once())->method('_mail')
            ->with(
                'CakePHP <cake@cakephp.org>',
                $encoded,
                implode(PHP_EOL, ['First Line', 'Second Line', '.Third Line', '']),
                $data,
                '-f'
            );

        $this->MailTransport->send($email);
    }
}
