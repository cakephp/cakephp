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
 * After adding the trait to your test case, replace all mail transports with the
 * TestEmailTransport:
 *
 * **tests/bootstrap.php**
 * ```
 * use Cake\TestSuite\TestEmailTransport;
 *
 * // replaces existing transports with the TestEmailTransport for email assertions
 * TestEmailTransport::replaceAllTransports();
 * ```
 *
 * Then, in your test case's tearDown method, clean up previously sent emails:
 *
 * ```
 * public function tearDown()
 * {
 *     // other cleanup
 *     parent::tearDown();
 *     TestEmailTransport::clearEmails();
 * }
 * ```
 */
trait EmailTrait
{
    /**
     * Asserts an expected number of emails were sent
     *
     * @param int $count Email count
     * @param string|null $message Message
     * @return void
     */
    public function assertMailCount(int $count, ?string $message = null): void
    {
        $this->assertThat($count, new MailCount(), $message);
    }
    /**
     *
     * Asserts that no emails were sent
     *
     * @param string|null $message Message
     * @return void
     */
    public function assertNoMailSent(?string $message = null): void
    {
        $this->assertThat(null, new NoMailSent(), $message);
    }

    /**
     * Asserts an email at a specific index was sent to an address
     *
     * @param int $at Email index
     * @param string $address Email address
     * @param string|null $message Message
     * @return void
     */
    public function assertMailSentToAt(int $at, string $address, ?string $message = null): void
    {
        $this->assertThat($address, new MailSentTo($at), $message);
    }

    /**
     * Asserts an email at a specific index was sent from an address
     *
     * @param int $at Email index
     * @param string $address Email address
     * @param string|null $message Message
     * @return void
     */
    public function assertMailSentFromAt(int $at, string $address, ?string $message = null): void
    {
        $this->assertThat($address, new MailSentFrom($at), $message);
    }

    /**
     * Asserts an email at a specific index contains expected contents
     *
     * @param int $at Email index
     * @param string $contents Contents
     * @param string|null $message Message
     * @return void
     */
    public function assertMailContainsAt(int $at, string $contents, ?string $message = null): void
    {
        $this->assertThat($contents, new MailContains($at), $message);
    }

    /**
     * Asserts an email at a specific index contains expected html contents
     *
     * @param int $at Email index
     * @param string $contents Contents
     * @param string|null $message Message
     * @return void
     */
    public function assertMailContainsHtmlAt(int $at, string $contents, ?string $message = null): void
    {
        $this->assertThat($contents, new MailContainsHtml($at), $message);
    }

    /**
     * Asserts an email at a specific index contains expected text contents
     *
     * @param int $at Email index
     * @param string $contents Contents
     * @param string|null $message Message
     * @return void
     */
    public function assertMailContainsTextAt(int $at, string $contents, ?string $message = null): void
    {
        $this->assertThat($contents, new MailContainsText($at), $message);
    }

    /**
     * Asserts an email at a specific index contains the expected value within an Email getter
     *
     * @param int $at Email index
     * @param string $expected Contents
     * @param string $parameter Email getter parameter (e.g. "cc", "subject")
     * @param string|null $message Message
     * @return void
     */
    public function assertMailSentWithAt(int $at, string $expected, string $parameter, ?string $message = null): void
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
    public function assertMailSentTo(string $address, ?string $message = null): void
    {
        $this->assertThat($address, new MailSentTo(), $message);
    }

    /**
     * Asserts an email was sent from an address
     *
     * @param string $address Email address
     * @param string|null $message Message
     * @return void
     */
    public function assertMailSentFrom(string $address, ?string $message = null): void
    {
        $this->assertThat($address, new MailSentFrom(), $message);
    }

    /**
     * Asserts an email contains expected contents
     *
     * @param string $contents Contents
     * @param string|null $message Message
     * @return void
     */
    public function assertMailContains(string $contents, ?string $message = null): void
    {
        $this->assertThat($contents, new MailContains(), $message);
    }

    /**
     * Asserts an email contains expected html contents
     *
     * @param string $contents Contents
     * @param string|null $message Message
     * @return void
     */
    public function assertMailContainsHtml(string $contents, ?string $message = null): void
    {
        $this->assertThat($contents, new MailContainsHtml(), $message);
    }

    /**
     * Asserts an email contains expected text contents
     *
     * @param string $contents Contents
     * @param string|null $message Message
     * @return void
     */
    public function assertMailContainsText(string $contents, ?string $message = null): void
    {
        $this->assertThat($contents, new MailContainsText(), $message);
    }

    /**
     * Asserts an email contains the expected value within an Email getter
     *
     * @param string $expected Contents
     * @param string $parameter Email getter parameter (e.g. "cc", "subject")
     * @param string|null $message Message
     * @return void
     */
    public function assertMailSentWith(string $expected, string $parameter, ?string $message = null): void
    {
        $this->assertThat($expected, new MailSentWith(null, $parameter), $message);
    }
}
