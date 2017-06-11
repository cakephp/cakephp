<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Mailer\Email;

/**
 * Email and mailer assertions.
 *
 * @method \PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount any()
 * @method void assertSame($expected, $result, $message)
 * @method void assertTextContains($needle, $haystack, $message)
 * @method \PHPUnit_Framework_MockObject_MockBuilder getMockBuilder($className)
 */
trait EmailAssertTrait
{

    /**
     * @var \Cake\Mailer\Email
     */
    protected $_email;

    /**
     * Sends email using the test email instance.
     *
     * @param array|string|null $content The email's content to send.
     * @return void
     */
    public function send($content = null)
    {
        $this->email(true)->send($content);
    }

    /**
     * Creates an email instance overriding its transport for testing purposes.
     *
     * @param bool $new Tells if new instance should forcibly be created.
     * @return \Cake\Mailer\Email
     */
    public function email($new = false)
    {
        if ($new || !$this->_email) {
            $this->_email = new Email();
            $this->_email->setProfile(['transport' => 'debug'] + $this->_email->getProfile());
        }

        return $this->_email;
    }

    /**
     * Generates mock for given mailer class.
     *
     * @param string $className The mailer's FQCN.
     * @param array $methods The methods to mock on the mailer.
     * @return \Cake\Mailer\Mailer|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockForMailer($className, array $methods = [])
    {
        $name = current(array_slice(explode('\\', $className), -1));

        if (!in_array('profile', $methods)) {
            $methods[] = 'profile';
        }

        $mailer = $this->getMockBuilder($className)
            ->setMockClassName($name)
            ->setMethods($methods)
            ->setConstructorArgs([$this->email()])
            ->getMock();

        $mailer->expects($this->any())
            ->method('profile')
            ->willReturn($mailer);

        return $mailer;
    }

    /**
     * Asserts email content (both text and HTML) contains `$needle`.
     *
     * @param string $needle Text to look for.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailMessageContains($needle, $message = null)
    {
        $this->assertEmailHtmlMessageContains($needle, $message);
        $this->assertEmailTextMessageContains($needle, $message);
    }

    /**
     * Asserts HTML email content contains `$needle`.
     *
     * @param string $needle Text to look for.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailHtmlMessageContains($needle, $message = null)
    {
        $haystack = $this->email()->message('html');
        $this->assertTextContains($needle, $haystack, $message);
    }

    /**
     * Asserts text email content contains `$needle`.
     *
     * @param string $needle Text to look for.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailTextMessageContains($needle, $message = null)
    {
        $haystack = $this->email()->message('text');
        $this->assertTextContains($needle, $haystack, $message);
    }

    /**
     * Asserts email's subject contains `$expected`.
     *
     * @param string $expected Email's subject.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailSubject($expected, $message = null)
    {
        $result = $this->email()->getSubject();
        $this->assertSame($expected, $result, $message);
    }

    /**
     * Asserts email's sender email address and optionally name.
     *
     * @param string $email Sender's email address.
     * @param string|null $name Sender's name.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailFrom($email, $name = null, $message = null)
    {
        if ($name === null) {
            $name = $email;
        }

        $expected = [$email => $name];
        $result = $this->email()->getFrom();
        $this->assertSame($expected, $result, $message);
    }

    /**
     * Asserts email is CC'd to only one email address (and optionally name).
     *
     * @param string $email CC'd email address.
     * @param string|null $name CC'd person name.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailCc($email, $name = null, $message = null)
    {
        if ($name === null) {
            $name = $email;
        }

        $expected = [$email => $name];
        $result = $this->email()->getCc();
        $this->assertSame($expected, $result, $message);
    }

    /**
     * Asserts email CC'd addresses contain given email address (and
     * optionally name).
     *
     * @param string $email CC'd email address.
     * @param string|null $name CC'd person name.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailCcContains($email, $name = null, $message = null)
    {
        $result = $this->email()->getCc();
        $this->assertNotEmpty($result[$email], $message);
        if ($name !== null) {
            $this->assertEquals($result[$email], $name, $message);
        }
    }

    /**
     * Asserts email is BCC'd to only one email address (and optionally name).
     *
     * @param string $email BCC'd email address.
     * @param string|null $name BCC'd person name.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailBcc($email, $name = null, $message = null)
    {
        if ($name === null) {
            $name = $email;
        }

        $expected = [$email => $name];
        $result = $this->email()->getBcc();
        $this->assertSame($expected, $result, $message);
    }

    /**
     * Asserts email BCC'd addresses contain given email address (and
     * optionally name).
     *
     * @param string $email BCC'd email address.
     * @param string|null $name BCC'd person name.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailBccContains($email, $name = null, $message = null)
    {
        $result = $this->email()->getBcc();
        $this->assertNotEmpty($result[$email], $message);
        if ($name !== null) {
            $this->assertEquals($result[$email], $name, $message);
        }
    }

    /**
     * Asserts email is sent to only the given recipient's address (and
     * optionally name).
     *
     * @param string $email Recipient's email address.
     * @param string|null $name Recipient's name.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailTo($email, $name = null, $message = null)
    {
        if ($name === null) {
            $name = $email;
        }

        $expected = [$email => $name];
        $result = $this->email()->getTo();
        $this->assertSame($expected, $result, $message);
    }

    /**
     * Asserts email recipients' list contains given email address (and
     * optionally name).
     *
     * @param string $email Recipient's email address.
     * @param string|null $name Recipient's name.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailToContains($email, $name = null, $message = null)
    {
        $result = $this->email()->getTo();
        $this->assertNotEmpty($result[$email], $message);
        if ($name !== null) {
            $this->assertEquals($result[$email], $name, $message);
        }
    }

    /**
     * Asserts the email attachments contain the given filename (and optionally
     * file info).
     *
     * @param string $filename Expected attachment's filename.
     * @param array|null $file Expected attachment's file info.
     * @param string|null $message The failure message to define.
     * @return void
     */
    public function assertEmailAttachmentsContains($filename, array $file = null, $message = null)
    {
        $result = $this->email()->getAttachments();
        $this->assertNotEmpty($result[$filename], $message);
        if ($file === null) {
            return;
        }
        $this->assertContains($file, $result, $message);
        $this->assertEquals($file, $result[$filename], $message);
    }
}
