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

use BadMethodCallException;
use Cake\Core\Exception\Exception;
use Cake\Core\StaticConfigTrait;
use Cake\Datasource\ModelAwareTrait;
use Cake\Event\EventListenerInterface;
use Cake\Log\Log;
use Cake\Mailer\Exception\MissingActionException;
use Cake\View\ViewBuilder;
use InvalidArgumentException;

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
 * @method $this setTo($email, $name = null)
 * @method array getTo()
 * @method $this setFrom($email, $name = null)
 * @method array getFrom()
 * @method $this setSender($email, $name = null)
 * @method array getSender()
 * @method $this setReplyTo($email, $name = null)
 * @method array getReplyTo()
 * @method $this setReadReceipt($email, $name = null)
 * @method array getReadReceipt()
 * @method $this setReturnPath($email, $name = null)
 * @method array getReturnPath()
 * @method $this addTo($email, $name = null)
 * @method $this setCc($email, $name = null)
 * @method array getCc()
 * @method $this addCc($email, $name = null)
 * @method $this setBcc($email, $name = null)
 * @method array getBcc()
 * @method $this addBcc($email, $name = null)
 * @method $this setCharset($charset)
 * @method string getCharset()
 * @method $this setHeaderCharset($charset)
 * @method string getHeaderCharset()
 * @method $this setSubject($subject)
 * @method string getSubject()
 * @method $this setHeaders(array $headers)
 * @method $this addHeaders(array $headers)
 * @method $this getHeaders(array $include = [])
 * @method $this setEmailFormat($format)
 * @method string getEmailFormat()
 * @method $this setMessageId($message)
 * @method bool|string getMessageId()
 * @method $this setDomain($domain)
 * @method string getDomain()
 * @method $this setAttachments($attachments)
 * @method array getAttachments()
 * @method $this addAttachments($attachments)
 * @method string|array getBody(?string $type = null)
 */
class Mailer implements EventListenerInterface
{
    use ModelAwareTrait;
    use StaticConfigTrait;

    /**
     * Mailer's name.
     *
     * @var string
     */
    public static $name;

    /**
     * The transport instance to use for sending mail.
     *
     * @var \Cake\Mailer\AbstractTransport|null
     */
    protected $transport;

    /**
     * Message class name.
     *
     * @var string
     */
    protected $messageClass = Message::class;

    /**
     * Message instance.
     *
     * @var \Cake\Mailer\Message
     */
    protected $message;

    /**
     * Email Renderer
     *
     * @var \Cake\Mailer\Renderer|null
     */
    protected $renderer;

    /**
     * Hold message, renderer and transport instance for restoring after runnning
     * a mailer action.
     *
     * @var array
     */
    protected $clonedInstances = [
        'message' => null,
        'renderer' => null,
        'transport' => null,
    ];

    /**
     * Mailer driver class map.
     *
     * @var array
     * @psalm-var array<string, class-string>
     */
    protected static $_dsnClassMap = [];

    /**
     * @var array|null
     */
    protected $logConfig = null;

    /**
     * Constructor
     *
     * @param array|string|null $config Array of configs, or string to load configs from app.php
     */
    public function __construct($config = null)
    {
        $this->message = new $this->messageClass();

        if ($config === null) {
            $config = static::getConfig('default');
        }

        if ($config) {
            $this->setProfile($config);
        }
    }

    /**
     * Get the view builder.
     *
     * @return \Cake\View\ViewBuilder
     */
    public function viewBuilder(): ViewBuilder
    {
        return $this->getRenderer()->viewBuilder();
    }

    /**
     * Get email renderer.
     *
     * @return \Cake\Mailer\Renderer
     */
    public function getRenderer(): Renderer
    {
        if ($this->renderer === null) {
            $this->renderer = new Renderer();
        }

        return $this->renderer;
    }

    /**
     * Set email renderer.
     *
     * @param \Cake\Mailer\Renderer $renderer Render instance.
     * @return $this
     */
    public function setRenderer(Renderer $renderer)
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * Get message instance.
     *
     * @return \Cake\Mailer\Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * Set message instance.
     *
     * @param \Cake\Mailer\Message $message Message instance.
     * @return $this
     */
    public function setMessage(Message $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Magic method to forward method class to Message instance.
     *
     * @param string $method Method name.
     * @param array $args Method arguments
     * @return $this|mixed
     */
    public function __call(string $method, array $args)
    {
        $result = $this->message->$method(...$args);
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
     * @deprecated 4.0.0 Use Mailer::setViewVars() instead.
     */
    public function set($key, $value = null)
    {
        return $this->setViewVars($key, $value);
    }

    /**
     * Sets email view vars.
     *
     * @param string|array $key Variable name or hash of view variables.
     * @param mixed $value View variable value.
     * @return $this
     */
    public function setViewVars($key, $value = null)
    {
        $this->getRenderer()->set($key, $value);

        return $this;
    }

    /**
     * Sends email.
     *
     * @param string|null $action The name of the mailer action to trigger.
     *   If no action is specified then all other method arguments will be ignored.
     * @param array $args Arguments to pass to the triggered mailer action.
     * @param array $headers Headers to set.
     * @return array
     * @throws \Cake\Mailer\Exception\MissingActionException
     * @throws \BadMethodCallException
     * @psalm-return array{headers: string, message: string}
     */
    public function send(?string $action = null, array $args = [], array $headers = []): array
    {
        if ($action === null) {
            return $this->deliver();
        }

        if (!method_exists($this, $action)) {
            throw new MissingActionException([
                'mailer' => static::class,
                'action' => $action,
            ]);
        }

        $this->clonedInstances['message'] = clone $this->message;
        $this->clonedInstances['renderer'] = clone $this->getRenderer();
        if ($this->transport !== null) {
            $this->clonedInstances['transport'] = clone $this->transport;
        }

        $this->getMessage()->setHeaders($headers);
        if (!$this->viewBuilder()->getTemplate()) {
            $this->viewBuilder()->setTemplate($action);
        }

        try {
            $this->$action(...$args);

            $result = $this->deliver();
        } finally {
            $this->restore();
        }

        return $result;
    }

    /**
     * Render content and set message body.
     *
     * @param string $content Content.
     * @return $this
     */
    public function render(string $content = '')
    {
        $content = $this->getRenderer()->render(
            $content,
            $this->message->getBodyTypes()
        );

        $this->message->setBody($content);

        return $this;
    }

    /**
     * Render content and send email using configured transport.
     *
     * @param string $content Content.
     * @return array
     * @psalm-return array{headers: string, message: string}
     */
    public function deliver(string $content = '')
    {
        $this->render($content);

        $result = $this->getTransport()->send($this->message);
        $this->logDelivery($result);

        return $result;
    }

    /**
     * Sets the configuration profile to use for this instance.
     *
     * @param string|array $config String with configuration name, or
     *    an array with config.
     * @return $this
     */
    public function setProfile($config)
    {
        if (is_string($config)) {
            $name = $config;
            $config = static::getConfig($name);
            if (empty($config)) {
                throw new InvalidArgumentException(sprintf('Unknown email configuration "%s".', $name));
            }
            unset($name);
        }

        $simpleMethods = [
            'transport',
        ];
        foreach ($simpleMethods as $method) {
            if (isset($config[$method])) {
                $this->{'set' . ucfirst($method)}($config[$method]);
                unset($config[$method]);
            }
        }

        $viewBuilderMethods = [
            'template', 'layout', 'theme',
        ];
        foreach ($viewBuilderMethods as $method) {
            if (array_key_exists($method, $config)) {
                $this->viewBuilder()->{'set' . ucfirst($method)}($config[$method]);
                unset($config[$method]);
            }
        }

        if (array_key_exists('helpers', $config)) {
            $this->viewBuilder()->setHelpers($config['helpers'], false);
            unset($config['helpers']);
        }
        if (array_key_exists('viewRenderer', $config)) {
            $this->viewBuilder()->setClassName($config['viewRenderer']);
            unset($config['viewRenderer']);
        }
        if (array_key_exists('viewVars', $config)) {
            $this->viewBuilder()->setVars($config['viewVars']);
            unset($config['viewVars']);
        }

        if (isset($config['log'])) {
            $this->setLogConfig($config['log']);
        }

        $this->message->setConfig($config);

        return $this;
    }

    /**
     * Sets the transport.
     *
     * When setting the transport you can either use the name
     * of a configured transport or supply a constructed transport.
     *
     * @param string|\Cake\Mailer\AbstractTransport $name Either the name of a configured
     *   transport, or a transport instance.
     * @return $this
     * @throws \LogicException When the chosen transport lacks a send method.
     * @throws \InvalidArgumentException When $name is neither a string nor an object.
     */
    public function setTransport($name)
    {
        if (is_string($name)) {
            $transport = TransportFactory::get($name);
        } elseif (is_object($name)) {
            $transport = $name;
            if (!$transport instanceof AbstractTransport) {
                throw new Exception('Transport class must extend Cake\Mailer\AbstractTransport');
            }
        } else {
            throw new InvalidArgumentException(sprintf(
                'The value passed for the "$name" argument must be either a string, or an object, %s given.',
                gettype($name)
            ));
        }

        $this->transport = $transport;

        return $this;
    }

    /**
     * Gets the transport.
     *
     * @return \Cake\Mailer\AbstractTransport
     */
    public function getTransport(): AbstractTransport
    {
        if ($this->transport === null) {
            throw new BadMethodCallException(
                'Transport was not defined. '
                . 'You must set on using setTransport() or set `transport` option in your mailer profile.'
            );
        }

        return $this->transport;
    }

    /**
     * Restore message, renderer, transport instances to state before an action was run.
     *
     * @return $this
     */
    protected function restore()
    {
        foreach (array_keys($this->clonedInstances) as $key) {
            if ($this->clonedInstances[$key] === null) {
                $this->{$key} = null;
            } else {
                $this->{$key} = clone $this->clonedInstances[$key];
                $this->clonedInstances[$key] = null;
            }
        }

        return $this;
    }

    /**
     * Reset all the internal variables to be able to send out a new email.
     *
     * @return $this
     */
    public function reset()
    {
        $this->message->reset();
        $this->getRenderer()->reset();
        $this->transport = null;
        $this->clonedInstances = [
            'message' => null,
            'renderer' => null,
            'transport' => null,
        ];

        return $this;
    }

    /**
     * Log the email message delivery.
     *
     * @param array $contents The content with 'headers' and 'message' keys.
     * @return void
     * @psalm-param array{headers: string, message: string} $contents
     */
    protected function logDelivery(array $contents): void
    {
        if (empty($this->logConfig)) {
            return;
        }

        Log::write(
            $this->logConfig['level'],
            PHP_EOL . $this->flatten($contents['headers']) . PHP_EOL . PHP_EOL . $this->flatten($contents['message']),
            $this->logConfig['scope']
        );
    }

    /**
     * Set logging config.
     *
     * @param string|array|true $log Log config.
     * @return void
     */
    protected function setLogConfig($log)
    {
        $config = [
            'level' => 'debug',
            'scope' => 'email',
        ];
        if ($log !== true) {
            if (!is_array($log)) {
                $log = ['level' => $log];
            }
            $config = $log + $config;
        }

        $this->logConfig = $config;
    }

    /**
     * Converts given value to string
     *
     * @param string|array $value The value to convert
     * @return string
     */
    protected function flatten($value): string
    {
        return is_array($value) ? implode(';', $value) : $value;
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
