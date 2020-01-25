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
namespace Cake\Test\TestCase\TestSuite;

use Cake\Mailer\Email;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestEmailTransport;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Tests EmailTrait assertions
 */
class EmailTraitTest extends TestCase
{
    use EmailTrait;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Email::drop('default');
        Email::drop('alternate');

        Email::setConfig('default', [
            'transport' => 'test_tools',
            'from' => 'default@example.com',
        ]);
        Email::setConfig('alternate', [
            'transport' => 'test_tools',
            'from' => 'alternate@example.com',
        ]);
        TransportFactory::setConfig('test_tools', [
            'className' => TestEmailTransport::class,
        ]);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        Email::drop('default');
        Email::drop('alternate');
        TransportFactory::drop('test_tools');
    }

    /**
     * tests assertions against any emails that were sent
     *
     * @return void
     */
    public function testSingleAssertions()
    {
        $this->sendEmails();

        $this->assertMailSentFrom('default@example.com');
        $this->assertMailSentFrom('alternate@example.com');

        $this->assertMailSentTo('to@example.com');
        $this->assertMailSentTo('alsoto@example.com');
        $this->assertMailSentTo('to2@example.com');

        $this->assertMailContains('text');
        $this->assertMailContains('html');

        $this->assertMailSentWith('Hello world', 'subject');
        $this->assertMailSentWith('cc@example.com', 'cc');
        $this->assertMailSentWith('bcc@example.com', 'bcc');
        $this->assertMailSentWith('cc2@example.com', 'cc');

        $this->assertMailSentWith('default', 'template');
        $this->assertMailSentWith('default', 'layout');
    }

    /**
     * tests multiple email assertions
     *
     * @return void
     */
    public function testMultipleAssertions()
    {
        $this->assertNoMailSent();

        $this->sendEmails();

        $this->assertMailCount(3);

        $this->assertMailSentFromAt(0, 'default@example.com');
        $this->assertMailSentFromAt(1, 'alternate@example.com');

        $this->assertMailSentToAt(0, 'to@example.com');
        $this->assertMailSentToAt(1, 'to2@example.com');
        $this->assertMailSentToAt(2, 'to3@example.com');

        $this->assertMailContainsAt(0, 'text');
        $this->assertMailContainsAt(1, 'html');

        $this->assertMailSentWithAt(0, 'Hello world', 'subject');
    }

    /**
     * tests assertNoMailSent fails when no mail is sent
     *
     * @return void
     */
    public function testAssertNoMailSentFailure()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that no emails were sent.');

        $this->sendEmails();
        $this->assertNoMailSent();
    }

    /**
     * tests assertMailContainsHtml fails appropriately
     *
     * @return void
     */
    public function testAssertContainsHtmlFailure()
    {
        $this->expectException(AssertionFailedError::class);

        $this->sendEmails();

        $this->assertMailContainsHtmlAt(0, 'text');
    }

    /**
     * tests assertMailContainsText fails appropriately
     *
     * @return void
     */
    public function testAssertContainsTextFailure()
    {
        $this->expectException(AssertionFailedError::class);

        $this->sendEmails();

        $this->assertMailContainsTextAt(1, 'html');
    }

    /**
     * Tests asserting using RegExp characters doesn't break the assertion
     *
     * @return void
     */
    public function testAssertUsingRegExpCharacters()
    {
        (new Email())
            ->setTo('to3@example.com')
            ->setCc('cc3@example.com')
            ->send('email with regexp chars $/[]');

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
    public function testFailureMessages($assertion, $expectedMessage, $params)
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
    public function failureMessageDataProvider()
    {
        return [
            'assertMailCount' => ['assertMailCount', 'Failed asserting that 2 emails were sent.', [2]],
            'assertMailSentTo' => ['assertMailSentTo', 'Failed asserting that \'missing@example.com\' was sent an email.', ['missing@example.com']],
            'assertMailSentToAt' => ['assertMailSentToAt', 'Failed asserting that \'missing@example.com\' was sent email #1.', [1, 'missing@example.com']],
            'assertMailSentFrom' => ['assertMailSentFrom', 'Failed asserting that \'missing@example.com\' sent an email.', ['missing@example.com']],
            'assertMailSentFromAt' => ['assertMailSentFromAt', 'Failed asserting that \'missing@example.com\' sent email #1.', [1, 'missing@example.com']],
            'assertMailSentWith' => ['assertMailSentWith', 'Failed asserting that \'Missing\' is in an email `subject`.', ['Missing', 'subject']],
            'assertMailSentWithAt' => ['assertMailSentWithAt', 'Failed asserting that \'Missing\' is in email #1 `subject`.', [1, 'Missing', 'subject']],
            'assertMailContains' => ['assertMailContains', 'Failed asserting that \'Missing\' is in an email.', ['Missing']],
            'assertMailContainsHtml' => ['assertMailContainsHtml', 'Failed asserting that \'Missing\' is in the html message of an email.', ['Missing']],
            'assertMailContainsText' => ['assertMailContainsText', 'Failed asserting that \'Missing\' is in the text message of an email.', ['Missing']],
            'assertMailContainsAt' => ['assertMailContainsAt', 'Failed asserting that \'Missing\' is in email #1.', [1, 'Missing']],
            'assertMailContainsHtmlAt' => ['assertMailContainsHtmlAt', 'Failed asserting that \'Missing\' is in the html message of email #1.', [1, 'Missing']],
            'assertMailContainsTextAt' => ['assertMailContainsTextAt', 'Failed asserting that \'Missing\' is in the text message of email #1.', [1, 'Missing']],
        ];
    }

    /**
     * sends some emails
     *
     * @return void
     */
    private function sendEmails()
    {
        (new Email())
            ->setTo(['to@example.com' => 'Foo Bar'])
            ->addTo('alsoto@example.com')
            ->setCc('cc@example.com')
            ->setBcc(['bcc@example.com' => 'Baz Qux'])
            ->setSubject('Hello world')
            ->setEmailFormat(Email::MESSAGE_TEXT)
            ->send('text');

        $email = (new Email('alternate'))
            ->setTo('to2@example.com')
            ->setCc('cc2@example.com')
            ->setEmailFormat(Email::MESSAGE_HTML);
        $email->viewBuilder()
            ->setTemplate('default')
            ->setLayout('default');
        $email->send('html');

        (new Email('alternate'))
            ->setTo(['to3@example.com' => null])
            ->send('html');
    }
}
