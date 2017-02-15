<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Cookie;

use Cake\Utility\Hash;
use Cake\Utility\Security;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cookie object to build a cookie and turn it into a header value
 */
class Cookie implements CookieInterface
{

    use CookieCryptTrait;

    /**
     * Cookie name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Raw Cookie value
     */
    protected $value = '';

    /**
     * Cookie data
     *
     * If the raw cookie data was a serialized array and was expanded
     * this property will keep the data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * @var bool
     */
    protected $isExpanded = false;

    /**
     * Expiration time
     *
     * @var int
     */
    protected $expiresAt = 0;

    /**
     * Path
     *
     * @var string|null
     */
    protected $path = null;

    /**
     * Domain
     *
     * @var string|null
     */
    protected $domain = null;

    /**
     * Secure
     *
     * @var bool
     */
    protected $secure = false;

    /**
     * HTTP only
     *
     * @var bool
     */
    protected $httpOnly = false;

    /**
     * The key for encrypting and decrypting the cookie
     *
     * @var string
     */
    protected $encryptionKey = '';

    /**
     * Constructor
     *
     * @param string $name Cookie name
     * @param string $value Value of the cookie
     */
    public function __construct($name, $value)
    {
        $this->validateName($name);
        $this->setName($name);
        $this->setValue($value);
    }

    /**
     * Builds the expiration value part of the header string
     *
     * @return string
     */
    protected function _buildExpirationValue()
    {
        return sprintf(
            '; expires=%s',
            gmdate('D, d-M-Y H:i:s T', $this->expiresAt)
        );
    }

    /**
     * Returns a header value as string
     *
     * @return string
     */
    public function toHeaderValue()
    {
        $headerValue = sprintf('%s=%s', $this->name, urlencode($this->value));
        if ($this->expiresAt !== 0) {
            $headerValue .= $this->_buildExpirationValue();
        }
        if (!empty($this->path)) {
            $headerValue .= sprintf('; path=%s', $this->path);
        }
        if (!empty($this->domain)) {
            $headerValue .= sprintf('; domain=%s', $this->domain);
        }
        if ($this->secure) {
            $headerValue .= '; secure';
        }
        if ($this->httpOnly) {
            $headerValue .= '; httponly';
        }

        return $headerValue;
    }

    /**
     * Sets the cookie name
     *
     * @param string $name Name of the cookie
     * @return $this
     */
    public function setName($name)
    {
        $this->validateName($name);
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the cookie name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Validates the cookie name
     *
     * @param string $name Name of the cookie
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateName($name)
    {
        if (preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new InvalidArgumentException(
                sprintf('The cookie name `%s` contains invalid characters.', $name)
            );
        }

        if (empty($name)) {
            throw new InvalidArgumentException('The cookie name cannot be empty.');
        }
    }

    /**
     * Gets the raw cookie value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the raw cookie data
     *
     * @param string $value Value of the cookie to set
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Sets the path
     *
     * @param string|null $path Sets the path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Sets the domain
     *
     * @param string $domain Domain to set
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Sets the expiration date
     *
     * @param \DateTimeInterface $dateTime Date time object
     * @return $this
     */
    public function expiresAt(DateTimeInterface $dateTime)
    {
        $this->expiresAt = (int)$dateTime->format('U');

        return $this;
    }

    /**
     * Checks if a value exists in the cookie data
     *
     * @param string $path Path to check
     * @return bool
     */
    public function check($path)
    {
        return Hash::check($this->value, $path);
    }

    /**
     * Writes data to the cookie
     *
     * @param string $path Path to write to
     * @param mixer $value Value to write
     * @return $this
     */
    public function write($path, $value)
    {
        if (!$this->isExpanded) {
            throw new RuntimeException('The Cookie data has not been expanded');
        }

        Hash::insert($this->value, $path, $value);

        return $this;
    }

    /**
     * Read data from the cookie
     *
     * @param string $path Path to read the data from
     * @return mixed
     */
    public function read($path = null)
    {
        if (!$this->isExpanded) {
            throw new \RuntimeException('The Cookie data has not been expanded');
        }

        if ($path === null) {
            return $this->data;
        }

        return Hash::get($this->data, $path);
    }

    /**
     * Encrypts the cookie value
     *
     * @param string $key Encryption key
     * @return $this
     */
    public function encrypt($key)
    {
        $this->encryptionKey = $key;
        $this->value = $this->_encrypt($this->value, 'aes', $key);

        return $this;
    }

    /**
     * Decrypts the cookie value
     *
     * @param string $key Encryption key
     * @return $this
     */
    public function decrypt($key)
    {
        $this->encryptionKey = $key;
        $this->value = $this->_decrypt($this->value, 'aes', $key);

        return $this;
    }

    /**
     * Expands a serialized cookie value
     *
     * @return $this
     */
    public function expand()
    {
        if (!$this->isExpanded) {
            $this->data = $this->_explode($this->value);
            $this->isExpanded = true;
        }

        return $this;
    }

    /**
     * Serialized the data to a string
     *
     * @return $this
     */
    public function flatten()
    {
        if ($this->isExpanded) {
            $this->value = $this->_implode($this->value);
            $this->isExpanded = false;
        }

        return $this;
    }

    /**
     * Checks if the cookie value was expanded
     *
     * @return bool
     */
    public function isExpanded()
    {
        return $this->isExpanded;
    }

    /**
     * Sets the encryption key
     *
     * @param string $key Encryption key
     * @return $this
     */
    public function setEncryptionKey($key)
    {
        $this->encryptionKey = $key;

        return $this;
    }

    /**
     * Gets the cryptographic key
     *
     * @return string
     */
    public function getEncryptionKey()
    {
        if (empty($this->encryptionKey)) {
            return Security::salt();
        }

        return $this->encryptionKey;
    }
}
