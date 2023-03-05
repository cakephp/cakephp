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

namespace Cake\Test\TestCase\TestSuite;

use Cake\Mailer\Mailer;
use Cake\Mailer\Message;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\Constraint\Email\MailSentFrom;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestEmailTransport;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\LogicalNot;

/**
 * Tests EmailTrait assertions
 */
class EmailTraitTest extends TestCase
{
    use EmailTrait;

    /**
     * setUp
     */
    public function setUp(): void
    {
        parent::setUp();

        Mailer::drop('default');
        Mailer::drop('alternate');

        Mailer::setConfig('default', [
            'transport' => 'test_tools',
            'from' => ['default@example.com' => 'Default Name'],
        ]);
        Mailer::setConfig('alternate', [
            'transport' => 'test_tools',
            'from' => 'alternate@example.com',
        ]);
        TransportFactory::setConfig('test_tools', [
            'className' => TestEmailTransport::class,
        ]);
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        parent::tearDown();

        Mailer::drop('default');
        Mailer::drop('alternate');
        TransportFactory::drop('test_tools');
    }

    /**
     * tests assertions against any emails that were sent
     */
    public function testSingleAssertions(): void
    {
        $this->sendEmails();

        $this->assertMailSentFrom(['default@example.com' => 'Default Name']);
        $this->assertMailSentFrom('alternate@example.com');

        $this->assertMailSentTo('to@example.com');
        $this->assertMailSentTo('alsoto@example.com');
        $this->assertMailSentTo('to2@example.com');

        $this->assertMailContains('text');
        $this->assertMailContains('html');

        $this->assertMailSubjectContains('world');

        $this->assertMailContainsAttachment('custom_name.php');
        $this->assertMailContainsAttachment('custom_name.php', ['file' => CAKE . 'basics.php']);

        $this->assertMailSentWith('Hello world', 'subject');
        $this->assertMailSentWith('cc@example.com', 'cc');
        $this->assertMailSentWith('bcc@example.com', 'bcc');
        $this->assertMailSentWith('cc2@example.com', 'cc');
        $this->assertMailSentWith('replyto@example.com', 'replyTo');
        $this->assertMailSentWith('sender@example.com', 'sender');
    }

    /**
     * tests multiple email assertions
     */
    public function testMultipleAssertions(): void
    {
        $this->assertNoMailSent();

        $this->sendEmails();

        $this->assertMailCount(3);

        $this->assertMailSentFromAt(0, 'default@example.com');
        $this->assertMailSentFromAt(1, 'alternate@example.com');

        // Confirm that "at 0" is really testing email 0, not all the emails
        $this->assertThat('alternate@example.com', new LogicalNot(new MailSentFrom(0)));

        $this->assertMailSentToAt(0, 'to@example.com');
        $this->assertMailSentToAt(1, 'to2@example.com');
        $this->assertMailSentToAt(2, 'to3@example.com');

        $this->assertMailContainsAt(0, 'text');
        $this->assertMailContainsAt(1, 'html');

        $this->assertMailSubjectContainsAt(0, 'world');

        $this->assertMailSentWithAt(0, 'Hello world', 'subject');
        $this->assertMailSentWithAt(0, 'replyto@example.com', 'replyTo');
    }

    /**
     * tests assertNoMailSent fails when no mail is sent
     */
    public function testAssertNoMailSentFailure(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that no emails were sent.');

        $this->sendEmails();
        $this->assertNoMailSent();
    }

    /**
     * tests assertMailContainsHtml fails appropriately
     */
    public function testAssertContainsHtmlFailure(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->sendEmails();

        $this->assertMailContainsHtmlAt(0, 'text');
    }

    /**
     * tests assertMailContainsText fails appropriately
     */
    public function testAssertContainsTextFailure(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->sendEmails();

        $this->assertMailContainsTextAt(1, 'html');
    }

    /**
     * tests multiple messages sent by same Mailer are captured correctly
     */
    public function testAssertMultipleMessages(): void
    {
        $this->sendMultipleEmails();

        $this->assertMailSentTo('to@example.com');
        $this->assertMailSentTo('to2@example.com');
        $this->assertMailSentFrom('reusable-mailer@example.com');
    }

    /**
     * Tests asserting using RegExp characters doesn't break the assertion
     */
    public function testAssertUsingRegExpCharacters(): void
    {
        (new Mailer())
            ->setTo('to3@example.com')
            ->setCc('cc3@example.com')
            ->deliver('email with regexp chars $/[]');

        $this->assertMailContains('$/[]');
    }

    /**
     * tests constraint failure messages
     *
     * @param string $assertion Assertion method
     * @param string $expectedMessage Expected failure message
     * @param array $params Assertion params
     * @dataProvider failureMessageDataProvider
     */
    public function testFailureMessages($assertion, $expectedMessage, $params): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage($expectedMessage);

        call_user_func_array([$this, $assertion], $params);
    }

    /**
     * data provider for checking failure messages
     *
     * @return array
     */
    public function failureMessageDataProvider(): array
    {
        return [
            'assertMailCount' => ['assertMailCount', 'Failed asserting that 2 emails were sent.', [2]],
            'assertMailSentTo' => ['assertMailSentTo', 'Failed asserting that \'missing@example.com\' was sent an email.', ['missing@example.com']],
            'assertMailSentToAt' => ['assertMailSentToAt', 'Failed asserting that \'missing@example.com\' was sent email #1.', [1, 'missing@example.com']],
            'assertMailSentFrom' => ['assertMailSentFrom', 'Failed asserting that \'missing@example.com\' sent an email.', ['missing@example.com']],
            'assertMailSentFromAt' => ['assertMailSentFromAt', 'Failed asserting that \'missing@example.com\' sent email #1.', [1, 'missing@example.com']],
            'assertMailSentWith' => ['assertMailSentWith', 'Failed asserting that \'Missing\' is in an email `subject`.', ['Missing', 'subject']],
            'assertMailSentWithAt' => ['assertMailSentWithAt', 'Failed asserting that \'Missing\' is in email #1 `subject`.', [1, 'Missing', 'subject']],
            'assertMailContains' => ['assertMailContains', 'Failed asserting that \'Missing\' is in an email' . PHP_EOL . 'was: .', ['Missing']],
            'assertMailContainsAttachment' => ['assertMailContainsAttachment', 'Failed asserting that \'no_existing_file.php\' is an attachment of an email.', ['no_existing_file.php']],
            'assertMailContainsHtml' => ['assertMailContainsHtml', 'Failed asserting that \'Missing\' is in the html message of an email' . PHP_EOL . 'was: .', ['Missing']],
            'assertMailContainsText' => ['assertMailContainsText', 'Failed asserting that \'Missing\' is in the text message of an email' . PHP_EOL . 'was: .', ['Missing']],
            'assertMailContainsAt' => ['assertMailContainsAt', 'Failed asserting that \'Missing\' is in email #1' . PHP_EOL . 'was: .', [1, 'Missing']],
            'assertMailContainsHtmlAt' => ['assertMailContainsHtmlAt', 'Failed asserting that \'Missing\' is in the html message of email #1' . PHP_EOL . 'was: .', [1, 'Missing']],
            'assertMailContainsTextAt' => ['assertMailContainsTextAt', 'Failed asserting that \'Missing\' is in the text message of email #1' . PHP_EOL . 'was: .', [1, 'Missing']],
            'assertMailSubjectContains' => ['assertMailSubjectContains', 'Failed asserting that \'Missing\' is in an email subject' . PHP_EOL . 'was: .', ['Missing']],
            'assertMailSubjectContainsAt' => ['assertMailSubjectContainsAt', 'Failed asserting that \'Missing\' is in an email subject #1' . PHP_EOL . 'was: .', [1, 'Missing']],
        ];
    }

    /**
     * sends some emails
     */
    private function sendEmails(): void
    {
        (new Mailer())
            ->setSender(['sender@example.com' => 'Sender'])
            ->setTo(['to@example.com' => 'Foo Bar'])
            ->addTo('alsoto@example.com')
            ->setReplyTo(['replyto@example.com' => 'Reply to me'])
            ->setCc('cc@example.com')
            ->setBcc(['bcc@example.com' => 'Baz Qux'])
            ->setSubject('Hello world')
            ->setAttachments(['custom_name.php' => CAKE . 'basics.php'])
            ->setEmailFormat(Message::MESSAGE_TEXT)
            ->deliver('text');

        (new Mailer('alternate'))
            ->setTo('to2@example.com')
            ->setCc('cc2@example.com')
            ->setEmailFormat(Message::MESSAGE_HTML)
            ->deliver('html');

        (new Mailer('alternate'))
            ->setTo(['to3@example.com' => null])
            ->deliver('html');
    }

    /**
     * sends some emails
     */
    private function sendMultipleEmails(): void
    {
        $reusableMailer = new Mailer();
        $reusableMailer
            ->setEmailFormat(Message::MESSAGE_TEXT)
            ->setFrom('reusable-mailer@example.com');

        $emails = [
            'to@example.com' => ['title' => 'Title1', 'content' => 'abc'],
            'to2@example.com' => ['title' => 'Title2', 'content' => 'xyz'],
        ];

        foreach ($emails as $email => $messageContents) {
            $reusableMailer->setTo($email)
                ->setSubject($messageContents['title'])
                ->setViewVars($messageContents)
                ->deliver();
        }
    }
}
