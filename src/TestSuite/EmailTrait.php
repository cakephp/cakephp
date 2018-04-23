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

use Cake\TestSuite\Constraint\MailContainsConstraint;
use Cake\TestSuite\Constraint\MailCountConstraint;
use Cake\TestSuite\Constraint\MailSentFromConstraint;
use Cake\TestSuite\Constraint\MailSentToConstraint;
use Cake\TestSuite\Constraint\MailSentWithConstraint;
use Cake\TestSuite\Constraint\NoMailSentConstraint;

/**
 * Make assertions on emails sent through the Cake\TestSuite\TestEmailTransport
 *
 * **tests/bootstrap.php**
 * ```
 * use Cake\Mailer\Email;
 * use Cake\TestSuite\TestEmailTransport;
 *
 * // replace with other transport configs if required
 * $config = Email::getConfigTransport('default');
 * $config['className'] = TestEmailTransport::class;
 * Email::dropTransport('default');
 * Email::setConfigTransport('default', $config);
 * ```
 */
trait EmailTrait
{

    /**
     * Asserts an expected number of emails were sent
     *
     * @param int $count Email count
     * @param string $message Message
     * @return void
     */
    public function assertMailCount($count, $message = null)
    {
        $this->assertThat($count, new MailCountConstraint(), $message);
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
        $this->assertThat(null, new NoMailSentConstraint(), $message);
    }

    /**
     * Asserts an email at a specific index was sent to an address
     *
     * @param int $at Email index
     * @param int $address Email address
     * @param string $message Message
     * @return void
     */
    public function assertMailSentToAt($at, $address, $message = null)
    {
        $this->assertThat($address, new MailSentToConstraint($at), $message);
    }

    /**
     * Asserts an email at a specific index was sent from an address
     *
     * @param int $at Email index
     * @param int $address Email address
     * @param string $message Message
     * @return void
     */
    public function assertMailSentFromAt($at, $address, $message = null)
    {
        $this->assertThat($address, new MailSentFromConstraint($at), $message);
    }

    /**
     * Asserts an email at a specific index contains expected contents
     *
     * @param int $at Email index
     * @param int $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContainsAt($at, $contents, $message = null)
    {
        $this->assertThat($contents, new MailContainsConstraint($at), $message);
    }

    /**
     * Asserts an email at a specific index contains the expected value within an Email getter
     *
     * @param int $at Email index
     * @param int $expected Contents
     * @param int $parameter Email getter parameter (e.g. "cc", "subject")
     * @param string $message Message
     * @return void
     */
    public function assertMailSentWithAt($at, $expected, $parameter, $message = null)
    {
        $this->assertThat($expected, new MailSentWithConstraint($at, $parameter), $message);
    }

    /**
     * Asserts an email was sent to an address
     *
     * @param int $address Email address
     * @param string $message Message
     * @return void
     */
    public function assertMailSentTo($address, $message = null)
    {
        $this->assertThat($address, new MailSentToConstraint(), $message);
    }

    /**
     * Asserts an email was sent from an address
     *
     * @param int $address Email address
     * @param string $message Message
     * @return void
     */
    public function assertMailSentFrom($address, $message = null)
    {
        $this->assertThat($address, new MailSentFromConstraint(), $message);
    }

    /**
     * Asserts an email contains expected contents
     *
     * @param int $contents Contents
     * @param string $message Message
     * @return void
     */
    public function assertMailContains($contents, $message = null)
    {
        $this->assertThat($contents, new MailContainsConstraint(), $message);
    }

    /**
     * Asserts an email contains the expected value within an Email getter
     *
     * @param int $expected Contents
     * @param int $parameter Email getter parameter (e.g. "cc", "subject")
     * @param string $message Message
     * @return void
     */
    public function assertMailSentWith($expected, $parameter, $message = null)
    {
        $this->assertThat($expected, new MailSentWithConstraint(null, $parameter), $message);
    }
}
