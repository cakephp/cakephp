<?php
declare(strict_types=1);

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
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\TestSuite\Constraint\Email\MailContains;
use Cake\TestSuite\Constraint\Email\MailContainsAttachment;
use Cake\TestSuite\Constraint\Email\MailContainsHtml;
use Cake\TestSuite\Constraint\Email\MailContainsText;
use Cake\TestSuite\Constraint\Email\MailCount;
use Cake\TestSuite\Constraint\Email\MailSentFrom;
use Cake\TestSuite\Constraint\Email\MailSentTo;
use Cake\TestSuite\Constraint\Email\MailSentWith;
use Cake\TestSuite\Constraint\Email\MailSubjectContains;
use Cake\TestSuite\Constraint\Email\NoMailSent;

/**
 * Make assertions on emails sent through the Cake\TestSuite\TestEmailTransport
 *
 * After adding the trait to your test case, all mail transports will be replaced
 * with TestEmailTransport which is used for making assertions and will *not* actually
 * send emails.
 */
trait EmailTrait
{
    /**
     * Replaces all transports with the test transport during test setup
     *
     * @before
     * @return void
     */
    public function setupTransports(): void
    {
        TestEmailTransport::replaceAllTransports();
    }

    /**
     * Resets transport state
     *
     * @after
     * @return void
     */
    public function cleanupEmailTrait(): void
    {
        TestEmailTransport::clearMessages();
    }

    /**
     * Asserts an expected number of emails were sent
     *
     * @param int $count Email count
     * @param string $message Message
     * @return void
     */
    public function assertMailCount(int $count, string $message = ''): void
    {
        $this->assertThat($count, new MailCount(), $message);
    }

    /**
     * Asserts that no emails were sent
     *
     * @param string $message Message
     * @return void
     */
    public function assertNoMailSent(string $message = ''): void
    {
        $this->assertThat(null, new NoMailSent(), $message);
    }

    /**
     * Asserts an email at a specific index was sent to an address
     *
     * @param int $at Email index
     * @param string $address Email address
     * @param string $message Message
     * @return void
     */
    public function assertMailSentToAt(int $at, string $address, string $message = ''): void
    {
        $this->assertThat($address, new MailSentTo($at), $message);
    }

    /**
     * Asserts an email at a specific index was sent from an address
     *
     * @param int $at Email index
     * @param string $address Email address
     * @param string $message Message
     * @return void
     */
    public function assertMailSentFromAt(int $at, string $address, string $message = ''): void
    {
        $this->assertThat($address, new MailSentFrom($at), $message);
    }

    /**
     * Asserts an email at a specific index contains expected contents
     *
     * @param int $at Email index
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsAt(int $at, string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailContains($at), $message);
    }

    /**
     * Asserts an email at a specific index contains expected html contents
     *
     * @param int $at Email index
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsHtmlAt(int $at, string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailContainsHtml($at), $message);
    }

    /**
     * Asserts an email at a specific index contains expected text contents
     *
     * @param int $at Email index
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsTextAt(int $at, string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailContainsText($at), $message);
    }

    /**
     * Asserts an email at a specific index contains the expected value within an Email getter
     *
     * @param int $at Email index
     * @param string $expected Contents
     * @param string $parameter Email getter parameter (e.g. "cc", "bcc")
     * @param string $message Message
     * @return void
     */
    public function assertMailSentWithAt(int $at, string $expected, string $parameter, string $message = ''): void
    {
        $this->assertThat($expected, new MailSentWith($at, $parameter), $message);
    }

    /**
     * Asserts an email was sent to an address
     *
     * @param string $address Email address
     * @param string $message Message
     * @return void
     */
    public function assertMailSentTo(string $address, string $message = ''): void
    {
        $this->assertThat($address, new MailSentTo(), $message);
    }

    /**
     * Asserts an email was sent from an address
     *
     * @param string $address Email address
     * @param string $message Message
     * @return void
     */
    public function assertMailSentFrom(string $address, string $message = ''): void
    {
        $this->assertThat($address, new MailSentFrom(), $message);
    }

    /**
     * Asserts an email contains expected contents
     *
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContains(string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailContains(), $message);
    }

    /**
     * Asserts an email contains expected attachment
     *
     * @param string $filename Filename
     * @param array $file Additional file properties
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsAttachment(string $filename, array $file = [], string $message = ''): void
    {
        $this->assertThat([$filename, $file], new MailContainsAttachment(), $message);
    }

    /**
     * Asserts an email contains expected html contents
     *
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsHtml(string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailContainsHtml(), $message);
    }

    /**
     * Asserts an email contains an expected text content
     *
     * @param string $expected Expected text.
     * @param string $message Message to display if assertion fails.
     * @return void
     */
    public function assertMailContainsText(string $expected, string $message = ''): void
    {
        $this->assertThat($expected, new MailContainsText(), $message);
    }

    /**
     * Asserts an email contains the expected value within an Email getter
     *
     * @param string $expected Contents
     * @param string $parameter Email getter parameter (e.g. "cc", "subject")
     * @param string $message Message
     * @return void
     */
    public function assertMailSentWith(string $expected, string $parameter, string $message = ''): void
    {
        $this->assertThat($expected, new MailSentWith(null, $parameter), $message);
    }

    /**
     * Asserts an email subject contains expected contents
     *
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailSubjectContains(string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailSubjectContains(), $message);
    }

    /**
     * Asserts an email at a specific index contains expected html contents
     *
     * @param int $at Email index
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailSubjectContainsAt(int $at, string $contents, string $message = ''): void
    {
        $this->assertThat($contents, new MailSubjectContains($at), $message);
    }
}
