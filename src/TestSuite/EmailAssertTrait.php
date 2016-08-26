<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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
     * @param array|string|null $content The email's content to send.
     * @return void
     */
    public function send($content = null)
    {
        $this->email(true)->send($content);
    }

    /**
     * @param bool $new Tells if new instance should forcebly be created.
     * @return \Cake\Mailer\Email
     */
    public function email($new = false)
    {
        if ($new || !$this->_email) {
            $this->_email = new Email();
            $this->_email->profile(['transport' => 'debug'] + $this->_email->profile());
        }

        return $this->_email;
    }

    /**
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
     * @param string $needle Text to look for.
     * @param string $message The failure message to define.
     * @return void
     */
    public function assertEmailMessageContains($needle, $message = null)
    {
        $this->assertEmailHtmlMessageContains($needle, $message);
        $this->assertEmailTextMessageContains($needle, $message);
    }

    /**
     * @param string $needle Text to look for.
     * @param string $message The failure message to define.
     * @return void
     */
    public function assertEmailHtmlMessageContains($needle, $message = null)
    {
        $haystack = $this->email()->message('html');
        $this->assertTextContains($needle, $haystack, $message);
    }

    /**
     * @param string $needle Text to look for.
     * @param string $message The failure message to define.
     * @return void
     */
    public function assertEmailTextMessageContains($needle, $message = null)
    {
        $haystack = $this->email()->message('text');
        $this->assertTextContains($needle, $haystack, $message);
    }

    /**
     * @param string $expected Email's subject.
     * @param string $message The failure message to define.
     * @return void
     */
    public function assertEmailSubject($expected, $message = null)
    {
        $result = $this->email()->subject();
        $this->assertSame($expected, $result, $message);
    }

    /**
     * @param string $email Sender's email address.
     * @param string $name Sender's name.
     * @param string $message The failure message to define.
     * @return void
     */
    public function assertEmailFrom($email, $name, $message = null)
    {
        $expected = [$email => $name];
        $result = $this->email()->from();
        $this->assertSame($expected, $result, $message);
    }

    /**
     * @param string $email Sender's email address.
     * @param string $name Sender's name.
     * @param string $message The failure message to define.
     * @return void
     */
    public function assertEmailTo($email, $name, $message = null)
    {
        $expected = [$email => $name];
        $result = $this->email()->to();
        $this->assertSame($expected, $result, $message);
    }
}
