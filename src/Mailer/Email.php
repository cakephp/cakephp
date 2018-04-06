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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer;

use BadMethodCallException;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\StaticConfigTrait;
use Cake\Filesystem\File;
use Cake\Http\Client\FormDataPart;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use Cake\Utility\Text;
use Cake\View\ViewVarsTrait;
use Closure;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use PDO;
use RuntimeException;
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
    use ViewVarsTrait;

    /**
     * Line length - no should more - RFC 2822 - 2.1.1
     *
     * @var int
     */
    const LINE_LENGTH_SHOULD = 78;

    /**
     * Line length - no must more - RFC 2822 - 2.1.1
     *
     * @var int
     */
    const LINE_LENGTH_MUST = 998;

    /**
     * Type of message - HTML
     *
     * @var string
     */
    const MESSAGE_HTML = 'html';

    /**
     * Type of message - TEXT
     *
     * @var string
     */
    const MESSAGE_TEXT = 'text';

    /**
     * Holds the regex pattern for email validation
     *
     * @var string
     */
    const EMAIL_PATTERN = '/^((?:[\p{L}0-9.!#$%&\'*+\/=?^_`{|}~-]+)*@[\p{L}0-9-._]+)$/ui';

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
    protected $_domain;

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
    protected $_emailFormatAvailable = ['text', 'html', 'both'];

    /**
     * What format should the email be sent in
     *
     * @var string
     */
    protected $_emailFormat = 'text';

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
    public $charset = 'utf-8';

    /**
     * Charset the email header is sent in
     * If null, the $charset property will be used as default
     *
     * @var string|null
     */
    public $headerCharset;

    /**
     * The email transfer encoding used.
     * If null, the $charset property is used for determined the transfer encoding.
     *
     * @var string|null
     */
    protected $transferEncoding;

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
        'quoted-printable'
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
     * An array mapping url schemes to fully qualified Transport class names
     *
     * @var array
     */
    protected static $_dsnClassMap = [
        'debug' => 'Cake\Mailer\Transport\DebugTransport',
        'mail' => 'Cake\Mailer\Transport\MailTransport',
        'smtp' => 'Cake\Mailer\Transport\SmtpTransport',
    ];

    /**
     * Configuration profiles for transports.
     *
     * @var array
     */
    protected static $_transportConfig = [];

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
        'ISO-2022-JP-MS' => 'ISO-2022-JP'
    ];

    /**
     * Regex for email validation
     *
     * If null, filter_var() will be used. Use the emailPattern() method
     * to set a custom pattern.'
     *
     * @var string
     */
    protected $_emailPattern = self::EMAIL_PATTERN;

    /**
     * Constructor
     *
     * @param array|string|null $config Array of configs, or string to load configs from email.php
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

        $this->viewBuilder()
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
     * Clone ViewBuilder instance when email object is cloned.
     *
     * @return void
     */
    public function __clone()
    {
        $this->_viewBuilder = clone $this->viewBuilder();
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
    public function setFrom($email, $name = null)
    {
        return $this->_setEmailSingle('_from', $email, $name, 'From requires only 1 email address.');
    }

    /**
     * Gets "from" address.
     *
     * @return array
     */
    public function getFrom()
    {
        return $this->_from;
    }

    /**
     * From
     *
     * @deprecated 3.4.0 Use setFrom()/getFrom() instead.
     * @param string|array|null $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return array|$this
     * @throws \InvalidArgumentException
     */
    public function from($email = null, $name = null)
    {
        if ($email === null) {
            return $this->getFrom();
        }

        return $this->setFrom($email, $name);
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
    public function setSender($email, $name = null)
    {
        return $this->_setEmailSingle('_sender', $email, $name, 'Sender requires only 1 email address.');
    }

    /**
     * Gets "sender" address.
     *
     * @return array
     */
    public function getSender()
    {
        return $this->_sender;
    }

    /**
     * Sender
     *
     * @deprecated 3.4.0 Use setSender()/getSender() instead.
     * @param string|array|null $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return array|$this
     * @throws \InvalidArgumentException
     */
    public function sender($email = null, $name = null)
    {
        if ($email === null) {
            return $this->getSender();
        }

        return $this->setSender($email, $name);
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
    public function setReplyTo($email, $name = null)
    {
        return $this->_setEmailSingle('_replyTo', $email, $name, 'Reply-To requires only 1 email address.');
    }

    /**
     * Gets "Reply-To" address.
     *
     * @return array
     */
    public function getReplyTo()
    {
        return $this->_replyTo;
    }

    /**
     * Reply-To
     *
     * @deprecated 3.4.0 Use setReplyTo()/getReplyTo() instead.
     * @param string|array|null $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return array|$this
     * @throws \InvalidArgumentException
     */
    public function replyTo($email = null, $name = null)
    {
        if ($email === null) {
            return $this->getReplyTo();
        }

        return $this->setReplyTo($email, $name);
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
    public function setReadReceipt($email, $name = null)
    {
        return $this->_setEmailSingle('_readReceipt', $email, $name, 'Disposition-Notification-To requires only 1 email address.');
    }

    /**
     * Gets Read Receipt (Disposition-Notification-To header).
     *
     * @return array
     */
    public function getReadReceipt()
    {
        return $this->_readReceipt;
    }

    /**
     * Read Receipt (Disposition-Notification-To header)
     *
     * @deprecated 3.4.0 Use setReadReceipt()/getReadReceipt() instead.
     * @param string|array|null $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return array|$this
     * @throws \InvalidArgumentException
     */
    public function readReceipt($email = null, $name = null)
    {
        if ($email === null) {
            return $this->getReadReceipt();
        }

        return $this->setReadReceipt($email, $name);
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
    public function setReturnPath($email, $name = null)
    {
        return $this->_setEmailSingle('_returnPath', $email, $name, 'Return-Path requires only 1 email address.');
    }

    /**
     * Gets return path.
     *
     * @return array
     */
    public function getReturnPath()
    {
        return $this->_returnPath;
    }

    /**
     * Return Path
     *
     * @deprecated 3.4.0 Use setReturnPath()/getReturnPath() instead.
     * @param string|array|null $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return array|$this
     * @throws \InvalidArgumentException
     */
    public function returnPath($email = null, $name = null)
    {
        if ($email === null) {
            return $this->getReturnPath();
        }

        return $this->setReturnPath($email, $name);
    }

    /**
     * Sets "to" address.
     *
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     */
    public function setTo($email, $name = null)
    {
        return $this->_setEmail('_to', $email, $name);
    }

    /**
     * Gets "to" address
     *
     * @return array
     */
    public function getTo()
    {
        return $this->_to;
    }

    /**
     * To
     *
     * @deprecated 3.4.0 Use setTo()/getTo() instead.
     * @param string|array|null $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return array|$this
     */
    public function to($email = null, $name = null)
    {
        if ($email === null) {
            return $this->getTo();
        }

        return $this->setTo($email, $name);
    }

    /**
     * Add To
     *
     * @param string|array $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     */
    public function addTo($email, $name = null)
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
    public function setCc($email, $name = null)
    {
        return $this->_setEmail('_cc', $email, $name);
    }

    /**
     * Gets "cc" address.
     *
     * @return array
     */
    public function getCc()
    {
        return $this->_cc;
    }

    /**
     * Cc
     *
     * @deprecated 3.4.0 Use setCc()/getCc() instead.
     * @param string|array|null $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return array|$this
     */
    public function cc($email = null, $name = null)
    {
        if ($email === null) {
            return $this->getCc();
        }

        return $this->setCc($email, $name);
    }

    /**
     * Add Cc
     *
     * @param string|array $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     */
    public function addCc($email, $name = null)
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
    public function setBcc($email, $name = null)
    {
        return $this->_setEmail('_bcc', $email, $name);
    }

    /**
     * Gets "bcc" address.
     *
     * @return array
     */
    public function getBcc()
    {
        return $this->_bcc;
    }

    /**
     * Bcc
     *
     * @deprecated 3.4.0 Use setBcc()/getBcc() instead.
     * @param string|array|null $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return array|$this
     */
    public function bcc($email = null, $name = null)
    {
        if ($email === null) {
            return $this->getBcc();
        }

        return $this->setBcc($email, $name);
    }

    /**
     * Add Bcc
     *
     * @param string|array $email Null to get, String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string|null $name Name
     * @return $this
     */
    public function addBcc($email, $name = null)
    {
        return $this->_addEmail('_bcc', $email, $name);
    }

    /**
     * Charset setter.
     *
     * @param string|null $charset Character set.
     * @return $this
     */
    public function setCharset($charset)
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
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Charset setter/getter
     *
     * @deprecated 3.4.0 Use setCharset()/getCharset() instead.
     * @param string|null $charset Character set.
     * @return string Charset
     */
    public function charset($charset = null)
    {
        if ($charset === null) {
            return $this->getCharset();
        }
        $this->setCharset($charset);

        return $this->charset;
    }

    /**
     * HeaderCharset setter.
     *
     * @param string|null $charset Character set.
     * @return $this
     */
    public function setHeaderCharset($charset)
    {
        $this->headerCharset = $charset;

        return $this;
    }

    /**
     * HeaderCharset getter.
     *
     * @return string Charset
     */
    public function getHeaderCharset()
    {
        return $this->headerCharset;
    }

    /**
     * HeaderCharset setter/getter
     *
     * @deprecated 3.4.0 Use setHeaderCharset()/getHeaderCharset() instead.
     * @param string|null $charset Character set.
     * @return string Charset
     */
    public function headerCharset($charset = null)
    {
        if ($charset === null) {
            return $this->getHeaderCharset();
        }

        $this->setHeaderCharset($charset);

        return $this->headerCharset;
    }

    /**
     * TransferEncoding setter.
     *
     * @param string|null $encoding Encoding set.
     * @return $this
     */
    public function setTransferEncoding($encoding)
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
    public function getTransferEncoding()
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
    public function setEmailPattern($regex)
    {
        $this->_emailPattern = $regex;

        return $this;
    }

    /**
     * EmailPattern setter/getter
     *
     * @return string
     */
    public function getEmailPattern()
    {
        return $this->_emailPattern;
    }

    /**
     * EmailPattern setter/getter
     *
     * @deprecated 3.4.0 Use setEmailPattern()/getEmailPattern() instead.
     * @param string|bool|null $regex The pattern to use for email address validation,
     *   null to unset the pattern and make use of filter_var() instead, false or
     *   nothing to return the current value
     * @return string|$this
     */
    public function emailPattern($regex = false)
    {
        if ($regex === false) {
            return $this->getEmailPattern();
        }

        return $this->setEmailPattern($regex);
    }

    /**
     * Set email
     *
     * @param string $varName Property name
     * @param string|array $email String with email,
     *   Array with email as key, name as value or email as value (without name)
     * @param string $name Name
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function _setEmail($varName, $email, $name)
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
    protected function _validateEmail($email, $context)
    {
        if ($this->_emailPattern === null) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return;
            }
        } elseif (preg_match($this->_emailPattern, $email)) {
            return;
        }

        $context = ltrim($context, '_');
        if ($email == '') {
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
     * @param string $name Name
     * @param string $throwMessage Exception message
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function _setEmailSingle($varName, $email, $name, $throwMessage)
    {
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
     * @param string $name Name
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function _addEmail($varName, $email, $name)
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
    public function setSubject($subject)
    {
        $this->_subject = $this->_encode((string)$subject);

        return $this;
    }

    /**
     * Gets subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    /**
     * Get/Set Subject.
     *
     * @deprecated 3.4.0 Use setSubject()/getSubject() instead.
     * @param string|null $subject Subject string.
     * @return string|$this
     */
    public function subject($subject = null)
    {
        if ($subject === null) {
            return $this->getSubject();
        }

        return $this->setSubject($subject);
    }

    /**
     * Get original subject without encoding
     *
     * @return string Original subject
     */
    public function getOriginalSubject()
    {
        return $this->_decode($this->_subject);
    }

    /**
     * Sets headers for the message
     *
     * @param array $headers Associative array containing headers to be set.
     * @return $this
     */
    public function setHeaders(array $headers)
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
    public function addHeaders(array $headers)
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
    public function getHeaders(array $include = [])
    {
        if ($include == array_values($include)) {
            $include = array_fill_keys($include, true);
        }
        $defaults = array_fill_keys(
            [
                'from', 'sender', 'replyTo', 'readReceipt', 'returnPath',
                'to', 'cc', 'bcc', 'subject'],
            false
        );
        $include += $defaults;

        $headers = [];
        $relation = [
            'from' => 'From',
            'replyTo' => 'Reply-To',
            'readReceipt' => 'Disposition-Notification-To',
            'returnPath' => 'Return-Path'
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
                $headers['Message-ID'] = '<' . str_replace('-', '', Text::uuid()) . '@' . $this->_domain . '>';
            } else {
                $headers['Message-ID'] = $this->_messageId;
            }
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
        } elseif ($this->_emailFormat === 'both') {
            $headers['Content-Type'] = 'multipart/alternative; boundary="' . $this->_boundary . '"';
        } elseif ($this->_emailFormat === 'text') {
            $headers['Content-Type'] = 'text/plain; charset=' . $this->_getContentTypeCharset();
        } elseif ($this->_emailFormat === 'html') {
            $headers['Content-Type'] = 'text/html; charset=' . $this->_getContentTypeCharset();
        }
        $headers['Content-Transfer-Encoding'] = $this->_getContentTransferEncoding();

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
    protected function _formatAddress($address)
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
     * Sets template.
     *
     * @param string|null $template Template name or null to not use.
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->viewBuilder()->setTemplate($template ?: '');

        return $this;
    }

    /**
     * Gets template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->viewBuilder()->getTemplate();
    }

    /**
     * Sets layout.
     *
     * @param string|null $layout Layout name or null to not use
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->viewBuilder()->setLayout($layout ?: false);

        return $this;
    }

    /**
     * Gets layout.
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->viewBuilder()->getLayout();
    }

    /**
     * Template and layout
     *
     * @deprecated 3.4.0 Use setTemplate()/getTemplate() and setLayout()/getLayout() instead.
     * @param bool|string $template Template name or null to not use
     * @param bool|string $layout Layout name or null to not use
     * @return array|$this
     */
    public function template($template = false, $layout = false)
    {
        if ($template === false) {
            return [
                'template' => $this->getTemplate(),
                'layout' => $this->getLayout()
            ];
        }
        $this->setTemplate($template);
        if ($layout !== false) {
            $this->setLayout($layout);
        }

        return $this;
    }

    /**
     * Sets view class for render.
     *
     * @param string $viewClass View class name.
     * @return $this
     */
    public function setViewRenderer($viewClass)
    {
        $this->viewBuilder()->setClassName($viewClass);

        return $this;
    }

    /**
     * Gets view class for render.
     *
     * @return string
     */
    public function getViewRenderer()
    {
        return $this->viewBuilder()->getClassName();
    }

    /**
     * View class for render
     *
     * @deprecated 3.4.0 Use setViewRenderer()/getViewRenderer() instead.
     * @param string|null $viewClass View class name.
     * @return string|$this
     */
    public function viewRender($viewClass = null)
    {
        if ($viewClass === null) {
            return $this->getViewRenderer();
        }
        $this->setViewRenderer($viewClass);

        return $this;
    }

    /**
     * Sets variables to be set on render.
     *
     * @param array $viewVars Variables to set for view.
     * @return $this
     */
    public function setViewVars($viewVars)
    {
        $this->set((array)$viewVars);

        return $this;
    }

    /**
     * Gets variables to be set on render.
     *
     * @return array
     */
    public function getViewVars()
    {
        return $this->viewVars;
    }

    /**
     * Variables to be set on render
     *
     * @deprecated 3.4.0 Use setViewVars()/getViewVars() instead.
     * @param array|null $viewVars Variables to set for view.
     * @return array|$this
     */
    public function viewVars($viewVars = null)
    {
        if ($viewVars === null) {
            return $this->getViewVars();
        }

        return $this->setViewVars($viewVars);
    }

    /**
     * Sets theme to use when rendering.
     *
     * @param string $theme Theme name.
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->viewBuilder()->setTheme($theme);

        return $this;
    }

    /**
     * Gets theme to use when rendering.
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->viewBuilder()->getTheme();
    }

    /**
     * Theme to use when rendering
     *
     * @deprecated 3.4.0 Use setTheme()/getTheme() instead.
     * @param string|null $theme Theme name.
     * @return string|$this
     */
    public function theme($theme = null)
    {
        if ($theme === null) {
            return $this->getTheme();
        }

        return $this->setTheme($theme);
    }

    /**
     * Sets helpers to be used when rendering.
     *
     * @param array $helpers Helpers list.
     * @return $this
     */
    public function setHelpers(array $helpers)
    {
        $this->viewBuilder()->setHelpers($helpers, false);

        return $this;
    }

    /**
     * Gets helpers to be used when rendering.
     *
     * @return array
     */
    public function getHelpers()
    {
        return $this->viewBuilder()->getHelpers();
    }

    /**
     * Helpers to be used in render
     *
     * @deprecated 3.4.0 Use setHelpers()/getHelpers() instead.
     * @param array|null $helpers Helpers list.
     * @return array|$this
     */
    public function helpers($helpers = null)
    {
        if ($helpers === null) {
            return $this->getHelpers();
        }

        return $this->setHelpers((array)$helpers);
    }

    /**
     * Sets email format.
     *
     * @param string $format Formatting string.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setEmailFormat($format)
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
    public function getEmailFormat()
    {
        return $this->_emailFormat;
    }

    /**
     * Email format
     *
     * @deprecated 3.4.0 Use setEmailFormat()/getEmailFormat() instead.
     * @param string|null $format Formatting string.
     * @return string|$this
     * @throws \InvalidArgumentException
     */
    public function emailFormat($format = null)
    {
        if ($format === null) {
            return $this->getEmailFormat();
        }

        return $this->setEmailFormat($format);
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
            $transport = $this->_constructTransport($name);
        } elseif (is_object($name)) {
            $transport = $name;
        } else {
            throw new InvalidArgumentException(
                sprintf('The value passed for the "$name" argument must be either a string, or an object, %s given.', gettype($name))
            );
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
     * @return \Cake\Mailer\AbstractTransport
     */
    public function getTransport()
    {
        return $this->_transport;
    }

    /**
     * Get/set the transport.
     *
     * When setting the transport you can either use the name
     * of a configured transport or supply a constructed transport.
     *
     * @deprecated 3.4.0 Use setTransport()/getTransport() instead.
     * @param string|\Cake\Mailer\AbstractTransport|null $name Either the name of a configured
     *   transport, or a transport instance.
     * @return \Cake\Mailer\AbstractTransport|$this
     * @throws \LogicException When the chosen transport lacks a send method.
     * @throws \InvalidArgumentException When $name is neither a string nor an object.
     */
    public function transport($name = null)
    {
        if ($name === null) {
            return $this->getTransport();
        }

        return $this->setTransport($name);
    }

    /**
     * Build a transport instance from configuration data.
     *
     * @param string $name The transport configuration name to build.
     * @return \Cake\Mailer\AbstractTransport
     * @throws \InvalidArgumentException When transport configuration is missing or invalid.
     */
    protected function _constructTransport($name)
    {
        if (!isset(static::$_transportConfig[$name])) {
            throw new InvalidArgumentException(sprintf('Transport config "%s" is missing.', $name));
        }

        if (!isset(static::$_transportConfig[$name]['className'])) {
            throw new InvalidArgumentException(
                sprintf('Transport config "%s" is invalid, the required `className` option is missing', $name)
            );
        }

        $config = static::$_transportConfig[$name];

        if (is_object($config['className'])) {
            if (!$config['className'] instanceof AbstractTransport) {
                throw new InvalidArgumentException(sprintf(
                    'Transport object must be of type "AbstractTransport". Found invalid type: "%s".',
                    get_class($config['className'])
                ));
            }

            return $config['className'];
        }

        $className = App::className($config['className'], 'Mailer/Transport', 'Transport');
        if (!$className) {
            $className = App::className($config['className'], 'Network/Email', 'Transport');
            if ($className) {
                trigger_error(
                    'Transports in "Network/Email" are deprecated, use "Mailer/Transport" instead.',
                    E_USER_DEPRECATED
                );
            }
        }

        if (!$className) {
            throw new InvalidArgumentException(sprintf('Transport class "%s" not found.', $config['className']));
        }
        if (!method_exists($className, 'send')) {
            throw new InvalidArgumentException(sprintf('The "%s" does not have a send() method.', $className));
        }

        unset($config['className']);

        return new $className($config);
    }

    /**
     * Sets message ID.
     *
     * @param bool|string $message True to generate a new Message-ID, False to ignore (not send in email), String to set as Message-ID.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setMessageId($message)
    {
        if (is_bool($message)) {
            $this->_messageId = $message;
        } else {
            if (!preg_match('/^\<.+@.+\>$/', $message)) {
                throw new InvalidArgumentException('Invalid format to Message-ID. The text should be something like "<uuid@server.com>"');
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
     * Message-ID
     *
     * @deprecated 3.4.0 Use setMessageId()/getMessageId() instead.
     * @param bool|string|null $message True to generate a new Message-ID, False to ignore (not send in email), String to set as Message-ID
     * @return bool|string|$this
     * @throws \InvalidArgumentException
     */
    public function messageId($message = null)
    {
        if ($message === null) {
            return $this->getMessageId();
        }

        return $this->setMessageId($message);
    }

    /**
     * Sets domain.
     *
     * Domain as top level (the part after @).
     *
     * @param string $domain Manually set the domain for CLI mailing.
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->_domain = $domain;

        return $this;
    }

    /**
     * Gets domain.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->_domain;
    }

    /**
     * Domain as top level (the part after @)
     *
     * @deprecated 3.4.0 Use setDomain()/getDomain() instead.
     * @param string|null $domain Manually set the domain for CLI mailing
     * @return string|$this
     */
    public function domain($domain = null)
    {
        if ($domain === null) {
            return $this->getDomain();
        }

        return $this->setDomain($domain);
    }

    /**
     * Add attachments to the email message
     *
     * Attachments can be defined in a few forms depending on how much control you need:
     *
     * Attach a single file:
     *
     * ```
     * $email->attachments('path/to/file');
     * ```
     *
     * Attach a file with a different filename:
     *
     * ```
     * $email->attachments(['custom_name.txt' => 'path/to/file.txt']);
     * ```
     *
     * Attach a file and specify additional properties:
     *
     * ```
     * $email->attachments(['custom_name.png' => [
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
     * $email->attachments(['custom_name.png' => [
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
     * @param string|array $attachments String with the filename or array with filenames
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setAttachments($attachments)
    {
        $attach = [];
        foreach ((array)$attachments as $name => $fileInfo) {
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
            if (!isset($fileInfo['mimetype']) && function_exists('mime_content_type')) {
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
    public function getAttachments()
    {
        return $this->_attachments;
    }

    /**
     * Add attachments to the email message
     *
     * Attachments can be defined in a few forms depending on how much control you need:
     *
     * Attach a single file:
     *
     * ```
     * $email->attachments('path/to/file');
     * ```
     *
     * Attach a file with a different filename:
     *
     * ```
     * $email->attachments(['custom_name.txt' => 'path/to/file.txt']);
     * ```
     *
     * Attach a file and specify additional properties:
     *
     * ```
     * $email->attachments(['custom_name.png' => [
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
     * $email->attachments(['custom_name.png' => [
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
     * @deprecated 3.4.0 Use setAttachments()/getAttachments() instead.
     * @param string|array|null $attachments String with the filename or array with filenames
     * @return array|$this Either the array of attachments when getting or $this when setting.
     * @throws \InvalidArgumentException
     */
    public function attachments($attachments = null)
    {
        if ($attachments === null) {
            return $this->getAttachments();
        }

        return $this->setAttachments($attachments);
    }

    /**
     * Add attachments
     *
     * @param string|array $attachments String with the filename or array with filenames
     * @return $this
     * @throws \InvalidArgumentException
     * @see \Cake\Mailer\Email::attachments()
     */
    public function addAttachments($attachments)
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
    public function message($type = null)
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
    public function setPriority($priority)
    {
        $this->_priority = $priority;

        return $this;
    }

    /**
     * Gets priority.
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->_priority;
    }

    /**
     * Sets transport configuration.
     *
     * Use this method to define transports to use in delivery profiles.
     * Once defined you cannot edit the configurations, and must use
     * Email::dropTransport() to flush the configuration first.
     *
     * When using an array of configuration data a new transport
     * will be constructed for each message sent. When using a Closure, the
     * closure will be evaluated for each message.
     *
     * The `className` is used to define the class to use for a transport.
     * It can either be a short name, or a fully qualified class name
     *
     * @param string|array $key The configuration name to write. Or
     *   an array of multiple transports to set.
     * @param array|\Cake\Mailer\AbstractTransport|null $config Either an array of configuration
     *   data, or a transport instance. Null when using key as array.
     * @return void
     * @throws \BadMethodCallException When modifying an existing configuration.
     */
    public static function setConfigTransport($key, $config = null)
    {
        if (is_array($key)) {
            foreach ($key as $name => $settings) {
                static::setConfigTransport($name, $settings);
            }

            return;
        }

        if (isset(static::$_transportConfig[$key])) {
            throw new BadMethodCallException(sprintf('Cannot modify an existing config "%s"', $key));
        }

        if (is_object($config)) {
            $config = ['className' => $config];
        }

        if (isset($config['url'])) {
            $parsed = static::parseDsn($config['url']);
            unset($config['url']);
            $config = $parsed + $config;
        }

        static::$_transportConfig[$key] = $config;
    }

    /**
     * Gets current transport configuration.
     *
     * @param string $key The configuration name to read.
     * @return array|null Transport config.
     */
    public static function getConfigTransport($key)
    {
        return isset(static::$_transportConfig[$key]) ? static::$_transportConfig[$key] : null;
    }

    /**
     * Add or read transport configuration.
     *
     * Use this method to define transports to use in delivery profiles.
     * Once defined you cannot edit the configurations, and must use
     * Email::dropTransport() to flush the configuration first.
     *
     * When using an array of configuration data a new transport
     * will be constructed for each message sent. When using a Closure, the
     * closure will be evaluated for each message.
     *
     * The `className` is used to define the class to use for a transport.
     * It can either be a short name, or a fully qualified classname
     *
     * @deprecated 3.4.0 Use setConfigTransport()/getConfigTransport() instead.
     * @param string|array $key The configuration name to read/write. Or
     *   an array of multiple transports to set.
     * @param array|\Cake\Mailer\AbstractTransport|null $config Either an array of configuration
     *   data, or a transport instance.
     * @return array|null Either null when setting or an array of data when reading.
     * @throws \BadMethodCallException When modifying an existing configuration.
     */
    public static function configTransport($key, $config = null)
    {
        if ($config === null && is_string($key)) {
            return static::getConfigTransport($key);
        }
        if ($config === null && is_array($key)) {
            static::setConfigTransport($key);

            return null;
        }

        static::setConfigTransport($key, $config);
    }

    /**
     * Returns an array containing the named transport configurations
     *
     * @return array Array of configurations.
     */
    public static function configuredTransport()
    {
        return array_keys(static::$_transportConfig);
    }

    /**
     * Delete transport configuration.
     *
     * @param string $key The transport name to remove.
     * @return void
     */
    public static function dropTransport($key)
    {
        unset(static::$_transportConfig[$key]);
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
        if (!is_array($config)) {
            $config = (string)$config;
        }
        $this->_applyConfig($config);

        return $this;
    }

    /**
     * Gets the configuration profile to use for this instance.
     *
     * @return string|array
     */
    public function getProfile()
    {
        return $this->_profile;
    }

    /**
     * Get/Set the configuration profile to use for this instance.
     *
     * @deprecated 3.4.0 Use setProfile()/getProfile() instead.
     * @param null|string|array $config String with configuration name, or
     *    an array with config or null to return current config.
     * @return string|array|$this
     */
    public function profile($config = null)
    {
        if ($config === null) {
            return $this->getProfile();
        }

        return $this->setProfile($config);
    }

    /**
     * Send an email using the specified content, template and layout
     *
     * @param string|array|null $content String with message or array with messages
     * @return array
     * @throws \BadMethodCallException
     */
    public function send($content = null)
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

        $this->_message = $this->_render($this->_wrap($content));

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
     * Log the email message delivery.
     *
     * @param array $contents The content with 'headers' and 'message' keys.
     * @return void
     */
    protected function _logDelivery($contents)
    {
        if (empty($this->_profile['log'])) {
            return;
        }
        $config = [
            'level' => 'debug',
            'scope' => 'email'
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
    protected function flatten($value)
    {
        return is_array($value) ? implode(';', $value) : (string)$value;
    }

    /**
     * Static method to fast create an instance of \Cake\Mailer\Email
     *
     * @param string|array|null $to Address to send (see Cake\Mailer\Email::to()). If null, will try to use 'to' from transport config
     * @param string|null $subject String of subject or null to use 'subject' from transport config
     * @param string|array|null $message String with message or array with variables to be used in render
     * @param string|array $transportConfig String to use config from EmailConfig or array with configs
     * @param bool $send Send the email or just return the instance pre-configured
     * @return static Instance of Cake\Mailer\Email
     * @throws \InvalidArgumentException
     */
    public static function deliver($to = null, $subject = null, $message = null, $transportConfig = 'default', $send = true)
    {
        $class = __CLASS__;

        if (is_array($transportConfig) && !isset($transportConfig['transport'])) {
            $transportConfig['transport'] = 'default';
        }
        /* @var \Cake\Mailer\Email $instance */
        $instance = new $class($transportConfig);
        if ($to !== null) {
            $instance->setTo($to);
        }
        if ($subject !== null) {
            $instance->setSubject($subject);
        }
        if (is_array($message)) {
            $instance->setViewVars($message);
            $message = null;
        } elseif ($message === null && array_key_exists('message', $config = $instance->getProfile())) {
            $message = $config['message'];
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
    protected function _applyConfig($config)
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
            'transport', 'emailFormat', 'emailPattern', 'charset', 'headerCharset'
        ];
        foreach ($simpleMethods as $method) {
            if (isset($config[$method])) {
                $this->$method($config[$method]);
            }
        }

        if (empty($this->headerCharset)) {
            $this->headerCharset = $this->charset;
        }
        if (isset($config['headers'])) {
            $this->setHeaders($config['headers']);
        }

        $viewBuilderMethods = [
            'template', 'layout', 'theme'
        ];
        foreach ($viewBuilderMethods as $method) {
            if (array_key_exists($method, $config)) {
                $this->viewBuilder()->$method($config[$method]);
            }
        }

        if (array_key_exists('helpers', $config)) {
            $this->viewBuilder()->setHelpers($config['helpers'], false);
        }
        if (array_key_exists('viewRender', $config)) {
            $this->viewBuilder()->setClassName($config['viewRender']);
        }
        if (array_key_exists('viewVars', $config)) {
            $this->set($config['viewVars']);
        }
    }

    /**
     * Reset all the internal variables to be able to send out a new email.
     *
     * @return $this
     */
    public function reset()
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
        $this->_emailFormat = 'text';
        $this->_transport = null;
        $this->_priority = null;
        $this->charset = 'utf-8';
        $this->headerCharset = null;
        $this->transferEncoding = null;
        $this->_attachments = [];
        $this->_profile = [];
        $this->_emailPattern = self::EMAIL_PATTERN;

        $this->viewBuilder()->setLayout('default');
        $this->viewBuilder()->setTemplate('');
        $this->viewBuilder()->setClassName('Cake\View\View');
        $this->viewVars = [];
        $this->viewBuilder()->setTheme(false);
        $this->viewBuilder()->setHelpers(['Html'], false);

        return $this;
    }

    /**
     * Encode the specified string using the current charset
     *
     * @param string $text String to encode
     * @return string Encoded string
     */
    protected function _encode($text)
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
    protected function _decode($text)
    {
        $restore = mb_internal_encoding();
        mb_internal_encoding($this->_appCharset);
        $return = mb_decode_mimeheader($text);
        mb_internal_encoding($restore);

        return $return;
    }

    /**
     * Translates a string for one charset to another if the App.encoding value
     * differs and the mb_convert_encoding function exists
     *
     * @param string $text The text to be converted
     * @param string $charset the target encoding
     * @return string
     */
    protected function _encodeString($text, $charset)
    {
        if ($this->_appCharset === $charset) {
            return $text;
        }

        return mb_convert_encoding($text, $charset, $this->_appCharset);
    }

    /**
     * Wrap the message to follow the RFC 2822 - 2.1.1
     *
     * @param string $message Message to wrap
     * @param int $wrapLength The line length
     * @return array Wrapped message
     */
    protected function _wrap($message, $wrapLength = Email::LINE_LENGTH_MUST)
    {
        if (strlen($message) === 0) {
            return [''];
        }
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $lines = explode("\n", $message);
        $formatted = [];
        $cut = ($wrapLength == Email::LINE_LENGTH_MUST);

        foreach ($lines as $line) {
            if (empty($line) && $line !== '0') {
                $formatted[] = '';
                continue;
            }
            if (strlen($line) < $wrapLength) {
                $formatted[] = $line;
                continue;
            }
            if (!preg_match('/<[a-z]+.*>/i', $line)) {
                $formatted = array_merge(
                    $formatted,
                    explode("\n", wordwrap($line, $wrapLength, "\n", $cut))
                );
                continue;
            }

            $tagOpen = false;
            $tmpLine = $tag = '';
            $tmpLineLength = 0;
            for ($i = 0, $count = strlen($line); $i < $count; $i++) {
                $char = $line[$i];
                if ($tagOpen) {
                    $tag .= $char;
                    if ($char === '>') {
                        $tagLength = strlen($tag);
                        if ($tagLength + $tmpLineLength < $wrapLength) {
                            $tmpLine .= $tag;
                            $tmpLineLength += $tagLength;
                        } else {
                            if ($tmpLineLength > 0) {
                                $formatted = array_merge(
                                    $formatted,
                                    explode("\n", wordwrap(trim($tmpLine), $wrapLength, "\n", $cut))
                                );
                                $tmpLine = '';
                                $tmpLineLength = 0;
                            }
                            if ($tagLength > $wrapLength) {
                                $formatted[] = $tag;
                            } else {
                                $tmpLine = $tag;
                                $tmpLineLength = $tagLength;
                            }
                        }
                        $tag = '';
                        $tagOpen = false;
                    }
                    continue;
                }
                if ($char === '<') {
                    $tagOpen = true;
                    $tag = '<';
                    continue;
                }
                if ($char === ' ' && $tmpLineLength >= $wrapLength) {
                    $formatted[] = $tmpLine;
                    $tmpLineLength = 0;
                    continue;
                }
                $tmpLine .= $char;
                $tmpLineLength++;
                if ($tmpLineLength === $wrapLength) {
                    $nextChar = $line[$i + 1];
                    if ($nextChar === ' ' || $nextChar === '<') {
                        $formatted[] = trim($tmpLine);
                        $tmpLine = '';
                        $tmpLineLength = 0;
                        if ($nextChar === ' ') {
                            $i++;
                        }
                    } else {
                        $lastSpace = strrpos($tmpLine, ' ');
                        if ($lastSpace === false) {
                            continue;
                        }
                        $formatted[] = trim(substr($tmpLine, 0, $lastSpace));
                        $tmpLine = substr($tmpLine, $lastSpace + 1);

                        $tmpLineLength = strlen($tmpLine);
                    }
                }
            }
            if (!empty($tmpLine)) {
                $formatted[] = $tmpLine;
            }
        }
        $formatted[] = '';

        return $formatted;
    }

    /**
     * Create unique boundary identifier
     *
     * @return void
     */
    protected function _createBoundary()
    {
        if ($this->_attachments || $this->_emailFormat === 'both') {
            $this->_boundary = md5(Security::randomBytes(16));
        }
    }

    /**
     * Attach non-embedded files by adding file contents inside boundaries.
     *
     * @param string|null $boundary Boundary to use. If null, will default to $this->_boundary
     * @return array An array of lines to add to the message
     */
    protected function _attachFiles($boundary = null)
    {
        if ($boundary === null) {
            $boundary = $this->_boundary;
        }

        $msg = [];
        foreach ($this->_attachments as $filename => $fileInfo) {
            if (!empty($fileInfo['contentId'])) {
                continue;
            }
            $data = isset($fileInfo['data']) ? $fileInfo['data'] : $this->_readFile($fileInfo['file']);
            $hasDisposition = (
                !isset($fileInfo['contentDisposition']) ||
                $fileInfo['contentDisposition']
            );
            $part = new FormDataPart(false, $data, false);

            if ($hasDisposition) {
                $part->disposition('attachment');
                $part->filename($filename);
            }
            $part->transferEncoding('base64');
            $part->type($fileInfo['mimetype']);

            $msg[] = '--' . $boundary;
            $msg[] = (string)$part;
            $msg[] = '';
        }

        return $msg;
    }

    /**
     * Read the file contents and return a base64 version of the file contents.
     *
     * @param string $path The absolute path to the file to read.
     * @return string File contents in base64 encoding
     */
    protected function _readFile($path)
    {
        $File = new File($path);

        return chunk_split(base64_encode($File->read()));
    }

    /**
     * Attach inline/embedded files to the message.
     *
     * @param string|null $boundary Boundary to use. If null, will default to $this->_boundary
     * @return array An array of lines to add to the message
     */
    protected function _attachInlineFiles($boundary = null)
    {
        if ($boundary === null) {
            $boundary = $this->_boundary;
        }

        $msg = [];
        foreach ($this->_attachments as $filename => $fileInfo) {
            if (empty($fileInfo['contentId'])) {
                continue;
            }
            $data = isset($fileInfo['data']) ? $fileInfo['data'] : $this->_readFile($fileInfo['file']);

            $msg[] = '--' . $boundary;
            $part = new FormDataPart(false, $data, 'inline');
            $part->type($fileInfo['mimetype']);
            $part->transferEncoding('base64');
            $part->contentId($fileInfo['contentId']);
            $part->filename($filename);
            $msg[] = (string)$part;
            $msg[] = '';
        }

        return $msg;
    }

    /**
     * Render the body of the email.
     *
     * @param array $content Content to render
     * @return array Email body ready to be sent
     */
    protected function _render($content)
    {
        $this->_textMessage = $this->_htmlMessage = '';

        $content = implode("\n", $content);
        $rendered = $this->_renderTemplates($content);

        $this->_createBoundary();
        $msg = [];

        $contentIds = array_filter((array)Hash::extract($this->_attachments, '{s}.contentId'));
        $hasInlineAttachments = count($contentIds) > 0;
        $hasAttachments = !empty($this->_attachments);
        $hasMultipleTypes = count($rendered) > 1;
        $multiPart = ($hasAttachments || $hasMultipleTypes);

        $boundary = $relBoundary = $textBoundary = $this->_boundary;

        if ($hasInlineAttachments) {
            $msg[] = '--' . $boundary;
            $msg[] = 'Content-Type: multipart/related; boundary="rel-' . $boundary . '"';
            $msg[] = '';
            $relBoundary = $textBoundary = 'rel-' . $boundary;
        }

        if ($hasMultipleTypes && $hasAttachments) {
            $msg[] = '--' . $relBoundary;
            $msg[] = 'Content-Type: multipart/alternative; boundary="alt-' . $boundary . '"';
            $msg[] = '';
            $textBoundary = 'alt-' . $boundary;
        }

        if (isset($rendered['text'])) {
            if ($multiPart) {
                $msg[] = '--' . $textBoundary;
                $msg[] = 'Content-Type: text/plain; charset=' . $this->_getContentTypeCharset();
                $msg[] = 'Content-Transfer-Encoding: ' . $this->_getContentTransferEncoding();
                $msg[] = '';
            }
            $this->_textMessage = $rendered['text'];
            $content = explode("\n", $this->_textMessage);
            $msg = array_merge($msg, $content);
            $msg[] = '';
        }

        if (isset($rendered['html'])) {
            if ($multiPart) {
                $msg[] = '--' . $textBoundary;
                $msg[] = 'Content-Type: text/html; charset=' . $this->_getContentTypeCharset();
                $msg[] = 'Content-Transfer-Encoding: ' . $this->_getContentTransferEncoding();
                $msg[] = '';
            }
            $this->_htmlMessage = $rendered['html'];
            $content = explode("\n", $this->_htmlMessage);
            $msg = array_merge($msg, $content);
            $msg[] = '';
        }

        if ($textBoundary !== $relBoundary) {
            $msg[] = '--' . $textBoundary . '--';
            $msg[] = '';
        }

        if ($hasInlineAttachments) {
            $attachments = $this->_attachInlineFiles($relBoundary);
            $msg = array_merge($msg, $attachments);
            $msg[] = '';
            $msg[] = '--' . $relBoundary . '--';
            $msg[] = '';
        }

        if ($hasAttachments) {
            $attachments = $this->_attachFiles($boundary);
            $msg = array_merge($msg, $attachments);
        }
        if ($hasAttachments || $hasMultipleTypes) {
            $msg[] = '';
            $msg[] = '--' . $boundary . '--';
            $msg[] = '';
        }

        return $msg;
    }

    /**
     * Gets the text body types that are in this email message
     *
     * @return array Array of types. Valid types are 'text' and 'html'
     */
    protected function _getTypes()
    {
        $types = [$this->_emailFormat];
        if ($this->_emailFormat === 'both') {
            $types = ['html', 'text'];
        }

        return $types;
    }

    /**
     * Build and set all the view properties needed to render the templated emails.
     * If there is no template set, the $content will be returned in a hash
     * of the text content types for the email.
     *
     * @param string $content The content passed in from send() in most cases.
     * @return array The rendered content with html and text keys.
     */
    protected function _renderTemplates($content)
    {
        $types = $this->_getTypes();
        $rendered = [];
        $template = $this->viewBuilder()->getTemplate();
        if (empty($template)) {
            foreach ($types as $type) {
                $rendered[$type] = $this->_encodeString($content, $this->charset);
            }

            return $rendered;
        }

        $View = $this->createView();

        list($templatePlugin) = pluginSplit($View->getTemplate());
        list($layoutPlugin) = pluginSplit($View->getLayout());
        if ($templatePlugin) {
            $View->plugin = $templatePlugin;
        } elseif ($layoutPlugin) {
            $View->plugin = $layoutPlugin;
        }

        if ($View->get('content') === null) {
            $View->set('content', $content);
        }

        foreach ($types as $type) {
            $View->hasRendered = false;
            $View->setTemplatePath('Email' . DIRECTORY_SEPARATOR . $type);
            $View->setLayoutPath('Email' . DIRECTORY_SEPARATOR . $type);

            $render = $View->render();
            $render = str_replace(["\r\n", "\r"], "\n", $render);
            $rendered[$type] = $this->_encodeString($render, $this->charset);
        }

        foreach ($rendered as $type => $content) {
            $rendered[$type] = $this->_wrap($content);
            $rendered[$type] = implode("\n", $rendered[$type]);
            $rendered[$type] = rtrim($rendered[$type], "\n");
        }

        return $rendered;
    }

    /**
     * Return the Content-Transfer Encoding value based
     * on the set transferEncoding or set charset.
     *
     * @return string
     */
    protected function _getContentTransferEncoding()
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
    protected function _getContentTypeCharset()
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
     * It has certain limitations for viewVars that are good to know:
     *
     *    - ORM\Query executed and stored as resultset
     *    - SimpleXMLElements stored as associative array
     *    - Exceptions stored as strings
     *    - Resources, \Closure and \PDO are not supported.
     *
     * @return array Serializable array of configuration properties.
     * @throws \Exception When a view var object can not be properly serialized.
     */
    public function jsonSerialize()
    {
        $properties = [
            '_to', '_from', '_sender', '_replyTo', '_cc', '_bcc', '_subject',
            '_returnPath', '_readReceipt', '_emailFormat', '_emailPattern', '_domain',
            '_attachments', '_messageId', '_headers', '_appCharset', 'viewVars', 'charset', 'headerCharset'
        ];

        $array = ['viewConfig' => $this->viewBuilder()->jsonSerialize()];

        foreach ($properties as $property) {
            $array[$property] = $this->{$property};
        }

        array_walk($array['_attachments'], function (&$item, $key) {
            if (!empty($item['file'])) {
                $item['data'] = $this->_readFile($item['file']);
                unset($item['file']);
            }
        });

        array_walk_recursive($array['viewVars'], [$this, '_checkViewVars']);

        return array_filter($array, function ($i) {
            return !is_array($i) && strlen($i) || !empty($i);
        });
    }

    /**
     * Iterates through hash to clean up and normalize.
     *
     * @param mixed $item Reference to the view var value.
     * @param string $key View var key.
     * @return void
     */
    protected function _checkViewVars(&$item, $key)
    {
        if ($item instanceof Exception) {
            $item = (string)$item;
        }

        if (is_resource($item) ||
            $item instanceof Closure ||
            $item instanceof PDO
        ) {
            throw new RuntimeException(sprintf(
                'Failed serializing the `%s` %s in the `%s` view var',
                is_resource($item) ? get_resource_type($item) : get_class($item),
                is_resource($item) ? 'resource' : 'object',
                $key
            ));
        }
    }

    /**
     * Configures an email instance object from serialized config.
     *
     * @param array $config Email configuration array.
     * @return $this Configured email instance.
     */
    public function createFromArray($config)
    {
        if (isset($config['viewConfig'])) {
            $this->viewBuilder()->createFromArray($config['viewConfig']);
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
    public function serialize()
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
    public function unserialize($data)
    {
        return $this->createFromArray(unserialize($data));
    }
}
