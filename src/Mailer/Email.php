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
use Cake\Core\Configure;
use Cake\Core\StaticConfigTrait;
use Cake\Log\Log;
use Cake\Utility\Text;
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
     */
    public const MESSAGE_HTML = 'html';

    /**
     * Type of message - TEXT
     *
     * @var string
     */
    public const MESSAGE_TEXT = 'text';

    /**
     * Type of message - BOTH
     *
     * @var string
     */
    public const MESSAGE_BOTH = 'both';

    /**
     * Holds the regex pattern for email validation
     *
     * @var string
     */
    public const EMAIL_PATTERN = '/^((?:[\p{L}0-9.!#$%&\'*+\/=?^_`{|}~-]+)*@[\p{L}0-9-._]+)$/ui';

    /**
     * Recipient of the email
     *
     * @var array
     */
    protected $_to = [];

    /**
     * The mail which the email is sent from
     *
     * @var array
     */
    protected $_from = [];

    /**
     * The sender email
     *
     * @var array
     */
    protected $_sender = [];

    /**
     * The email the recipient will reply to
     *
     * @var array
     */
    protected $_replyTo = [];

    /**
     * The read receipt email
     *
     * @var array
     */
    protected $_readReceipt = [];

    /**
     * The mail that will be used in case of any errors like
     * - Remote mailserver down
     * - Remote user has exceeded his quota
     * - Unknown user
     *
     * @var array
     */
    protected $_returnPath = [];

    /**
     * Carbon Copy
     *
     * List of email's that should receive a copy of the email.
     * The Recipient WILL be able to see this list
     *
     * @var array
     */
    protected $_cc = [];

    /**
     * Blind Carbon Copy
     *
     * List of email's that should receive a copy of the email.
     * The Recipient WILL NOT be able to see this list
     *
     * @var array
     */
    protected $_bcc = [];

    /**
     * Message ID
     *
     * @var bool|string
     */
    protected $_messageId = true;

    /**
     * Domain for messageId generation.
     * Needs to be manually set for CLI mailing as env('HTTP_HOST') is empty
     *
     * @var string
     */
    protected $_domain = '';

    /**
     * The subject of the email
     *
     * @var string
     */
    protected $_subject = '';

    /**
     * Associative array of a user defined headers
     * Keys will be prefixed 'X-' as per RFC2822 Section 4.7.5
     *
     * @var array
     */
    protected $_headers = [];

    /**
     * Text message
     *
     * @var string
     */
    protected $_textMessage = '';

    /**
     * Html message
     *
     * @var string
     */
    protected $_htmlMessage = '';

    /**
     * Final message to send
     *
     * @var array
     */
    protected $_message = [];

    /**
     * Available formats to be sent.
     *
     * @var array
     */
    protected $_emailFormatAvailable = [self::MESSAGE_TEXT, self::MESSAGE_HTML, self::MESSAGE_BOTH];

    /**
     * What format should the email be sent in
     *
     * @var string
     */
    protected $_emailFormat = self::MESSAGE_TEXT;

    /**
     * The transport instance to use for sending mail.
     *
     * @var \Cake\Mailer\AbstractTransport|null
     */
    protected $_transport;

    /**
     * Charset the email body is sent in
     *
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * Charset the email header is sent in
     * If null, the $charset property will be used as default
     *
     * @var string|null
     */
    protected $headerCharset;

    /**
     * The email transfer encoding used.
     * If null, the $charset property is used for determined the transfer encoding.
     *
     * @var string|null
     */
    protected $transferEncoding;

    /**
     * Email Renderer
     *
     * @var \Cake\Mailer\Renderer
     */
    protected $renderer;

    /**
     * Available encoding to be set for transfer.
     *
     * @var array
     */
    protected $_transferEncodingAvailable = [
        '7bit',
        '8bit',
        'base64',
        'binary',
        'quoted-printable',
    ];

    /**
     * The application wide charset, used to encode headers and body
     *
     * @var string|null
     */
    protected $_appCharset;

    /**
     * List of files that should be attached to the email.
     *
     * Only absolute paths
     *
     * @var array
     */
    protected $_attachments = [];

    /**
     * If set, boundary to use for multipart mime messages
     *
     * @var string|null
     */
    protected $_boundary;

    /**
     * Contains the optional priority of the email.
     *
     * @var int|null
     */
    protected $_priority;

    /**
     * A copy of the configuration profile for this
     * instance. This copy can be modified with Email::profile().
     *
     * @var array
     */
    protected $_profile = [];

    /**
     * 8Bit character sets
     *
     * @var array
     */
    protected $_charset8bit = ['UTF-8', 'SHIFT_JIS'];

    /**
     * Define Content-Type charset name
     *
     * @var array
     */
    protected $_contentTypeCharset = [
        'ISO-2022-JP-MS' => 'ISO-2022-JP',
    ];

    /**
     * Regex for email validation
     *
     * If null, filter_var() will be used. Use the emailPattern() method
     * to set a custom pattern.'
     *
     * @var string|null
     */
    protected $_emailPattern = self::EMAIL_PATTERN;

    /**
     * Constructor
     *
     * @param array|string|null $config Array of configs, or string to load configs from app.php
     */
    public function __construct($config = null)
    {
        $this->_appCharset = Configure::read('App.encoding');
        if ($this->_appCharset !== null) {
            $this->charset = $this->_appCharset;
        }
        $this->_domain = preg_replace('/\:\d+$/', '', env('HTTP_HOST'));
        if (empty($this->_domain)) {
            $this->_domain = php_uname('n');
        }

        $this->getRenderer()->viewBuilder()
            ->setClassName('Cake\View\View')
            ->setTemplate('')
            ->setLayout('default')
            ->setHelpers(['Html']);

        if ($config === null) {
            $config = static::getConfig('default');
        }
        if ($config) {
            $this->setProfile($config);
        }
        if (empty($this->headerCharset)) {
            $this->headerCharset = $this->charset;
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
    }

    /**
     * Sets "from" address.
     *
     * @param string|array $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setFrom($email, ?string $name = null): self
    {
        return $this->_setEmailSingle('_from', $email, $name, 'From requires only 1 email address.');
    }

    /**
     * Gets "from" address.
     *
     * @return array
     */
    public function getFrom(): array
    {
        return $this->_from;
    }

    /**
     * Sets "sender" address.
     *
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setSender($email, ?string $name = null): self
    {
        return $this->_setEmailSingle('_sender', $email, $name, 'Sender requires only 1 email address.');
    }

    /**
     * Gets "sender" address.
     *
     * @return array
     */
    public function getSender(): array
    {
        return $this->_sender;
    }

    /**
     * Sets "Reply-To" address.
     *
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setReplyTo($email, ?string $name = null): self
    {
        return $this->_setEmailSingle('_replyTo', $email, $name, 'Reply-To requires only 1 email address.');
    }

    /**
     * Gets "Reply-To" address.
     *
     * @return array
     */
    public function getReplyTo(): array
    {
        return $this->_replyTo;
    }

    /**
     * Sets Read Receipt (Disposition-Notification-To header).
     *
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setReadReceipt($email, ?string $name = null): self
    {
        return $this->_setEmailSingle(
            '_readReceipt',
            $email,
            $name,
            'Disposition-Notification-To requires only 1 email address.'
        );
    }

    /**
     * Gets Read Receipt (Disposition-Notification-To header).
     *
     * @return array
     */
    public function getReadReceipt(): array
    {
        return $this->_readReceipt;
    }

    /**
     * Return Path
     *
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setReturnPath($email, ?string $name = null): self
    {
        return $this->_setEmailSingle('_returnPath', $email, $name, 'Return-Path requires only 1 email address.');
    }

    /**
     * Gets return path.
     *
     * @return array
     */
    public function getReturnPath(): array
    {
        return $this->_returnPath;
    }

    /**
     * Sets "to" address.
     *
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     */
    public function setTo($email, ?string $name = null): self
    {
        return $this->_setEmail('_to', $email, $name);
    }

    /**
     * Gets "to" address
     *
     * @return array
     */
    public function getTo(): array
    {
        return $this->_to;
    }

    /**
     * Add To
     *
     * @param string|array $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     */
    public function addTo($email, ?string $name = null): self
    {
        return $this->_addEmail('_to', $email, $name);
    }

    /**
     * Sets "cc" address.
     *
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     */
    public function setCc($email, ?string $name = null): self
    {
        return $this->_setEmail('_cc', $email, $name);
    }

    /**
     * Gets "cc" address.
     *
     * @return array
     */
    public function getCc(): array
    {
        return $this->_cc;
    }

    /**
     * Add Cc
     *
     * @param string|array $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     */
    public function addCc($email, ?string $name = null): self
    {
        return $this->_addEmail('_cc', $email, $name);
    }

    /**
     * Sets "bcc" address.
     *
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     */
    public function setBcc($email, ?string $name = null): self
    {
        return $this->_setEmail('_bcc', $email, $name);
    }

    /**
     * Gets "bcc" address.
     *
     * @return array
     */
    public function getBcc(): array
    {
        return $this->_bcc;
    }

    /**
     * Add Bcc
     *
     * @param string|array $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     */
    public function addBcc($email, ?string $name = null): self
    {
        return $this->_addEmail('_bcc', $email, $name);
    }

    /**
     * Charset setter.
     *
     * @param string $charset Character set.
     * @return $this
     */
    public function setCharset(string $charset): self
    {
        $this->charset = $charset;
        if (!$this->headerCharset) {
            $this->headerCharset = $charset;
        }

        return $this;
    }

    /**
     * Charset getter.
     *
     * @return string Charset
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * HeaderCharset setter.
     *
     * @param string|null $charset Character set.
     * @return $this
     */
    public function setHeaderCharset(?string $charset): self
    {
        $this->headerCharset = $charset;

        return $this;
    }

    /**
     * HeaderCharset getter.
     *
     * @return string|null Charset
     */
    public function getHeaderCharset(): ?string
    {
        return $this->headerCharset;
    }

    /**
     * TransferEncoding setter.
     *
     * @param string|null $encoding Encoding set.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTransferEncoding(?string $encoding): self
    {
        $encoding = strtolower($encoding);
        if (!in_array($encoding, $this->_transferEncodingAvailable)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Transfer encoding not available. Can be : %s.',
                    implode(', ', $this->_transferEncodingAvailable)
                )
            );
        }
        $this->transferEncoding = $encoding;

        return $this;
    }

    /**
     * TransferEncoding getter.
     *
     * @return string|null Encoding
     */
    public function getTransferEncoding(): ?string
    {
        return $this->transferEncoding;
    }

    /**
     * EmailPattern setter/getter
     *
     * @param string|null $regex The pattern to use for email address validation,
     *   null to unset the pattern and make use of filter_var() instead.
     * @return $this
     */
    public function setEmailPattern(?string $regex): self
    {
        $this->_emailPattern = $regex;

        return $this;
    }

    /**
     * EmailPattern setter/getter
     *
     * @return string|null
     */
    public function getEmailPattern(): ?string
    {
        return $this->_emailPattern;
    }

    /**
     * Set email
     *
     * @param string $varName Property name
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function _setEmail(string $varName, $email, ?string $name): self
    {
        if (!is_array($email)) {
            $this->_validateEmail($email, $varName);
            if ($name === null) {
                $name = $email;
            }
            $this->{$varName} = [$email => $name];

            return $this;
        }
        $list = [];
        foreach ($email as $key => $value) {
            if (is_int($key)) {
                $key = $value;
            }
            $this->_validateEmail($key, $varName);
            $list[$key] = $value;
        }
        $this->{$varName} = $list;

        return $this;
    }

    /**
     * Validate email address
     *
     * @param string $email Email address to validate
     * @param string $context Which property was set
     * @return void
     * @throws \InvalidArgumentException If email address does not validate
     */
    protected function _validateEmail(string $email, string $context): void
    {
        if ($this->_emailPattern === null) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return;
            }
        } elseif (preg_match($this->_emailPattern, (string)$email)) {
            return;
        }

        $context = ltrim($context, '_');
        if ($email === '') {
            throw new InvalidArgumentException(sprintf('The email set for "%s" is empty.', $context));
        }
        throw new InvalidArgumentException(sprintf('Invalid email set for "%s". You passed "%s".', $context, $email));
    }

    /**
     * Set only 1 email
     *
     * @param string $varName Property name
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @param string $throwMessage Exception message
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function _setEmailSingle(string $varName, $email, ?string $name, string $throwMessage): self
    {
        if ($email === []) {
            $this->{$varName} = $email;

            return $this;
        }

        $current = $this->{$varName};
        $this->_setEmail($varName, $email, $name);
        if (count($this->{$varName}) !== 1) {
            $this->{$varName} = $current;
            throw new InvalidArgumentException($throwMessage);
        }

        return $this;
    }

    /**
     * Add email
     *
     * @param string $varName Property name
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function _addEmail(string $varName, $email, ?string $name): self
    {
        if (!is_array($email)) {
            $this->_validateEmail($email, $varName);
            if ($name === null) {
                $name = $email;
            }
            $this->{$varName}[$email] = $name;

            return $this;
        }
        $list = [];
        foreach ($email as $key => $value) {
            if (is_int($key)) {
                $key = $value;
            }
            $this->_validateEmail($key, $varName);
            $list[$key] = $value;
        }
        $this->{$varName} = array_merge($this->{$varName}, $list);

        return $this;
    }

    /**
     * Sets subject.
     *
     * @param string $subject Subject string.
     * @return $this
     */
    public function setSubject(string $subject): self
    {
        $this->_subject = $this->_encode($subject);

        return $this;
    }

    /**
     * Gets subject.
     *
     * @return string
     */
    public function getSubject(): string
    {
        return $this->_subject;
    }

    /**
     * Get original subject without encoding
     *
     * @return string Original subject
     */
    public function getOriginalSubject(): string
    {
        return $this->_decode($this->_subject);
    }

    /**
     * Sets headers for the message
     *
     * @param array $headers Associative array containing headers to be set.
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        $this->_headers = $headers;

        return $this;
    }

    /**
     * Add header for the message
     *
     * @param array $headers Headers to set.
     * @return $this
     */
    public function addHeaders(array $headers): self
    {
        $this->_headers = array_merge($this->_headers, $headers);

        return $this;
    }

    /**
     * Get list of headers
     *
     * ### Includes:
     *
     * - `from`
     * - `replyTo`
     * - `readReceipt`
     * - `returnPath`
     * - `to`
     * - `cc`
     * - `bcc`
     * - `subject`
     *
     * @param array $include List of headers.
     * @return array
     */
    public function getHeaders(array $include = []): array
    {
        if ($include === array_values($include)) {
            $include = array_fill_keys($include, true);
        }
        $defaults = array_fill_keys(
            [
                'from', 'sender', 'replyTo', 'readReceipt', 'returnPath',
                'to', 'cc', 'bcc', 'subject',
            ],
            false
        );
        $include += $defaults;

        $headers = [];
        $relation = [
            'from' => 'From',
            'replyTo' => 'Reply-To',
            'readReceipt' => 'Disposition-Notification-To',
            'returnPath' => 'Return-Path',
        ];
        foreach ($relation as $var => $header) {
            if ($include[$var]) {
                $var = '_' . $var;
                $headers[$header] = current($this->_formatAddress($this->{$var}));
            }
        }
        if ($include['sender']) {
            if (key($this->_sender) === key($this->_from)) {
                $headers['Sender'] = '';
            } else {
                $headers['Sender'] = current($this->_formatAddress($this->_sender));
            }
        }

        foreach (['to', 'cc', 'bcc'] as $var) {
            if ($include[$var]) {
                $classVar = '_' . $var;
                $headers[ucfirst($var)] = implode(', ', $this->_formatAddress($this->{$classVar}));
            }
        }

        $headers += $this->_headers;
        if (!isset($headers['Date'])) {
            $headers['Date'] = date(DATE_RFC2822);
        }
        if ($this->_messageId !== false) {
            if ($this->_messageId === true) {
                $this->_messageId = '<' . str_replace('-', '', Text::uuid()) . '@' . $this->_domain . '>';
            }

            $headers['Message-ID'] = $this->_messageId;
        }

        if ($this->_priority) {
            $headers['X-Priority'] = $this->_priority;
        }

        if ($include['subject']) {
            $headers['Subject'] = $this->_subject;
        }

        $headers['MIME-Version'] = '1.0';
        if ($this->_attachments) {
            $headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->_boundary . '"';
        } elseif ($this->_emailFormat === static::MESSAGE_BOTH) {
            $headers['Content-Type'] = 'multipart/alternative; boundary="' . $this->_boundary . '"';
        } elseif ($this->_emailFormat === static::MESSAGE_TEXT) {
            $headers['Content-Type'] = 'text/plain; charset=' . $this->getContentTypeCharset();
        } elseif ($this->_emailFormat === static::MESSAGE_HTML) {
            $headers['Content-Type'] = 'text/html; charset=' . $this->getContentTypeCharset();
        }
        $headers['Content-Transfer-Encoding'] = $this->getContentTransferEncoding();

        return $headers;
    }

    /**
     * Format addresses
     *
     * If the address contains non alphanumeric/whitespace characters, it will
     * be quoted as characters like `:` and `,` are known to cause issues
     * in address header fields.
     *
     * @param array $address Addresses to format.
     * @return array
     */
    protected function _formatAddress(array $address): array
    {
        $return = [];
        foreach ($address as $email => $alias) {
            if ($email === $alias) {
                $return[] = $email;
            } else {
                $encoded = $this->_encode($alias);
                if ($encoded === $alias && preg_match('/[^a-z0-9 ]/i', $encoded)) {
                    $encoded = '"' . str_replace('"', '\"', $encoded) . '"';
                }
                $return[] = sprintf('%s <%s>', $encoded, $email);
            }
        }

        return $return;
    }

    /**
     * Sets view class for render.
     *
     * @param string $viewClass View class name.
     * @return $this
     */
    public function setViewRenderer(string $viewClass): self
    {
        $this->getRenderer()->viewBuilder()->setClassName($viewClass);

        return $this;
    }

    /**
     * Gets view class for render.
     *
     * @return string
     */
    public function getViewRenderer(): string
    {
        return $this->getRenderer()->viewBuilder()->getClassName();
    }

    /**
     * Sets variables to be set on render.
     *
     * @param array $viewVars Variables to set for view.
     * @return $this
     */
    public function setViewVars(array $viewVars): self
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
     * Sets email format.
     *
     * @param string $format Formatting string.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setEmailFormat(string $format): self
    {
        if (!in_array($format, $this->_emailFormatAvailable)) {
            throw new InvalidArgumentException('Format not available.');
        }
        $this->_emailFormat = $format;

        return $this;
    }

    /**
     * Gets email format.
     *
     * @return string
     */
    public function getEmailFormat(): string
    {
        return $this->_emailFormat;
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
    public function setTransport($name): self
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
     * Sets message ID.
     *
     * @param bool|string $message True to generate a new Message-ID, False to ignore (not send in email),
     *   String to set as Message-ID.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setMessageId($message): self
    {
        if (is_bool($message)) {
            $this->_messageId = $message;
        } else {
            if (!preg_match('/^\<.+@.+\>$/', $message)) {
                throw new InvalidArgumentException(
                    'Invalid format to Message-ID. The text should be something like "<uuid@server.com>"'
                );
            }
            $this->_messageId = $message;
        }

        return $this;
    }

    /**
     * Gets message ID.
     *
     * @return bool|string
     */
    public function getMessageId()
    {
        return $this->_messageId;
    }

    /**
     * Sets domain.
     *
     * Domain as top level (the part after @).
     *
     * @param string $domain Manually set the domain for CLI mailing.
     * @return $this
     */
    public function setDomain(string $domain): self
    {
        $this->_domain = $domain;

        return $this;
    }

    /**
     * Gets domain.
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->_domain;
    }

    /**
     * Add attachments to the email message
     *
     * Attachments can be defined in a few forms depending on how much control you need:
     *
     * Attach a single file:
     *
     * ```
     * $email->setAttachments('path/to/file');
     * ```
     *
     * Attach a file with a different filename:
     *
     * ```
     * $email->setAttachments(['custom_name.txt' => 'path/to/file.txt']);
     * ```
     *
     * Attach a file and specify additional properties:
     *
     * ```
     * $email->setAttachments(['custom_name.png' => [
     *      'file' => 'path/to/file',
     *      'mimetype' => 'image/png',
     *      'contentId' => 'abc123',
     *      'contentDisposition' => false
     *    ]
     * ]);
     * ```
     *
     * Attach a file from string and specify additional properties:
     *
     * ```
     * $email->setAttachments(['custom_name.png' => [
     *      'data' => file_get_contents('path/to/file'),
     *      'mimetype' => 'image/png'
     *    ]
     * ]);
     * ```
     *
     * The `contentId` key allows you to specify an inline attachment. In your email text, you
     * can use `<img src="cid:abc123" />` to display the image inline.
     *
     * The `contentDisposition` key allows you to disable the `Content-Disposition` header, this can improve
     * attachment compatibility with outlook email clients.
     *
     * @param array $attachments Array of filenames.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setAttachments(array $attachments): self
    {
        $attach = [];
        foreach ($attachments as $name => $fileInfo) {
            if (!is_array($fileInfo)) {
                $fileInfo = ['file' => $fileInfo];
            }
            if (!isset($fileInfo['file'])) {
                if (!isset($fileInfo['data'])) {
                    throw new InvalidArgumentException('No file or data specified.');
                }
                if (is_int($name)) {
                    throw new InvalidArgumentException('No filename specified.');
                }
                $fileInfo['data'] = chunk_split(base64_encode($fileInfo['data']), 76, "\r\n");
            } else {
                $fileName = $fileInfo['file'];
                $fileInfo['file'] = realpath($fileInfo['file']);
                if ($fileInfo['file'] === false || !file_exists($fileInfo['file'])) {
                    throw new InvalidArgumentException(sprintf('File not found: "%s"', $fileName));
                }
                if (is_int($name)) {
                    $name = basename($fileInfo['file']);
                }
            }
            if (!isset($fileInfo['mimetype']) && isset($fileInfo['file']) && function_exists('mime_content_type')) {
                $fileInfo['mimetype'] = mime_content_type($fileInfo['file']);
            }
            if (!isset($fileInfo['mimetype'])) {
                $fileInfo['mimetype'] = 'application/octet-stream';
            }
            $attach[$name] = $fileInfo;
        }
        $this->_attachments = $attach;

        return $this;
    }

    /**
     * Gets attachments to the email message.
     *
     * @return array Array of attachments.
     */
    public function getAttachments(): array
    {
        return $this->_attachments;
    }

    /**
     * Add attachments
     *
     * @param array $attachments Array of filenames.
     * @return $this
     * @throws \InvalidArgumentException
     * @see \Cake\Mailer\Email::setAttachments()
     */
    public function addAttachments(array $attachments): self
    {
        $current = $this->_attachments;
        $this->setAttachments($attachments);
        $this->_attachments = array_merge($current, $this->_attachments);

        return $this;
    }

    /**
     * Get generated message (used by transport classes)
     *
     * @param string|null $type Use MESSAGE_* constants or null to return the full message as array
     * @return string|array String if type is given, array if type is null
     */
    public function message(?string $type = null)
    {
        switch ($type) {
            case static::MESSAGE_HTML:
                return $this->_htmlMessage;
            case static::MESSAGE_TEXT:
                return $this->_textMessage;
        }

        return $this->_message;
    }

    /**
     * Sets priority.
     *
     * @param int|null $priority 1 (highest) to 5 (lowest)
     * @return $this
     */
    public function setPriority(?int $priority): self
    {
        $this->_priority = $priority;

        return $this;
    }

    /**
     * Gets priority.
     *
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->_priority;
    }

    /**
     * Sets the configuration profile to use for this instance.
     *
     * @param string|array $config String with configuration name, or
     *    an array with config.
     * @return $this
     */
    public function setProfile($config): self
    {
        $this->_applyConfig($config);

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
        if (empty($this->_from)) {
            throw new BadMethodCallException('From is not specified.');
        }
        if (empty($this->_to) && empty($this->_cc) && empty($this->_bcc)) {
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
        $contents = $transport->send($this);
        $this->_logDelivery($contents);

        return $contents;
    }

    /**
     * Render email.
     *
     * @param string|null $content Content array or string
     * @return void
     */
    public function render(?string $content = null): void
    {
        $content = $this->getRenderer()->render($this, $content);

        $this->_message = $content['message'];
        $this->_boundary = $content['boundary'];
        $this->_textMessage = $content['textMessage'];
        $this->_htmlMessage = $content['htmlMessage'];
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
    protected function getRenderer(): Renderer
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
    protected function setRenderer(Renderer $renderer): self
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
    protected function _logDelivery($contents): void
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
    ): Email {
        $class = __CLASS__;

        if (is_array($config) && !isset($config['transport'])) {
            $config['transport'] = 'default';
        }
        /* @var \Cake\Mailer\Email $instance */
        $instance = new $class($config);
        if ($to !== null) {
            $instance->setTo($to);
        }
        if ($subject !== null) {
            $instance->setSubject($subject);
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
     * Apply the config to an instance
     *
     * @param string|array $config Configuration options.
     * @return void
     * @throws \InvalidArgumentException When using a configuration that doesn't exist.
     */
    protected function _applyConfig($config): void
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
            'from', 'sender', 'to', 'replyTo', 'readReceipt', 'returnPath',
            'cc', 'bcc', 'messageId', 'domain', 'subject', 'attachments',
            'transport', 'emailFormat', 'emailPattern', 'charset', 'headerCharset',
        ];
        foreach ($simpleMethods as $method) {
            if (isset($config[$method])) {
                $this->{'set' . ucfirst($method)}($config[$method]);
            }
        }

        if (empty($this->headerCharset)) {
            $this->headerCharset = $this->charset;
        }
        if (isset($config['headers'])) {
            $this->setHeaders($config['headers']);
        }

        $viewBuilderMethods = [
            'template', 'layout', 'theme',
        ];
        foreach ($viewBuilderMethods as $method) {
            if (array_key_exists($method, $config)) {
                $this->getRenderer()->viewBuilder()->{'set' . ucfirst($method)}($config[$method]);
            }
        }

        if (array_key_exists('helpers', $config)) {
            $this->getRenderer()->viewBuilder()->setHelpers($config['helpers'], false);
        }
        if (array_key_exists('viewRender', $config)) {
            $this->getRenderer()->viewBuilder()->setClassName($config['viewRender']);
        }
        if (array_key_exists('viewVars', $config)) {
            $this->getRenderer()->viewBuilder()->setVars($config['viewVars']);
        }
    }

    /**
     * Reset all the internal variables to be able to send out a new email.
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->_to = [];
        $this->_from = [];
        $this->_sender = [];
        $this->_replyTo = [];
        $this->_readReceipt = [];
        $this->_returnPath = [];
        $this->_cc = [];
        $this->_bcc = [];
        $this->_messageId = true;
        $this->_subject = '';
        $this->_headers = [];
        $this->_textMessage = '';
        $this->_htmlMessage = '';
        $this->_message = [];
        $this->_emailFormat = static::MESSAGE_TEXT;
        $this->_transport = null;
        $this->_priority = null;
        $this->charset = 'utf-8';
        $this->headerCharset = null;
        $this->transferEncoding = null;
        $this->_attachments = [];
        $this->_profile = [];
        $this->_emailPattern = self::EMAIL_PATTERN;

        $this->getRenderer()->viewBuilder()
            ->setLayout('default')
            ->setTemplate('')
            ->setClassName('Cake\View\View')
            ->setTheme(null)
            ->setHelpers(['Html'], false)
            ->setVars([], false);

        return $this;
    }

    /**
     * Encode the specified string using the current charset
     *
     * @param string $text String to encode
     * @return string Encoded string
     */
    protected function _encode(string $text): string
    {
        $restore = mb_internal_encoding();
        mb_internal_encoding($this->_appCharset);
        if (empty($this->headerCharset)) {
            $this->headerCharset = $this->charset;
        }
        $return = mb_encode_mimeheader($text, $this->headerCharset, 'B');
        mb_internal_encoding($restore);

        return $return;
    }

    /**
     * Decode the specified string
     *
     * @param string $text String to decode
     * @return string Decoded string
     */
    protected function _decode(string $text): string
    {
        $restore = mb_internal_encoding();
        mb_internal_encoding($this->_appCharset);
        $return = mb_decode_mimeheader($text);
        mb_internal_encoding($restore);

        return $return;
    }

    /**
     * Read the file contents and return a base64 version of the file contents.
     *
     * @param string $path The absolute path to the file to read.
     * @return string File contents in base64 encoding
     */
    protected function _readFile(string $path): string
    {
        return chunk_split(base64_encode((string)file_get_contents($path)));
    }

    /**
     * Return the Content-Transfer Encoding value based
     * on the set transferEncoding or set charset.
     *
     * @return string
     */
    public function getContentTransferEncoding(): string
    {
        if ($this->transferEncoding) {
            return $this->transferEncoding;
        }

        $charset = strtoupper($this->charset);
        if (in_array($charset, $this->_charset8bit)) {
            return '8bit';
        }

        return '7bit';
    }

    /**
     * Return charset value for Content-Type.
     *
     * Checks fallback/compatibility types which include workarounds
     * for legacy japanese character sets.
     *
     * @return string
     */
    public function getContentTypeCharset(): string
    {
        $charset = strtoupper($this->charset);
        if (array_key_exists($charset, $this->_contentTypeCharset)) {
            return strtoupper($this->_contentTypeCharset[$charset]);
        }

        return strtoupper($this->charset);
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
        $properties = [
            '_to', '_from', '_sender', '_replyTo', '_cc', '_bcc', '_subject',
            '_returnPath', '_readReceipt', '_emailFormat', '_emailPattern', '_domain',
            '_attachments', '_messageId', '_headers', '_appCharset', 'charset', 'headerCharset',
        ];

        $array = ['viewConfig' => $this->getRenderer()->viewBuilder()->jsonSerialize()];

        foreach ($properties as $property) {
            $array[$property] = $this->{$property};
        }

        array_walk($array['_attachments'], function (&$item, $key) {
            if (!empty($item['file'])) {
                $item['data'] = $this->_readFile($item['file']);
                unset($item['file']);
            }
        });

        return array_filter($array, function ($i) {
            return !is_array($i) && strlen($i) || !empty($i);
        });
    }

    /**
     * Configures an email instance object from serialized config.
     *
     * @param array $config Email configuration array.
     * @return $this Configured email instance.
     */
    public function createFromArray(array $config): self
    {
        if (isset($config['viewConfig'])) {
            $this->getRenderer()->viewBuilder()->createFromArray($config['viewConfig']);
            unset($config['viewConfig']);
        }

        foreach ($config as $property => $value) {
            $this->{$property} = $value;
        }

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
        array_walk_recursive($array, function (&$item, $key) {
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
     * @return static Configured email instance.
     */
    public function unserialize($data): self
    {
        return $this->createFromArray(unserialize($data));
    }
}
