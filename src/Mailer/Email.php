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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer;

use BadMethodCallException;
use Cake\Core\StaticConfigTrait;
use Cake\Log\Log;
use Cake\View\View;
use Cake\View\ViewBuilder;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use Serializable;
use SimpleXMLElement;

/**
 * CakePHP Email class.
 *
 * This class is used for sending Internet Message Format based
 * on the standard outlined in https://www.rfc-editor.org/rfc/rfc2822.txt
 *
 * ### Configuration
 *
 * Configuration for Email is managed by Email::config() and Email::configTransport().
 * Email::config() can be used to add or read a configuration profile for Email instances.
 * Once made configuration profiles can be used to re-use across various email messages your
 * application sends.
 */
class Email implements JsonSerializable, Serializable
{
    use StaticConfigTrait;

    /**
     * Type of message - HTML
     *
     * @var string
     * @deprecated 4.0.0 Use Message::MESSAGE_HTML instead.
     */
    public const MESSAGE_HTML = 'html';

    /**
     * Type of message - TEXT
     *
     * @var string
     * @deprecated 4.0.0 Use Message::MESSAGE_TEXT instead.
     */
    public const MESSAGE_TEXT = 'text';

    /**
     * Type of message - BOTH
     *
     * @var string
     * @deprecated 4.0.0 Use Message::MESSAGE_BOTH instead.
     */
    public const MESSAGE_BOTH = 'both';

    /**
     * Holds the regex pattern for email validation
     *
     * @var string
     * @deprecated 4.0.0 Use Message::EMAIL_PATTERN instead.
     */
    public const EMAIL_PATTERN = '/^((?:[\p{L}0-9.!#$%&\'*+\/=?^_`{|}~-]+)*@[\p{L}0-9-._]+)$/ui';

    /**
     * Email driver class map.
     *
     * @var array
     */
    protected static $_dsnClassMap = [];

    /**
     * The transport instance to use for sending mail.
     *
     * @var \Cake\Mailer\AbstractTransport|null
     */
    protected $_transport;

    /**
     * Email Renderer
     *
     * @var \Cake\Mailer\Renderer|null
     */
    protected $renderer;

    /**
     * A copy of the configuration profile for this
     * instance. This copy can be modified with Email::profile().
     *
     * @var array
     */
    protected $_profile = [];

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

        $this->getRenderer()->viewBuilder()
            ->setClassName(View::class)
            ->setTemplate('')
            ->setLayout('default')
            ->setHelpers(['Html']);

        if ($config) {
            $this->setProfile($config);
        }
    }

    /**
     * Clone Renderer instance when email object is cloned.
     *
     * @return void
     */
    public function __clone()
    {
        if ($this->renderer) {
            $this->renderer = clone $this->renderer;
        }

        if ($this->message !== null) {
            $this->message = clone $this->message;
        }
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
        $result = $this->message->$method(...$args);

        if (strpos($method, 'get') === 0) {
            return $result;
        }

        $getters = ['message'];
        if (in_array($method, $getters, true)) {
            return $result;
        }

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
     * Sets view class for render.
     *
     * @param string $viewClass View class name.
     * @return $this
     */
    public function setViewRenderer(string $viewClass)
    {
        $this->getRenderer()->viewBuilder()->setClassName($viewClass);

        return $this;
    }

    /**
     * Gets view class for render.
     *
     * @return string
     * @psalm-suppress InvalidNullableReturnType
     */
    public function getViewRenderer(): string
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->getRenderer()->viewBuilder()->getClassName();
    }

    /**
     * Sets variables to be set on render.
     *
     * @param array $viewVars Variables to set for view.
     * @return $this
     */
    public function setViewVars(array $viewVars)
    {
        $this->getRenderer()->viewBuilder()->setVars($viewVars);

        return $this;
    }

    /**
     * Gets variables to be set on render.
     *
     * @return array
     */
    public function getViewVars(): array
    {
        return $this->getRenderer()->viewBuilder()->getVars();
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
        } else {
            throw new InvalidArgumentException(sprintf(
                'The value passed for the "$name" argument must be either a string, or an object, %s given.',
                gettype($name)
            ));
        }
        if (!method_exists($transport, 'send')) {
            throw new LogicException(sprintf('The "%s" do not have send method.', get_class($transport)));
        }

        $this->_transport = $transport;

        return $this;
    }

    /**
     * Gets the transport.
     *
     * @return \Cake\Mailer\AbstractTransport|null
     */
    public function getTransport(): ?AbstractTransport
    {
        return $this->_transport;
    }

    /**
     * Get generated message (used by transport classes)
     *
     * @param string|null $type Use MESSAGE_* constants or null to return the full message as array
     * @return string|array String if type is given, array if type is null
     */
    public function message(?string $type = null)
    {
        return $this->message->getBody($type);
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

        $this->_profile = array_merge($this->_profile, $config);

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
                $this->getRenderer()->viewBuilder()->{'set' . ucfirst($method)}($config[$method]);
                unset($config[$method]);
            }
        }

        if (array_key_exists('helpers', $config)) {
            $this->getRenderer()->viewBuilder()->setHelpers($config['helpers'], false);
            unset($config['helpers']);
        }
        if (array_key_exists('viewRenderer', $config)) {
            $this->getRenderer()->viewBuilder()->setClassName($config['viewRenderer']);
            unset($config['viewRenderer']);
        }
        if (array_key_exists('viewVars', $config)) {
            $this->getRenderer()->viewBuilder()->setVars($config['viewVars']);
            unset($config['viewVars']);
        }

        $this->message->setConfig($config);

        return $this;
    }

    /**
     * Gets the configuration profile to use for this instance.
     *
     * @return array
     */
    public function getProfile(): array
    {
        return $this->_profile;
    }

    /**
     * Send an email using the specified content, template and layout
     *
     * @param string|array|null $content String with message or array with messages
     * @return array
     * @throws \BadMethodCallException
     */
    public function send($content = null): array
    {
        if (empty($this->message->getFrom())) {
            throw new BadMethodCallException('From is not specified.');
        }

        if (empty($this->message->getTo())
            && empty($this->message->getCc())
            && empty($this->message->getBcc())
        ) {
            throw new BadMethodCallException('You need specify one destination on to, cc or bcc.');
        }

        if (is_array($content)) {
            $content = implode("\n", $content) . "\n";
        }

        $this->render($content);

        $transport = $this->getTransport();
        if (!$transport) {
            $msg = 'Cannot send email, transport was not defined. Did you call transport() or define ' .
                ' a transport in the set profile?';
            throw new BadMethodCallException($msg);
        }
        $contents = $transport->send($this->message);
        $this->_logDelivery($contents);

        return $contents;
    }

    /**
     * Render email.
     *
     * @param string|array|null $content Content array or string
     * @return void
     */
    public function render($content = null): void
    {
        if (is_array($content)) {
            $content = implode("\n", $content) . "\n";
        }

        $this->message->setBody(
            $this->getRenderer()->getContent(
                (string)$content,
                $this->message->getBodyTypes()
            )
        );
    }

    /**
     * Get view builder.
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
     * Log the email message delivery.
     *
     * @param array $contents The content with 'headers' and 'message' keys.
     * @return void
     */
    protected function _logDelivery(array $contents): void
    {
        if (empty($this->_profile['log'])) {
            return;
        }
        $config = [
            'level' => 'debug',
            'scope' => 'email',
        ];
        if ($this->_profile['log'] !== true) {
            if (!is_array($this->_profile['log'])) {
                $this->_profile['log'] = ['level' => $this->_profile['log']];
            }
            $config = $this->_profile['log'] + $config;
        }
        Log::write(
            $config['level'],
            PHP_EOL . $this->flatten($contents['headers']) . PHP_EOL . PHP_EOL . $this->flatten($contents['message']),
            $config['scope']
        );
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
     * Static method to fast create an instance of \Cake\Mailer\Email
     *
     * @param string|array|null $to Address to send (see Cake\Mailer\Email::to()).
     *   If null, will try to use 'to' from transport config
     * @param string|null $subject String of subject or null to use 'subject' from transport config
     * @param string|array|null $message String with message or array with variables to be used in render
     * @param string|array $config String to use Email delivery profile from app.php or array with configs
     * @param bool $send Send the email or just return the instance pre-configured
     * @return static Instance of Cake\Mailer\Email
     * @throws \InvalidArgumentException
     */
    public static function deliver(
        $to = null,
        ?string $subject = null,
        $message = null,
        $config = 'default',
        bool $send = true
    ) {
        $class = self::class;

        if (is_array($config) && !isset($config['transport'])) {
            $config['transport'] = 'default';
        }
        /** @var \Cake\Mailer\Email $instance */
        $instance = new $class($config);
        if ($to !== null) {
            $instance->getMessage()->setTo($to);
        }
        if ($subject !== null) {
            $instance->getMessage()->setSubject($subject);
        }
        if (is_array($message)) {
            $instance->setViewVars($message);
            $message = null;
        } elseif ($message === null) {
            $config = $instance->getProfile();
            if (array_key_exists('message', $config)) {
                $message = $config['message'];
            }
        }

        if ($send === true) {
            $instance->send($message);
        }

        return $instance;
    }

    /**
     * Reset all the internal variables to be able to send out a new email.
     *
     * @return $this
     */
    public function reset()
    {
        $this->message->reset();
        $this->_transport = null;
        $this->_profile = [];

        $this->getRenderer()->viewBuilder()
            ->setLayout('default')
            ->setTemplate('')
            ->setClassName(View::class)
            ->setTheme(null)
            ->setHelpers(['Html'], false)
            ->setVars([], false);

        return $this;
    }

    /**
     * Serializes the email object to a value that can be natively serialized and re-used
     * to clone this email instance.
     *
     * @return array Serializable array of configuration properties.
     * @throws \Exception When a view var object can not be properly serialized.
     */
    public function jsonSerialize(): array
    {
        $array = $this->message->jsonSerialize();
        $array['viewConfig'] = $this->getRenderer()->viewBuilder()->jsonSerialize();

        return $array;
    }

    /**
     * Configures an email instance object from serialized config.
     *
     * @param array $config Email configuration array.
     * @return $this Configured email instance.
     */
    public function createFromArray(array $config)
    {
        if (isset($config['viewConfig'])) {
            $this->getRenderer()->viewBuilder()->createFromArray($config['viewConfig']);
            unset($config['viewConfig']);
        }

        if ($this->message === null) {
            $this->message = new $this->messageClass();
        }
        $this->message->createFromArray($config);

        return $this;
    }

    /**
     * Serializes the Email object.
     *
     * @return string
     */
    public function serialize(): string
    {
        $array = $this->jsonSerialize();
        array_walk_recursive($array, function (&$item, $key): void {
            if ($item instanceof SimpleXMLElement) {
                $item = json_decode(json_encode((array)$item), true);
            }
        });

        return serialize($array);
    }

    /**
     * Unserializes the Email object.
     *
     * @param string $data Serialized string.
     * @return void
     */
    public function unserialize($data): void
    {
        $this->createFromArray(unserialize($data));
    }
}
