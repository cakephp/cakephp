<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer;

use Cake\Datasource\ModelAwareTrait;
use Cake\Event\EventListenerInterface;
use Cake\Mailer\Exception\MissingActionException;
use Cake\View\ViewBuilder;

/**
 * Mailer base class.
 *
 * Mailer classes let you encapsulate related Email logic into a reusable
 * and testable class.
 *
 * ## Defining Messages
 *
 * Mailers make it easy for you to define methods that handle email formatting
 * logic. For example:
 *
 * ```
 * class UserMailer extends Mailer
 * {
 *     public function resetPassword($user)
 *     {
 *         $this
 *             ->setSubject('Reset Password')
 *             ->setTo($user->email)
 *             ->set(['token' => $user->token]);
 *     }
 * }
 * ```
 *
 * Is a trivial example but shows how a mailer could be declared.
 *
 * ## Sending Messages
 *
 * After you have defined some messages you will want to send them:
 *
 * ```
 * $mailer = new UserMailer();
 * $mailer->send('resetPassword', $user);
 * ```
 *
 * ## Event Listener
 *
 * Mailers can also subscribe to application event allowing you to
 * decouple email delivery from your application code. By re-declaring the
 * `implementedEvents()` method you can define event handlers that can
 * convert events into email. For example, if your application had a user
 * registration event:
 *
 * ```
 * public function implementedEvents(): array
 * {
 *     return [
 *         'Model.afterSave' => 'onRegistration',
 *     ];
 * }
 *
 * public function onRegistration(EventInterface $event, EntityInterface $entity, ArrayObject $options)
 * {
 *     if ($entity->isNew()) {
 *          $this->send('welcome', [$entity]);
 *     }
 * }
 * ```
 *
 * The onRegistration method converts the application event into a mailer method.
 * Our mailer could either be registered in the application bootstrap, or
 * in the Table class' initialize() hook.
 *
 * @method \Cake\Mailer\Mailer setTo($email, $name = null)
 * @method array getTo()
 * @method \Cake\Mailer\Mailer setFrom($email, $name = null)
 * @method array getFrom()
 * @method \Cake\Mailer\Mailer setSender($email, $name = null)
 * @method array getSender()
 * @method \Cake\Mailer\Mailer setReplyTo($email, $name = null)
 * @method array getReplyTo()
 * @method \Cake\Mailer\Mailer setReadReceipt($email, $name = null)
 * @method array getReadReceipt()
 * @method \Cake\Mailer\Mailer setReturnPath($email, $name = null)
 * @method array getReturnPath()
 * @method \Cake\Mailer\Mailer addTo($email, $name = null)
 * @method \Cake\Mailer\Mailer setCc($email, $name = null)
 * @method array getCc()
 * @method \Cake\Mailer\Mailer addCc($email, $name = null)
 * @method \Cake\Mailer\Mailer setBcc($email, $name = null)
 * @method array getBcc()
 * @method \Cake\Mailer\Mailer addBcc($email, $name = null)
 * @method \Cake\Mailer\Mailer setCharset($charset)
 * @method string getCharset()
 * @method \Cake\Mailer\Mailer setHeaderCharset($charset)
 * @method string getHeaderCharset()
 * @method \Cake\Mailer\Mailer setSubject($subject)
 * @method string getSubject()
 * @method \Cake\Mailer\Mailer setHeaders(array $headers)
 * @method \Cake\Mailer\Mailer addHeaders(array $headers)
 * @method \Cake\Mailer\Mailer getHeaders(array $include = [])
 * @method \Cake\Mailer\Mailer setViewRenderer($viewClass)
 * @method string getViewRenderer()
 * @method \Cake\Mailer\Mailer setViewVars($viewVars)
 * @method array getViewVars()
 * @method \Cake\Mailer\Mailer setEmailFormat($format)
 * @method string getEmailFormat()
 * @method \Cake\Mailer\Mailer setTransport($name)
 * @method \Cake\Mailer\AbstractTransport getTransport()
 * @method \Cake\Mailer\Mailer setMessageId($message)
 * @method bool|string getMessageId()
 * @method \Cake\Mailer\Mailer setDomain($domain)
 * @method string getDomain()
 * @method \Cake\Mailer\Mailer setAttachments($attachments)
 * @method array getAttachments()
 * @method \Cake\Mailer\Mailer addAttachments($attachments)
 * @method \Cake\Mailer\Mailer message($type = null)
 * @method \Cake\Mailer\Mailer setProfile($config)
 * @method string|array getProfile()
 */
abstract class Mailer implements EventListenerInterface
{
    use ModelAwareTrait;

    /**
     * Mailer's name.
     *
     * @var string
     */
    public static $name;

    /**
     * Email instance.
     *
     * @var \Cake\Mailer\Email
     */
    protected $_email;

    /**
     * Cloned Email instance for restoring instance after email is sent by
     * mailer action.
     *
     * @var \Cake\Mailer\Email
     */
    protected $_clonedEmail;

    /**
     * Constructor.
     *
     * @param \Cake\Mailer\Email|null $email Email instance.
     */
    public function __construct(?Email $email = null)
    {
        if ($email === null) {
            $email = new Email();
        }

        $this->_email = $email;
        $this->_clonedEmail = clone $email;
    }

    /**
     * Returns the mailer's name.
     *
     * @return string
     */
    public function getName(): string
    {
        if (!static::$name) {
            static::$name = str_replace(
                'Mailer',
                '',
                implode('', array_slice(explode('\\', static::class), -1))
            );
        }

        return static::$name;
    }

    /**
     * Get Email instance's view builder.
     *
     * @return \Cake\View\ViewBuilder
     */
    public function viewBuilder(): ViewBuilder
    {
        return $this->_email->viewBuilder();
    }

    /**
     * Magic method to forward method class to Email instance.
     *
     * @param string $method Method name.
     * @param array $args Method arguments
     * @return $this|mixed
     */
    public function __call(string $method, array $args)
    {
        $result = $this->_email->$method(...$args);
        if (strpos($method, 'get') === 0) {
            return $result;
        }

        return $this;
    }

    /**
     * Sets email view vars.
     *
     * @param string|array $key Variable name or hash of view variables.
     * @param mixed $value View variable value.
     * @return $this
     */
    public function set($key, $value = null)
    {
        $this->_email->setViewVars(is_string($key) ? [$key => $value] : $key);

        return $this;
    }

    /**
     * Sends email.
     *
     * @param string $action The name of the mailer action to trigger.
     * @param array $args Arguments to pass to the triggered mailer action.
     * @param array $headers Headers to set.
     * @return array
     * @throws \Cake\Mailer\Exception\MissingActionException
     * @throws \BadMethodCallException
     */
    public function send(string $action, array $args = [], array $headers = []): array
    {
        try {
            if (!method_exists($this, $action)) {
                throw new MissingActionException([
                    'mailer' => $this->getName() . 'Mailer',
                    'action' => $action,
                ]);
            }

            $this->_email->getMessage()->setHeaders($headers);
            if (!$this->_email->viewBuilder()->getTemplate()) {
                $this->_email->viewBuilder()->setTemplate($action);
            }

            $this->$action(...$args);

            $result = $this->_email->send();
        } finally {
            $this->reset();
        }

        return $result;
    }

    /**
     * Reset email instance.
     *
     * @return $this
     */
    protected function reset()
    {
        $this->_email = clone $this->_clonedEmail;

        return $this;
    }

    /**
     * Implemented events.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [];
    }
}
