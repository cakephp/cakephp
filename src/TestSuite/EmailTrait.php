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
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\TestSuite\Constraint\Email\MailContains;
use Cake\TestSuite\Constraint\Email\MailContainsHtml;
use Cake\TestSuite\Constraint\Email\MailContainsText;
use Cake\TestSuite\Constraint\Email\MailCount;
use Cake\TestSuite\Constraint\Email\MailSentFrom;
use Cake\TestSuite\Constraint\Email\MailSentTo;
use Cake\TestSuite\Constraint\Email\MailSentWith;
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
    public function setupTransports()
    {
        TestEmailTransport::replaceAllTransports();
    }

    /**
     * Resets transport state
     *
     * @after
     * @return void
     */
    public function cleanupEmailTrait()
    {
        TestEmailTransport::clearEmails();
    }

    /**
     * Asserts an expected number of emails were sent
     *
     * @param int $count Email count
     * @param string $message Message
     * @return void
     */
    public function assertMailCount($count, $message = null)
    {
        $this->assertThat($count, new MailCount(), $message);
    }

    /**
     *
     * Asserts that no emails were sent
     *
     * @param string $message Message
     * @return void
     */
    public function assertNoMailSent($message = null)
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
    public function assertMailSentToAt($at, $address, $message = null)
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
    public function assertMailSentFromAt($at, $address, $message = null)
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
    public function assertMailContainsAt($at, $contents, $message = null)
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
    public function assertMailContainsHtmlAt($at, $contents, $message = null)
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
    public function assertMailContainsTextAt($at, $contents, $message = null)
    {
        $this->assertThat($contents, new MailContainsText($at), $message);
    }

    /**
     * Asserts an email at a specific index contains the expected value within an Email getter
     *
     * @param int $at Email index
     * @param string $expected Contents
     * @param string $parameter Email getter parameter (e.g. "cc", "subject")
     * @param string $message Message
     * @return void
     */
    public function assertMailSentWithAt($at, $expected, $parameter, $message = null)
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
    public function assertMailSentTo($address, $message = null)
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
    public function assertMailSentFrom($address, $message = null)
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
    public function assertMailContains($contents, $message = null)
    {
        $this->assertThat($contents, new MailContains(), $message);
    }

    /**
     * Asserts an email contains expected html contents
     *
     * @param string $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsHtml($contents, $message = null)
    {
        $this->assertThat($contents, new MailContainsHtml(), $message);
    }

    /**
     * Asserts an email contains an expected text content
     *
     * @param string $expectedText Expected text.
     * @param string $message Message to display if assertion fails.
     * @return void
     */
    public function assertMailContainsText($expectedText, $message = null)
    {
        $this->assertThat($expectedText, new MailContainsText(), $message);
    }

    /**
     * Asserts an email contains the expected value within an Email getter
     *
     * @param string $expected Contents
     * @param string $parameter Email getter parameter (e.g. "cc", "subject")
     * @param string $message Message
     * @return void
     */
    public function assertMailSentWith($expected, $parameter, $message = null)
    {
        $this->assertThat($expected, new MailSentWith(null, $parameter), $message);
    }
}
