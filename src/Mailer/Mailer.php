<?php
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
 * public function implementedEvents()
 * {
 *     return [
 *         'Model.afterSave' => 'onRegistration',
 *     ];
 * }
 *
 * public function onRegistration(Event $event, Entity $entity, ArrayObject $options)
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
 * @method \Cake\Mailer\Email setTo($email, $name = null)
 * @method array getTo()
 * @method \Cake\Mailer\Email to($email = null, $name = null)
 * @method \Cake\Mailer\Email setFrom($email, $name = null)
 * @method array getFrom()
 * @method \Cake\Mailer\Email from($email = null, $name = null)
 * @method \Cake\Mailer\Email setSender($email, $name = null)
 * @method array getSender()
 * @method \Cake\Mailer\Email sender($email = null, $name = null)
 * @method \Cake\Mailer\Email setReplyTo($email, $name = null)
 * @method array getReplyTo()
 * @method \Cake\Mailer\Email replyTo($email = null, $name = null)
 * @method \Cake\Mailer\Email setReadReceipt($email, $name = null)
 * @method array getReadReceipt()
 * @method \Cake\Mailer\Email readReceipt($email = null, $name = null)
 * @method \Cake\Mailer\Email setReturnPath($email, $name = null)
 * @method array getReturnPath()
 * @method \Cake\Mailer\Email returnPath($email = null, $name = null)
 * @method \Cake\Mailer\Email addTo($email, $name = null)
 * @method \Cake\Mailer\Email setCc($email, $name = null)
 * @method array getCc()
 * @method \Cake\Mailer\Email cc($email = null, $name = null)
 * @method \Cake\Mailer\Email addCc($email, $name = null)
 * @method \Cake\Mailer\Email setBcc($email, $name = null)
 * @method array getBcc()
 * @method \Cake\Mailer\Email bcc($email = null, $name = null)
 * @method \Cake\Mailer\Email addBcc($email, $name = null)
 * @method \Cake\Mailer\Email setCharset($charset)
 * @method string getCharset()
 * @method \Cake\Mailer\Email charset($charset = null)
 * @method \Cake\Mailer\Email setHeaderCharset($charset)
 * @method string getHeaderCharset()
 * @method \Cake\Mailer\Email headerCharset($charset = null)
 * @method \Cake\Mailer\Email setSubject($subject)
 * @method string getSubject()
 * @method \Cake\Mailer\Email subject($subject = null)
 * @method \Cake\Mailer\Email setHeaders(array $headers)
 * @method \Cake\Mailer\Email addHeaders(array $headers)
 * @method \Cake\Mailer\Email getHeaders(array $include = [])
 * @method \Cake\Mailer\Email setTemplate($template)
 * @method string getTemplate()
 * @method \Cake\Mailer\Email setLayout($layout)
 * @method string getLayout()
 * @method \Cake\Mailer\Email template($template = false, $layout = false)
 * @method \Cake\Mailer\Email setViewRenderer($viewClass)
 * @method string getViewRenderer()
 * @method \Cake\Mailer\Email viewRender($viewClass = null)
 * @method \Cake\Mailer\Email setViewVars($viewVars)
 * @method array getViewVars()
 * @method \Cake\Mailer\Email viewVars($viewVars = null)
 * @method \Cake\Mailer\Email setTheme($theme)
 * @method string getTheme()
 * @method \Cake\Mailer\Email theme($theme = null)
 * @method \Cake\Mailer\Email setHelpers(array $helpers)
 * @method array getHelpers()
 * @method \Cake\Mailer\Email helpers($helpers = null)
 * @method \Cake\Mailer\Email setEmailFormat($format)
 * @method string getEmailFormat()
 * @method \Cake\Mailer\Email emailFormat($format = null)
 * @method \Cake\Mailer\Email setTransport($name)
 * @method \Cake\Mailer\AbstractTransport getTransport()
 * @method \Cake\Mailer\Email transport($name = null)
 * @method \Cake\Mailer\Email setMessageId($message)
 * @method bool|string getMessageId()
 * @method \Cake\Mailer\Email messageId($message = null)
 * @method \Cake\Mailer\Email setDomain($domain)
 * @method string getDomain()
 * @method \Cake\Mailer\Email domain($domain = null)
 * @method \Cake\Mailer\Email setAttachments($attachments)
 * @method array getAttachments()
 * @method \Cake\Mailer\Email attachments($attachments = null)
 * @method \Cake\Mailer\Email addAttachments($attachments)
 * @method \Cake\Mailer\Email message($type = null)
 * @method \Cake\Mailer\Email setProfile($config)
 * @method string|array getProfile()
 * @method \Cake\Mailer\Email profile($config = null)
 */
abstract class Mailer implements EventListenerInterface
{

    use ModelAwareTrait;

    /**
     * Mailer's name.
     *
     * @var string
     */
    static public $name;

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
    public function __construct(Email $email = null)
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
    public function getName()
    {
        if (!static::$name) {
            static::$name = str_replace(
                'Mailer',
                '',
                implode('', array_slice(explode('\\', get_class($this)), -1))
            );
        }

        return static::$name;
    }

    /**
     * Sets layout to use.
     *
     * @deprecated 3.4.0 Use setLayout() which sets the layout on the email class instead.
     * @param string $layout Name of the layout to use.
     * @return $this
     */
    public function layout($layout)
    {
        $this->_email->viewBuilder()->setLayout($layout);

        return $this;
    }

    /**
     * Get Email instance's view builder.
     *
     * @return \Cake\View\ViewBuilder
     */
    public function viewBuilder()
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
    public function __call($method, $args)
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
    public function send($action, $args = [], $headers = [])
    {
        try {
            if (!method_exists($this, $action)) {
                throw new MissingActionException([
                    'mailer' => $this->getName() . 'Mailer',
                    'action' => $action,
                ]);
            }

            $this->_email->setHeaders($headers);
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
    public function implementedEvents()
    {
        return [];
    }
}
